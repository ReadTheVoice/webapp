<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class IndexController extends AbstractController
{
    #[Route("/", name: "app_index")]
    public function index(Request $request): Response
    {
        if (!$request->cookies->get("token")) {
            return $this->redirectToRoute("app_login");
        } else {
            return $this->redirectToRoute("app_dashboard");
        }
    }
}
