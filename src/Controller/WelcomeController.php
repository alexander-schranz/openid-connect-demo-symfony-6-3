<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WelcomeController extends AbstractController
{
    #[Route(path: '/', name: 'app_welcome')]
    public function login(): Response
    {
        return $this->render('security/welcome.html.twig');
    }
}
