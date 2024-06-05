<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;

class GetUserTranscriptionFunction
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

    public function getUserTranscription($transcriptionId)
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
                    throw new \RuntimeException("Firebase GetUserTranscription Request Failed: Token Expired", 401);
                    return null;
                } else if ($response["error"] == "MEETING_NOT_FOUND"){
                    $this->flashBag->add("meetings_error", "Could not get meeting: not found.");
                    return null;
                } else {
                    $this->flashBag->add("meetings_error", "An error occurred getting transcription.");
                    return null;
                }
            }


            if (isset($response["meeting"])) {
                
                if (isset($response["meeting"]["createdAt"])) {
                    $timestamp = $response["meeting"]["createdAt"]["_seconds"] * 1000 + round($response["meeting"]["createdAt"]["_nanoseconds"] / 1000000);
                    $response["meeting"]["createdAtString"] = date("d/m/Y H:i", $timestamp / 1000);
                } else {
                    $response["meeting"]["createdAtString"] = "N/A";
                }
            
                if (isset($response["meeting"]["endDate"]) && $response["meeting"]["endDate"] != null) {
                    $timestamp = $response["meeting"]["endDate"]["_seconds"] * 1000 + round($response["meeting"]["endDate"]["_nanoseconds"] / 1000000);
                    $response["meeting"]["endDateString"] = date("d/m/Y H:i", $timestamp / 1000);
                } else {
                    $response["meeting"]["endDateString"] = "N/A";
                }
                
                if (isset($response["meeting"]["scheduledDate"]) && $response["meeting"]["scheduledDate"] != null) {
                    $timestamp = $response["meeting"]["scheduledDate"]["_seconds"] * 1000 + round($response["meeting"]["scheduledDate"]["_nanoseconds"] / 1000000);
                    $response["meeting"]["scheduledDateString"] = date("d/m/Y H:i", $timestamp / 1000);
                } else {
                    $response["meeting"]["scheduledDateString"] = "N/A";
                }

                                
                if (isset($response["meeting"]["deletionDate"]) && $response["meeting"]["deletionDate"] != null) {
                    $timestamp = $response["meeting"]["deletionDate"]["_seconds"] * 1000 + round($response["meeting"]["deletionDate"]["_nanoseconds"] / 1000000);
                    $response["meeting"]["deletionDateString"] = date("d/m/Y H:i", $timestamp / 1000);
                } else {
                    $response["meeting"]["deletionDateString"] = "N/A";
                }
            
                return $response;
            }

        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase GetUserTranscription Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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
