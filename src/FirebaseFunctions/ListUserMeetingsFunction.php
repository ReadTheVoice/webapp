<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ListUserMeetingsFunction
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

    public function listUserMeetings()
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            $token = $request->cookies->get("token");

            $data = [
                "token" => $token,
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if (in_array($response["error"], ['TOKEN_EXPIRED', 'TOKEN_INVALID', 'TOKEN_VERIFICATION_ERROR'])) {
                    $redirectResponse = new RedirectResponse('/logout');
                    $redirectResponse->send();
                    return null;
                } else {
                    $this->flashBag->add("meetings_error", "An error occurred listing meetings.");
                }
                return null;
            }

            if (isset($response["meetings"])) {
                foreach ($response["meetings"] as &$meeting) {
                    if (isset($meeting["createdAt"])) {
                        $timestamp = $meeting["createdAt"]['_seconds'] * 1000 + round($meeting["createdAt"]['_nanoseconds'] / 1000000);
                        $meeting["createdAtString"] = date("d/m/Y H:i", $timestamp / 1000);;
                    } else {
                        $meeting["createdAtString"] = "N/A";
                    }

                    if (isset($meeting["scheduledDate"]) && $meeting["scheduledDate"] != null) {
                        $timestamp = $meeting["scheduledDate"]['_seconds'] * 1000 + round($meeting["scheduledDate"]['_nanoseconds'] / 1000000);
                        $meeting["scheduledDateString"] = date("d/m/Y H:i", $timestamp / 1000);
                    } else {
                        $meeting["scheduledDateString"] = "N/A";
                    }
                
                    if (isset($meeting["endDate"])) {
                        $timestamp = $meeting["endDate"]['_seconds'] * 1000 + round($meeting["endDate"]['_nanoseconds'] / 1000000);
                        $meeting["endDateString"] = date("d/m/Y H:i", $timestamp);
                    } else {
                        $meeting["endDateString"] = "N/A";
                    }
                }
                unset($meeting);
                
                
                return $response["meetings"];
            } else {
                $this->flashBag->add("meetings_info", "No meetings found.");
                return null;
            }

        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase ListUserMeetings Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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
