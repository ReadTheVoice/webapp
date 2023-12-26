<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;


class LogoutFunction
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

    public function logOut(string $token)
    {
        try {
            
            $data = [
                "token" => $token
            ];

            $this->makeRequest($this->endpoint, $data);

        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase LogOut Request Failed: {$e->getMessage()}", $e->getCode(), $e);
        }
    }

    private function makeRequest(string $endpoint, array $data)
    {
        $httpClient = HttpClient::create();

        $response = $httpClient->request("POST", $endpoint, [
            "headers" => [
                "Authorization" => $this->accessToken,
            ],
            "json" => $data,
        ]);

        return $response->toArray();
    }

}