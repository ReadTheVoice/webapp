<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;


class LoginFunction
{
    private $accessToken;
    private $endpoint;
    protected $requestStack;
    protected $flashBag;

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
            $data = ["email" => $email, "password" => $password, "rememberMe" => $rememberMe];
            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                $this->handleError($response["error"]);
                return null;
            }

            if (isset($response["jwtToken"])) {
                return $this->createResponseWithCookie($response["jwtToken"], $rememberMe);
            }
            return null;
        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase LogIn Request Failed: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    private function makeRequest(string $endpoint, array $data)
    {
        $httpClient = HttpClient::create();
        $response = $httpClient->request("POST", $endpoint, [
            "headers" => ["Authorization" => $this->accessToken],
            "json" => $data
        ]);
        return $response->toArray();
    }

    private function createResponseWithCookie(string $jwtToken, bool $rememberMe)
    {
        $time = $rememberMe ? strtotime("+1 year") : strtotime("+1 day");
        $cookie = new Cookie("token", $jwtToken, $time);
        $response = new Response();
        $response->headers->setCookie($cookie);
        return $response;
    }

    private function handleError(string $error)
    {
        if ($error === "EMAIL_NOT_VERIFIED") {
            $this->flashBag->add("login_error", "Please verify your email before login.");
        } else if ($error === "LOGIN_ERROR") {
            $this->flashBag->add("login_error", "We were unable to log you in, please check your credentials.");
        } else {
            $this->flashBag->add("login_error", "Unknown error.");
        }
    }
}
