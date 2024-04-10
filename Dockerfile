FROM composer:2.7.2 as composer-install
WORKDIR /app/
COPY composer.json composer.lock /app/
RUN composer install --no-dev --no-scripts --no-autoloader && composer dump-autoload --optimize

FROM node:lts as webpack-builder
WORKDIR /app/
COPY assets ./assets
COPY package.json ./
COPY webpack.config.js ./
RUN npm install
RUN npm run build

FROM php:8.1-apache
RUN apt-get update && apt-get install -y \
    acl \
    && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo
ENV APP_ENV=prod
WORKDIR /var/www/project/
COPY --from=composer-install /app/vendor /var/www/project/vendor
COPY ./vhost.conf /etc/apache2/sites-available/000-default.conf
COPY --from=webpack-builder /app/public/build /var/www/project/public/build
COPY . /var/www/project/
RUN chown -R www-data:www-data /var/www/project
USER www-data

EXPOSE 8000

CMD ["apache2-foreground"]
