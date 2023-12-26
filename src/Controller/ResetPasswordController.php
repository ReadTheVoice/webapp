<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\FirebaseFunctions\ResetPasswordFunction;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ResetPasswordController extends AbstractController
{
    #[Route("/resetpassword", name: "app_reset_password")]
    public function index(Request $request, ResetPasswordFunction $resetPasswordFunction, ValidatorInterface $validator ): Response
    {
        if ($request->getMethod() === "GET") {
            return $this->render("reset_password/index.html.twig");
        } else if ($request->getMethod() === "POST") {
            $token = $request->request->get("token");
            if (!$this->isCsrfTokenValid("reset", $token)) {
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
                    $this->addFlash("app_reset_password", $error->getMessage());
                }
                return $this->redirectToRoute("app_reset_password");
            }

            $error = $resetPasswordFunction->resetPassword($email);
            if (!$error) {
                return $this->render("reset_password/done.html.twig");
            } else {
                return $this->redirectToRoute("app_reset_password");
            }    
        
        }
    }
}
