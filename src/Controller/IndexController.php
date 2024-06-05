<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;


class IndexController extends AbstractController
{
    #[Route("/", name: "app_index")]
    public function index(Request $request): Response
    {
        if (!$request->getSession()->get("jwtToken")) {
            return $this->redirectToRoute("app_login");
        } else {
            return $this->redirectToRoute("app_dashboard");
        }
    }

    #[Route("/diapo", name: "app_redirect_diapo")]
    public function diapo(Request $request): Response
    {
        return new RedirectResponse("https://www.canva.com/design/DAGHB6MNr4Y/NTH6i_qz2eWRfSciBBAJ1g/edit");
    }
}
