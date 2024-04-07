<?php

namespace App\FirebaseFunctions;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CreateUserTranscriptionFunction
{
    private $accessToken;
    private $endpoint;
    protected $requestStack;
    protected $flashBag;

    public function __construct(string $accessToken, string $endpoint, RequestStack $requestStack)
    {
        $this->accessToken = $accessToken;
        $this->endpoint = $endpoint;
        $this->requestStack = $requestStack;
        $this->flashBag = $this->requestStack->getSession()->getFlashBag();
    }

    public function createUserTranscription($name, $description, $isAccessibleAfter, $language, $allowDownload, $scheduledDate, $deletionDate = null)
    {
        try {
            $request = $this->requestStack->getCurrentRequest();
            $token = $request->cookies->get("token");

            $data = [
                "name" => $name,
                "description" => $description,
                "isTranscriptAccessibleAfter" => $isAccessibleAfter,
                "language" => $language,
                "allowDownload" => $allowDownload,
                "scheduledDate" => $scheduledDate,
                "deletionDate" => $deletionDate,
                "token" => $token,
            ];

            $response = $this->makeRequest($this->endpoint, $data);

            if (isset($response["error"])) {
                if (in_array($response["error"], ['TOKEN_EXPIRED', 'TOKEN_INVALID', 'TOKEN_VERIFICATION_ERROR'])) {
                    $redirectResponse = new RedirectResponse('/logout');
                    $redirectResponse->send();
                    return false;
                } else {
                    $this->flashBag->add("create_transcription_error", "An error occurred while creating the transcription.");
                }
                return false;
            }

            if (isset($response["message"]) && $response["message"] === "MEETING_CREATED") {
                $this->flashBag->add("meetings_success", "Your transcription has been created successfully.");
                return true;
            }

            return false;
        } catch (\Exception $e) {
            throw new \RuntimeException("Firebase CreateUserTranscription Request Failed: {$e->getMessage()}", $e->getCode(), $e);
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
