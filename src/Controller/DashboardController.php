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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardController extends AbstractController
{
    #[Route("/dashboard", name: "app_dashboard")]
    public function index(Request $request): Response
    {
        if (!$request->cookies->get("token")) {
            return $this->redirectToRoute("app_login");
        } else {
            return $this->redirectToRoute("app_dashboard_transcriptions");
        }
    }

    #[Route("/dashboard/transcriptions", name: "app_dashboard_transcriptions")]
    public function transcriptions(Request $request): Response
    {
        if (!$request->cookies->get("token")) {
            return $this->redirectToRoute("app_login");
        } else {
            return $this->render("dashboard/transcriptions/index.html.twig");
        }
    }

    #[Route("/dashboard/settings", name: "app_dashboard_settings")]
    public function settings(Request $request): Response
    {
        if (!$request->cookies->get("token")) {
            
        } else {
            return $this->redirectToRoute("app_dashboard_settings_profile");
        }
    }

    #[Route("/dashboard/settings/profile", name: "app_dashboard_settings_profile")]
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

    #[Route("/dashboard/settings/email", name: "app_dashboard_settings_email")]
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

    #[Route("/dashboard/settings/password", name: "app_dashboard_settings_password")]
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

    #[Route("/dashboard/settings/deleteaccount", name: "app_dashboard_settings_delete_account")]
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
            if (!$error) {
                return $this->redirectToRoute("app_dashboard_settings_delete_account");
            } else {
                return $this->redirectToRoute("app_dashboard_settings_delete_account");
            } 
        }   
    }

}
