<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;

class UpdateUserProfileFunction
{
    private $accessToken;
    private $endpoint;
    protected $requestStack;

    public function __construct(string $accessToken, string $endpoint, RequestStack $requestStack)
    {
        $this->accessToken = $accessToken;
        $this->endpoint = $endpoint;
        $this->requestStack = $requestStack;
        $this->session = $this->requestStack->getSession();
        $this->flashBag = $this->requestStack->getSession()->getFlashBag();
    }

    public function updateUserProfile(string $firstName, string $lastName)
    {
        try {

            $request = $this->requestStack->getCurrentRequest();
            $token = $this->session->get("jwtToken");
            
            $data = [
                "token" => $token,
                "firstName" => $firstName,
                "lastName" => $lastName
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if (in_array($response["error"], ["TOKEN_EXPIRED", "TOKEN_INVALID", "TOKEN_VERIFICATION_ERROR"])) {
                    $redirectResponse = new RedirectResponse("/logout");
                    $redirectResponse->send();
                } else {
                    $this->flashBag->add("profile_error", "An error occurred.");
                }
            }

            if (isset($response["message"])) {
                if ($response["message"] === "USER_PROFILE_UPDATED") {
                    $this->flashBag->add("profile_success", "Your profile has been updated.");
                }
            }

        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase UpdateUserProfile Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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