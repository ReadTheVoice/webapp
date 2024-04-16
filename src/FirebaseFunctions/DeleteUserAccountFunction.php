<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;



class DeleteUserAccountFunction
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

    public function deleteUserAccount()
    {
        try {

            $request = $this->requestStack->getCurrentRequest();
            $token = $this->session->get("jwtToken");
            
            $data = [
                "token" => $token
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if (in_array($response["error"], ["TOKEN_EXPIRED", "TOKEN_INVALID", "TOKEN_VERIFICATION_ERROR"])) {
                    $redirectResponse = new RedirectResponse("/logout");
                    $redirectResponse->send();
                } else {
                    if ($response["error"] === "USER_NOT_FOUND") {
                        $this->flashBag->add("delete_account_error", "User not found.");
                    } else {
                        $this->flashBag->add("delete_account_error", "An error occurred.");
                    }
                    $error = true;
                }
            }

            if (isset($response["message"])) {
                $error = false;
                $redirectResponse = new RedirectResponse("/logout");
                $redirectResponse->send();
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