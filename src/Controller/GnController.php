<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class GnController extends AbstractController
{
    #[Route('/gn', name: 'app_gn')]
    public function index(): Response
    {
        return $this->render('gn/index.html.twig', [
            'controller_name' => 'GnController',
        ]);
    }
}
