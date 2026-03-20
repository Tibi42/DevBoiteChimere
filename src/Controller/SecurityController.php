<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    /**
     * GET /login : redirige vers l'accueil (la modale de connexion est sur la home).
     * POST /login est géré par le firewall (form_login).
     */
    #[Route('/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        return $this->redirectToRoute('app_home', ['open' => 'login']);
    }

    /**
     * Cette méthode ne sera jamais exécutée : le firewall intercepte /logout.
     */
    #[Route('/logout', name: 'app_logout', methods: ['GET'])]
    public function logout(): never
    {
        throw new \LogicException('Cette route est interceptée par le firewall Symfony.');
    }
}
