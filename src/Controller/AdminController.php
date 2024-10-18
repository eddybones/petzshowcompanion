<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController {
    #[Route('/admin{react}', name: 'admin_home', requirements: ['react' => '.+'], defaults: ['react' => null], methods: ['GET'])]
    public function admin(): Response {
        throw new Exception("Blah");
        return $this->render('admin/index.html.twig');
    }
}