<?php

namespace App\Controller;

use App\FirebaseFunctions\LoginFunction;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginController extends AbstractController
{
    #[Route("/login", name: "app_login")]
    public function index(Request $request, LoginFunction $loginFunction, ValidatorInterface $validator): Response
    {
        if ($request->getMethod() === "GET") {
            return $this->render("login/index.html.twig");
        }

        $token = $request->request->get("token");
        if (!$this->isCsrfTokenValid("login", $token)) {
            return $this->redirectToRoute("app_login");
        }

        $email = $request->request->get("email");
        $password = $request->request->get("password");
        $rememberMe = $request->request->get("remember_me", false);

        $emailConstraint = new Assert\Email(["message" => "Email address not valid."]);
        $errors = $validator->validate($email, $emailConstraint);

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash("login_error", $error->getMessage());
            }
            return $this->redirectToRoute("app_login");
        }

        $response = $loginFunction->logIn($email, $password, $rememberMe);
        if ($response instanceof Response) {
            $response->sendHeaders();
            return $this->redirectToRoute("app_dashboard");
        }
        return $this->redirectToRoute("app_login", ["email" => $email, "remember_me" => $rememberMe]);
    }
}
