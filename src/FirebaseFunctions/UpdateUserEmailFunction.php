<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;



class UpdateUserEmailFunction
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

    public function updateUserEmail(string $email)
    {
        try {

            $request = $this->requestStack->getCurrentRequest();
            $token = $this->session->get("jwtToken");
            
            $data = [
                "email" => $email,
                "token" => $token
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if (in_array($response["error"], ["TOKEN_EXPIRED", "TOKEN_INVALID", "TOKEN_VERIFICATION_ERROR"])) {
                    $redirectResponse = new RedirectResponse("/logout");
                    $redirectResponse->send();
                } else {
                    if ($response["error"] === "EMAIL_ALREADY_USED") {
                        $this->flashBag->add("email_update_error", "This email address is already in use.");
                    } else if ($response["error"] === "USER_EMAIL_NOT_UPDATED") {
                        $this->flashBag->add("email_update_error", "This email address is the same as your current email address.");
                    } else {
                        $this->flashBag->add("email_update_error", "An error occurred.");
                    }
                    $error = true;
                }
            }

            if (isset($response["message"])) {
                if ($response["message"] === "USER_EMAIL_UPDATED") {
                    $this->flashBag->add("email_update_success", "Your email address has been updated.");
                }
                $error = false;
            }

            return $error;

        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase UpdateUserEmail Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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