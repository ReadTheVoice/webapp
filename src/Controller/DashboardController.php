<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends AbstractController
{
    #[Route("/dashboard", name: "app_dashboard")]
    public function index(Request $request): Response
    {
        if (!$request->cookies->get("token")) {
            return $this->redirectToRoute("app_login");
        } else {
            return $this->render("dashboard/index.html.twig");
        }
    }
}
