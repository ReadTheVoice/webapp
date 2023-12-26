<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;


class LoginFunction
{
    private $accessToken;
    private $endpoint;
    protected $requestStack;

    public function __construct(string $accessToken, string $endpoint, RequestStack $requestStack)
    {
        $this->accessToken = $accessToken;
        $this->endpoint = $endpoint;
        $this->requestStack = $requestStack;
        $this->flashBag = $this->requestStack->getSession()->getFlashBag();
    }

    public function logIn(string $email, string $password, bool $rememberMe)
    {
        try {
            
            $data = [
                "email" => $email,
                "password" => $password,
                "rememberMe" => $rememberMe,            
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if ($response["error"] === "EMAIL_NOT_VERIFIED") {
                    $this->flashBag->add("login_error", "Please verify your email before login.");
                } else if ($response["error"] === "LOGIN_ERROR") {
                    $this->flashBag->add("login_error", "We were unable to log you in, please check your credentials.");
                } else {
                    $this->flashBag->add("login_error", "Unknown error.");
                }
                $error = true;
            }

            if (isset($response["jwtToken"])) {
                if ($rememberMe) {
                    $time = strtotime("+1 year");
                } else {
                    $time = strtotime("+1 day");
                }
                $cookie = new Cookie("token", $response["jwtToken"], $time);
                $response = new Response();
                $response->headers->setCookie($cookie);
                $response->send();
                $error = false;
            }

            return $error;
        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase LogIn Request Failed: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    private function makeRequest(string $endpoint, array $data)
    {
        $httpClient = HttpClient::create();

        $response = $httpClient->request(
            "POST", $endpoint, [
            "headers" => [
                "Authorization" => $this->accessToken,
            ],
            "json" => $data,
            ]
        );

        return $response->toArray();
    }

}