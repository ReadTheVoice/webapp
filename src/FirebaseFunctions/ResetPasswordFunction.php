<?php 

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;


class ResetPasswordFunction
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

    public function resetPassword(string $email)
    {
        try {
            
            $data = [
                "email" => $email,
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if ($response["error"] === "USER_NOT_FOUND") {
                    $error = false;
                } else if ($response["error"] === "PASSWORD_RESET_ERROR") {
                    $this->flashBag->add("reset_password_error", "An error occured. Please try again later.");
                    $error = true;
                } else                {
                    $this->flashBag->add("reset_password_error", "Unknown error.");
                    $error = true;
                }
            }

            if (isset($response["message"])) {
                if ($response["message"] === "PASSWORD_RESET_EMAIL_SENT") {
                    $error = false;
                }
            }

            return $error;
        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase Reset Password Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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