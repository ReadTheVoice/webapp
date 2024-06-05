<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;

class RegisterFunction
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

    public function register(string $firstname, string $lastname, string $email, string $password)
    {
        try {
            
            $data = [
                "firstName" => $firstname,
                "lastName" => $lastname,
                "email" => $email,
                "password" => $password
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if ($response["error"] === "EMAIL_ALREADY_EXISTS") {
                    $this->flashBag->add("register_error", "This email address is already in use by another user.");
                } else if ($response["error"] === "REGISTRATION_ERROR") {
                    $this->flashBag->add("register_error", "An error occured. Please try again later.");
                } else
                {
                    $this->flashBag->add("register_error", "Unknown error.");
                }
                $error = true;
                return $error;
            }

            if (isset($response["message"])) {
                if ($response["message"] === "SUCCESSFULLY_REGISTERED") {
                    $error = false;
                    return $error;
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase Register Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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