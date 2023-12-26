<?php

namespace App\Controller;

use App\FirebaseFunctions\RegisterFunction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class RegisterController extends AbstractController
{
    #[Route("/register", name: "app_register")]
    public function index(Request $request, RegisterFunction $registerFunction, ValidatorInterface $validator): Response
    {
        if ($request->getMethod() === "GET") {
            return $this->render("register/index.html.twig");
        } else if ($request->getMethod() === "POST") {
            $token = $request->request->get("token");
            if (!$this->isCsrfTokenValid("register", $token)) {
                return $this->redirectToRoute("app_register");
            }

            $firstname = $request->request->get("firstname");
            $lastname = $request->request->get("lastname");
            $email = $request->request->get("email");
            $password = $request->request->get("password");

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

            $emailConstraint = new Assert\Email();
            $emailConstraint->message = "Email address is not valid.";

            $passwordConstraint = new Assert\Length(
                [
                "min" => 6,
                "minMessage" => "Password must be at least {{ limit }} characters long.",
                ]
            );

            $errors = $validator->validate($firstname, [$firstnameConstraint, $firstnameRegexConstraint]);
            $errors = $validator->validate($lastname, [$lastnameConstraint, $lastnameRegexConstraint]);
            $errors = $validator->validate($email, $emailConstraint);
            $errors = $validator->validate($password, $passwordConstraint);

            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash("register_error", $error->getMessage());
                }
                return $this->redirectToRoute("app_register");
            }

            $error = $registerFunction->register($firstname, $lastname, $email, $password);

            if (!$error) {
                return $this->render("register/done.html.twig");
            } else {
                return $this->redirectToRoute("app_register");
            }
        }
    }
}
