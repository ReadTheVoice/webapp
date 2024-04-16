<?php 

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;


class LogOutOtherSessionsFunction
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

    public function logOutOtherSessions()
    {
        try {

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
                    if ($response["error"] === "LOGOUT_OTHER_SESSIONS_ERROR") {
                        $this->flashBag->add("logout_other_sessions_error", "An error occurred when deleting the other sessions.");
                    }
                    $this->flashBag->add("logout_other_sessions_error", "An error occurred.");
                }
                    $error = true;
            }

            if (isset($response["message"])) {
                if ($response["message"] === "LOGOUT_OTHER_SESSIONS_SUCCESS") {
                    $this->flashBag->add("logout_other_sessions_success", "Your other sessions have been disconnected.");
                }
                $error = false;
            }

            return $error;

        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase LogOutOtherSessions Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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