<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route('/login', name: 'app_login', methods: ['GET'])]
class SecurityController extends AbstractController
{
    /**
     * GET /login : redirige vers l'accueil (la modale de connexion est sur la home).
     * POST /login est géré par le firewall (form_login).
     */
    public function __invoke(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        return $this->redirectToRoute('app_home', ['open' => 'login']);
    }
}
