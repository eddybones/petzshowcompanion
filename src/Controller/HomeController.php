<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMAIL_VERIFIED')]
class HomeController extends AbstractController {
    #[Route('', name: 'default', methods: ['GET'])]
    public function default(): Response {
        return $this->render('home/index.html.twig');
    }

    #[Route('/home', name: 'home')]
    public function index(): Response {
        return $this->render('home/index.html.twig');
    }
}