<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;

class DeleteUserTranscriptionFunction
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

    public function deleteUserTranscription($transcriptionId)
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            $token = $this->session->get("jwtToken");

            $data = [
                "token" => $token,
                "meetingId" => $transcriptionId,
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if (in_array($response["error"], ["TOKEN_EXPIRED", "TOKEN_INVALID", "TOKEN_VERIFICATION_ERROR"])) {
                    $redirectResponse = new RedirectResponse("/logout");
                    $redirectResponse->send();
                    return null;
                } else if ($response["error"] == "MEETING_NOT_FOUND"){
                    $this->flashBag->add("meetings_error", "Could not delete transcription: not found.");
                } else {
                    $this->flashBag->add("meetings_error", "An error occurred deleting transcription.");
                }
            }

            if (isset($response["message"]) && ($response["message"] === "MEETING_DELETED")) {
                $this->flashBag->add("meetings_success", "Meeting deleted successfully.");
            }

        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase DeleteUserTranscription Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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
