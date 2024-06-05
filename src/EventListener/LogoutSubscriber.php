<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use App\FirebaseFunctions\LogoutFunction;
use Symfony\Component\HttpFoundation\RequestStack;



class LogoutSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        LogoutFunction $logoutFunction
    ) {
        $this->logoutFunction = $logoutFunction;
    }

    public static function getSubscribedEvents(): array
    {
        return [LogoutEvent::class => 'onLogout'];
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();
        $token = $request->getSession()->get("jwtToken");
        if ($token) {
            $this->logoutFunction->logOut($token);
            $request->getSession()->clear();
        }
    }
}