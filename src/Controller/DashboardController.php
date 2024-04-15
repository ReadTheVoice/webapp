<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use App\FirebaseFunctions\VerifyTokenFunction;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\FirebaseFunctions\ResetPasswordFunction;
use App\FirebaseFunctions\UpdateUserEmailFunction;
use App\FirebaseFunctions\DeleteUserAccountFunction;
use App\FirebaseFunctions\UpdateUserProfileFunction;
use App\FirebaseFunctions\ListUserMeetingsFunction;
use App\FirebaseFunctions\CreateUserTranscriptionFunction;
use App\FirebaseFunctions\DeleteUserTranscriptionFunction;
use App\FirebaseFunctions\EndUserTranscriptionFunction;
use App\FirebaseFunctions\GetUserTranscriptionFunction;
use App\FirebaseFunctions\UpdateUserMeetingFunction;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
    #[Route("", name: "app_dashboard")]
    public function index(Request $request): Response
    {
        if (!$request->getSession()->get("jwtToken")) {
            return $this->redirectToRoute("app_login");
        } else {
            return $this->redirectToRoute("app_dashboard_transcriptions");
        }
    }

    #[Route("/meetings", name: "app_dashboard_transcriptions")]
    public function transcriptions(Request $request, VerifyTokenFunction $verifyTokenFunction, ListUserMeetingsFunction $listUserMeetingsFunction): Response
    { 
        if ($request->getMethod() === "GET") {
            if (!$request->getSession()->get("jwtToken")) {
                return $this->redirectToRoute("app_login");
            } else {
                $meetings = $listUserMeetingsFunction->listUserMeetings();
                return $this->render("dashboard/meetings/index.html.twig", ["meetings" => $meetings]);
            }
        }
    }

    #[Route("/meetings/create", name: "app_dashboard_create_transcription")]
    public function createTranscription(Request $request, VerifyTokenFunction $verifyTokenFunction, CreateUserTranscriptionFunction $createUserTranscriptionFunction, ValidatorInterface $validator): Response
    {
        if ($request->getMethod() === "GET") {
            if (!$request->getSession()->get("jwtToken")) {
                return $this->redirectToRoute("app_login");
            } else {
                $userData = $verifyTokenFunction->verifyToken();
                return $this->render("dashboard/meetings/create.html.twig");
            }
        } else if ($request->getMethod() === "POST") {
            $token = $request->request->get("token");
            if (!$this->isCsrfTokenValid("createTranscription", $token)) {
                return $this->redirectToRoute("app_dashboard_create_transcription");
            }

            $name = $request->request->get("name");
            $description = $request->request->get("description");
            $language = $request->request->get("language");
            $allowDownload = $request->request->get("allowDownload") === 'on';
            $isAccessibleAfter = $request->request->get("isaccessibleafter") === 'on';
            $scheduledDateInput = $request->request->get("scheduledDate");
            $enabledeletionDate = $request->request->get("enabledeletionDate") === 'on';
            $deletionDateInput = $enabledeletionDate ? $request->request->get("deletionDate") : null;

            $scheduledDate = \DateTime::createFromFormat('Y-m-d\TH:i', $scheduledDateInput);
            $deletionDate = $enabledeletionDate && $deletionDateInput ? \DateTime::createFromFormat('Y-m-d\TH:i', $deletionDateInput) : null;

            $constraints = new Assert\Collection([
                "fields" => [
                    "name" => new Assert\NotBlank(),
                    "description" => new Assert\NotBlank(),
                    "language" => new Assert\Choice([
                        "choices" => ["bg", "cs", "da", "nl", "en", "et", "fr", "de", "el", "hi", "hu", "id", "it", "ja", "ko", "lv", "lt", "ms", "no", "pl", "pt", "ro", "ru", "sk", "es", "sv", "th", "tr", "uk", "vi"],
                        "message" => "Please select a valid language.",
                    ]),
                    "scheduledDate" => new Assert\NotNull([
                        'message' => 'Scheduled date must be provided.',
                    ]),
                    "deletionDate" => $enabledeletionDate ? new Assert\DateTime([
                        'format' => 'Y-m-d H:i:s',
                        'message' => 'End date must be a valid date and time.',
                    ]) : new Assert\Optional(),
                ],
                "allowMissingFields" => true,
            ]);

            $input = [
                "name" => $name,
                "description" => $description,
                "language" => $language,
                "scheduledDate" => $scheduledDate ? $scheduledDate->format('Y-m-d H:i:s') : null,
                "deletionDate" => $deletionDate ? $deletionDate->format('Y-m-d H:i:s') : null,
            ];

            $violations = $validator->validate($input, $constraints);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash("create_transcription_error", $violation->getMessage());
                }
                return $this->redirectToRoute("app_dashboard_create_transcription", [
                    "name" => $name, "description" => $description, "scheduledDate" => $scheduledDateInput, "deletionDate" => $deletionDateInput, "isAccessibleAfter" => $isAccessibleAfter, "allowDownload" => $allowDownload, "language" => $language
                ]);
            }

            if ($deletionDate && $scheduledDate && $deletionDate <= $scheduledDate) {
                $this->addFlash("create_transcription_error", "End date must be after the scheduled start date.");
                return $this->redirectToRoute("app_dashboard_create_transcription", [
                    "name" => $name,
                    "description" => $description,
                    "scheduledDate" => $scheduledDateInput,
                    "deletionDate" => $deletionDateInput,
                    "isAccessibleAfter" => $isAccessibleAfter,
                    "allowDownload" => $allowDownload,
                    "language" => $language,
                ]);
            }

            $status = $createUserTranscriptionFunction->createUserTranscription($name, $description, $isAccessibleAfter, $language, $allowDownload, $scheduledDate->format('Y-m-d H:i:s'), $deletionDate ? $deletionDate->format('Y-m-d H:i:s') : null);

            if ($status) {
                return $this->redirectToRoute("app_dashboard_transcriptions");
            } else {
                return $this->redirectToRoute("app_dashboard_create_transcription", [
                    "name" => $name,
                    "description" => $description,
                    "scheduledDate" => $scheduledDateInput,
                    "deletionDate" => $deletionDateInput,
                    "isAccessibleAfter" => $isAccessibleAfter,
                    "allowDownload" => $allowDownload,
                    "language" => $language,
                ]);
            }
        }
    }

    #[Route("/meetings/edit/{transcriptionId}", name: "app_dashboard_transcription_edit")]
    public function updateTranscription(Request $request, VerifyTokenFunction $verifyTokenFunction, GetUserTranscriptionFunction $getUserTranscriptionFunction, UpdateUserMeetingFunction $updateUserMeetingFunction, ValidatorInterface $validator, $transcriptionId, $redirectToMeeting = null): Response
    {
        if ($request->getMethod() === "GET") {
            if (!$request->getSession()->get("jwtToken")) {
                return $this->redirectToRoute("app_login");
            } else {
                $meeting = $getUserTranscriptionFunction->getUserTranscription($transcriptionId)["meeting"];
                if ($meeting["isFinished"]) {
                    return $this->redirectToRoute('app_dashboard_transcriptions_get', ['transcriptionId' => $transcriptionId]);
                }
                $scheduledDate = (new \DateTime())->setTimestamp($meeting['scheduledDate']['_seconds']);
                $deletionDate = $meeting['deletionDate'] ? (new \DateTime())->setTimestamp($meeting['deletionDate']['_seconds']) : null;
                return $this->render("dashboard/meetings/update.html.twig", [
                    "transcriptionId" => $transcriptionId,
                    "name" => $meeting["name"],
                    "description" => $meeting["description"],
                    "language" => $meeting["language"],
                    "allowDownload" => $meeting["allowDownload"],
                    "isTranscriptAccessibleAfter" => $meeting["isTranscriptAccessibleAfter"],
                    "scheduledDate" => $scheduledDate,
                    "transcript" => $meeting["transcript"],
                    "deletionDate" => $deletionDate,
                    "redirectToMeeting" => $redirectToMeeting,
                ]);
            }
        } else if ($request->getMethod() === "POST") {
            $token = $request->request->get("token");
            if (!$this->isCsrfTokenValid("editTranscription", $token)) {
                return $this->redirectToRoute("app_dashboard_transcription_edit", [
                    "transcriptionId" => $transcriptionId,
                    "redirectToMeeting" => $redirectToMeeting,
                ]);
            }

            $name = $request->request->get("name");
            $description = $request->request->get("description");
            $language = $request->request->get("language");
            $allowDownload = $request->request->get("allowDownload") === 'on';
            $isAccessibleAfter = $request->request->get("isaccessibleafter") === 'on';
            $scheduledDateInput = $request->request->get("scheduledDate");
            $enabledeletionDate = $request->request->get("enabledeletionDate") === 'on';
            $deletionDateInput = $enabledeletionDate ? $request->request->get("deletionDate") : null;
            $transcript = $request->request->get("transcript") ?? null;


            $scheduledDate = \DateTime::createFromFormat('Y-m-d\TH:i', $scheduledDateInput);
            $deletionDate = $enabledeletionDate && $deletionDateInput ? \DateTime::createFromFormat('Y-m-d\TH:i', $deletionDateInput) : null;

            $constraints = new Assert\Collection([
                "fields" => [
                    "name" => new Assert\NotBlank(),
                    "description" => new Assert\NotBlank(),
                    "language" => new Assert\Choice([
                        "choices" => ["bg", "cs", "da", "nl", "en", "et", "fr", "de", "el", "hi", "hu", "id", "it", "ja", "ko", "lv", "lt", "ms", "no", "pl", "pt", "ro", "ru", "sk", "es", "sv", "th", "tr", "uk", "vi"],
                        "message" => "Please select a valid language.",
                    ]),
                    "scheduledDate" => new Assert\NotNull([
                        'message' => 'Scheduled date must be provided.',
                    ]),
                    "deletionDate" => $enabledeletionDate ? new Assert\DateTime([
                        'format' => 'Y-m-d H:i:s',
                        'message' => 'End date must be a valid date and time.',
                    ]) : new Assert\Optional(),
                ],
                "allowMissingFields" => true,
            ]);

            $input = [
                "name" => $name,
                "description" => $description,
                "language" => $language,
                "scheduledDate" => $scheduledDate ? $scheduledDate->format('Y-m-d H:i:s') : null,
                "deletionDate" => $deletionDate ? $deletionDate->format('Y-m-d H:i:s') : null,
            ];

            $violations = $validator->validate($input, $constraints);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $this->addFlash("edit_meeting_error", $violation->getMessage());
                }
                return $this->redirectToRoute("app_dashboard_transcription_edit", [
                    "transcriptionId" => $transcriptionId,
                    "redirectToMeeting" => $redirectToMeeting,
                ]);
            }

            if ($deletionDate && $scheduledDate && $deletionDate <= $scheduledDate) {
                $this->addFlash("edit_meeting_error", "End date must be after the scheduled start date.");
                return $this->redirectToRoute("app_dashboard_transcription_edit", [
                    "transcriptionId" => $transcriptionId,
                    "redirectToMeeting" => $redirectToMeeting,
                ]);
            }

            $updateUserMeetingFunction->updateMeeting($transcriptionId, $name, $description, $isAccessibleAfter, $language, $allowDownload, $scheduledDate->format('Y-m-d H:i:s'), $deletionDate ? $deletionDate->format('Y-m-d H:i:s') : null, $transcript ?? null);

            
                return $this->redirectToRoute("app_dashboard_transcription_edit", [
                    "transcriptionId" => $transcriptionId,
                    "redirectToMeeting" => $redirectToMeeting,
                ]);
            
            
        }
    }
    


    #[Route("/meetings/delete/{transcriptionId}", name: "app_dashboard_transcriptions_delete")]
    public function deleteTranscription(Request $request, VerifyTokenFunction $verifyTokenFunction, DeleteUserTranscriptionFunction $deleteUserTranscriptionFunction, $transcriptionId): Response
    { 
        if ($request->getMethod() === "GET") {
            if (!$request->getSession()->get("jwtToken")) {
                return $this->redirectToRoute("app_login");
            } else {
                $deleteUserTranscriptionFunction->deleteUserTranscription($transcriptionId);
                return $this->redirectToRoute("app_dashboard_transcriptions");
            }
        }
    }

    #[Route("/meetings/end/{transcriptionId}", name: "app_dashboard_transcriptions_end")]
    public function endTranscription(Request $request, VerifyTokenFunction $verifyTokenFunction, EndUserTranscriptionFunction $endUserTranscriptionFunction, $transcriptionId, $redirectToMeeting = null): Response
    { 
        if ($request->getMethod() === "GET") {
            if (!$request->getSession()->get("jwtToken")) {
                return $this->redirectToRoute("app_login");
            } else {
                $endUserTranscriptionFunction->endUserTranscription($transcriptionId);
                if ($redirectToMeeting) {
                    return $this->redirectToRoute('app_dashboard_transcriptions_get', ['transcriptionId' => $transcriptionId]);
                } else {
                    return $this->redirectToRoute("app_dashboard_transcriptions");
                }
                
            }
        }
    }

    #[Route("/meetings/view/{transcriptionId}", name: "app_dashboard_transcriptions_get")]
    public function getTranscription(Request $request, VerifyTokenFunction $verifyTokenFunction, GetUserTranscriptionFunction $getUserTranscriptionFunction, $transcriptionId): Response
    { 
        if ($request->getMethod() === "GET") {
            if (!$request->getSession()->get("jwtToken")) {
                return $this->redirectToRoute("app_login");
            } else {
                $return = $getUserTranscriptionFunction->getUserTranscription($transcriptionId);
                if ($return != null) {
                    $meeting = $return['meeting'];
                    return $this->render('dashboard/meetings/show.html.twig', [
                        'meeting' => $meeting
                    ]);
                } else {
                    return $this->redirectToRoute("app_dashboard_transcriptions");
                }

            }
        }
    }
    

    #[Route("/settings", name: "app_dashboard_settings")]
    public function settings(Request $request): Response
    {
        if (!$request->getSession()->get("jwtToken")) {
            
        } else {
            return $this->redirectToRoute("app_dashboard_settings_profile");
        }
    }

    #[Route("/settings/profile", name: "app_dashboard_settings_profile")]
    public function settingsProfile(Request $request, VerifyTokenFunction $verifyTokenFunction, UpdateUserProfileFunction $updateUserProfileFonction, ValidatorInterface $validator): Response
    {
        if ($request->getMethod() === "GET") {
            $userData = $verifyTokenFunction->verifyToken();
            if (is_bool($userData)) {
                $email = "";
                $firstName = "";
                $lastName = "";
            } else {
                $email = $userData[0];
                $firstName = $userData[1];
                $lastName = $userData[2];
            }
            return $this->render(
                "dashboard/settings/profile.html.twig", [
                "email" => $email,
                "firstName" => $firstName,
                "lastName" => $lastName
                ]
            );
        } else if ($request->getMethod() === "POST") {
            $token = $request->request->get("token");
            if (!$this->isCsrfTokenValid("updateProfile", $token)) {
                return $this->redirectToRoute("app_dashboard_settings_profile");
            }

            $firstName = $request->request->get("firstname");
            $lastName = $request->request->get("lastname");


            $firstnameConstraint = new Assert\NotBlank();
            $firstnameConstraint->message = "First name cannot be empty.";

            $firstnameRegexConstraint = new Assert\Regex(
                [
                "pattern" => "/^[a-zA-Z0-9\s-]+$/",
                "message" => "First name can only contain letters, numbers, spaces, and hyphens.",
                ]
            );

            $lastnameConstraint = new Assert\NotBlank();
            $lastnameConstraint->message = "Last name cannot be empty.";

            $lastnameRegexConstraint = new Assert\Regex(
                [
                "pattern" => "/^[a-zA-Z0-9\s-]+$/",
                "message" => "Last name can only contain letters, numbers, spaces, and hyphens.",
                ]
            );

            $errors = $validator->validate($firstName, [$firstnameConstraint, $firstnameRegexConstraint]);
            $errors = $validator->validate($lastName, [$lastnameConstraint, $lastnameRegexConstraint]);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash("profile_error", $error->getMessage());
                }
                return $this->redirectToRoute("app_dashboard_settings_profile");
            }

            $updateUserProfileFonction->updateUserProfile($firstName, $lastName);

            return $this->redirectToRoute("app_dashboard_settings_profile");

        }
    }

    #[Route("/settings/email", name: "app_dashboard_settings_email")]
    public function settingsEmail(Request $request, VerifyTokenFunction $verifyTokenFunction, UpdateUserEmailFunction $updateUserEmailFunction, ValidatorInterface $validator): Response
    {
        if ($request->getMethod() === "GET") {
            $userData = $verifyTokenFunction->verifyToken();
            return $this->render(
                "dashboard/settings/update_email.html.twig", [
                "email" => $userData[0],
                ]
            );
        } else if ($request->getMethod() === "POST") {
            $token = $request->request->get("token");
            if (!$this->isCsrfTokenValid("updateEmail", $token)) {
                return $this->redirectToRoute("app_dashboard_settings_email");
            }
    
            $email = $request->request->get("email");
    
            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "Email address not valid.";
    
            $errors = $validator->validate(
                $email,
                $emailConstraint
            );
    
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash("email_update_error", $error->getMessage());
                }
                return $this->redirectToRoute("app_dashboard_settings_email");
            }
    
            $error = $updateUserEmailFunction->updateUserEmail($email);
    
            if (!$error) {
                return $this->redirectToRoute("app_logout");
            } else {
                return $this->redirectToRoute("app_dashboard_settings_email");
            }
        }
    }

    #[Route("/settings/password", name: "app_dashboard_settings_password")]
    public function settingsPassword(Request $request, VerifyTokenFunction $verifyTokenFunction, ResetPasswordFunction $resetPasswordFunction, ValidatorInterface $validator): Response
    {
        if ($request->getMethod() === "GET") {
            $userData = $verifyTokenFunction->verifyToken();

            return $this->render(
                "dashboard/settings/password.html.twig", [
                "email" => $userData[0],
                ]
            );
        } else if ($request->getMethod() === "POST") {
            $token = $request->request->get("token");
            if (!$this->isCsrfTokenValid("resetUserPassword", $token)) {
                return $this->redirectToRoute("app_reset_password");
            }
            $email = $request->request->get("email");

            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "Email address not valid.";

            $errors = $validator->validate(
                $email,
                $emailConstraint
            );

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash("reset_password_error", $error->getMessage());
                }
                return $this->redirectToRoute("app_dashboard_settings_password");
            }

            $error = $resetPasswordFunction->resetPassword($email);
            if (!$error) {
                $this->addFlash("reset_password_success", "Reset password email sent.");
                return $this->redirectToRoute("app_dashboard_settings_password");
            } else {
                return $this->redirectToRoute("app_dashboard_settings_password");
            } 
        }   
    }

    #[Route("/settings/deleteaccount", name: "app_dashboard_settings_delete_account")]
    public function settingsDeleteAccount(Request $request, VerifyTokenFunction $verifyTokenFunction, DeleteUserAccountFunction $deleteUserAccountFunction): Response
    {

        if ($request->getMethod() === "GET") {
            $userData = $verifyTokenFunction->verifyToken();

            return $this->render("dashboard/settings/delete_account.html.twig");
        } else if ($request->getMethod() === "POST") {
            $token = $request->request->get("token");
            if (!$this->isCsrfTokenValid("deleteUserAccount", $token)) {
                return $this->redirectToRoute("app_dashboard_settings_delete_account");
            }

            $error = $deleteUserAccountFunction->deleteUserAccount();

            return $this->redirectToRoute("app_dashboard_settings_delete_account");
        }   
    }

}
