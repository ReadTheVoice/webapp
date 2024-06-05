<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;


class VerifyTokenFunction
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

    public function verifyToken()
    {
        try {

            $request = $this->requestStack->getCurrentRequest();
            $token = $this->session->get("jwtToken");
           
            
                $data = [
                    "token" => $token,
                ];
    
                $response = $this->makeRequest($this->endpoint, $data);

                if (isset($response["error"])) {
                    if (in_array($response["error"], ["TOKEN_EXPIRED", "TOKEN_INVALID", "TOKEN_VERIFICATION_ERROR"])) {
                        $redirectResponse = new RedirectResponse("/logout");
                        $redirectResponse->send();
                    } else {
                        $this->flashBag->add("profile_error", "An error occurred.");
                    }
                    $error = true;
                    return $error;
                } else {
                    $email = $response["email"];
                    $firstName = $response["firstName"];
                    $lastName = $response["lastName"];
                }

                return [$email, $firstName, $lastName];
        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase VerifyTokenFunction Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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