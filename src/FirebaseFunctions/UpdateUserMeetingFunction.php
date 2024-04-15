<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UpdateUserMeetingFunction
{
    private $accessToken;
    private $endpoint;
    protected $requestStack;
    protected $flashBag;
    protected $session;

    public function __construct(string $accessToken, string $endpoint, RequestStack $requestStack)
    {
        $this->accessToken = $accessToken;
        $this->endpoint = $endpoint;
        $this->requestStack = $requestStack;
        $this->session = $requestStack->getSession();
        $this->flashBag = $this->session->getFlashBag();
    }

    public function updateMeeting($meetingId, $name, $description, $isAccessibleAfter, $language, $allowDownload, $scheduledDate, $deletionDate = null, $transcript = null)
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            $token = $this->session->get("jwtToken");

            $data = [
                "meetingId" => $meetingId,
                "name" => $name,
                "description" => $description,
                "isTranscriptAccessibleAfter" => $isAccessibleAfter,
                "language" => $language,
                "allowDownload" => $allowDownload,
                "scheduledDate" => $scheduledDate,
                "deletionDate" => $deletionDate,
                "transcript" => $transcript,
                "token" => $token,
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if (in_array($response["error"], ["TOKEN_EXPIRED", "TOKEN_INVALID", "TOKEN_VERIFICATION_ERROR"])) {
                    $redirectResponse = new RedirectResponse("/logout");
                    $redirectResponse->send();
                } else {
                    $this->flashBag->add("edit_meeting_error", "An error occurred while updating the meeting.");
                }
            }

            if (isset($response["message"]) && $response["message"] === "MEETING_UPDATED") {
                $this->flashBag->add("edit_meeting_success", "Meeting updated successfully.");
            }

        } catch (TransportExceptionInterface $e) {
            $this->flashBag->add("edit_meeting_error", "Network error: " . $e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException("Update Meeting Request Failed: " . $e->getMessage(), 0, $e);
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
