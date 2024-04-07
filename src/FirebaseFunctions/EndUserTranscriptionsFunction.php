<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EndUserTranscriptionFunction
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

    public function EndUserTranscription($transcriptionId)
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            $token = $request->cookies->get("token");

            $data = [
                "token" => $token,
                "meetingId" => $transcriptionId,
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if (in_array($response["error"], ['TOKEN_EXPIRED', 'TOKEN_INVALID', 'TOKEN_VERIFICATION_ERROR'])) {
                    $redirectResponse = new RedirectResponse('/logout');
                    $redirectResponse->send();
                    return null;
                } else if ($response["error"] == "MEETING_NOT_FOUND"){
                    $this->flashBag->add("meetings_error", "Could not end transcription: not found.");
                } else {
                    $this->flashBag->add("meetings_error", "An error occurred ending transcription.");
                }
            }

            if (isset($response["message"]) && ($response["message"] === "MEETING_FINISHED")) {
                $this->flashBag->add("meetings_success", "Transcription ended successfully.");
            }

            if (isset($response["message"]) && ($response["message"] === "MEETING_DELETED")) {
                $this->flashBag->add("meetings_success", "Meeting ended and transcription deleted successfully.");
                $redirectResponse = new RedirectResponse('/dashboard');
                $redirectResponse->send();
            }


        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase EndUserTranscription Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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
