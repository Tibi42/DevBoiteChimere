<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NouvellesController extends AbstractController
{
    #[Route('/nouvelles', name: 'app_nouvelles')]
    public function index(): Response
    {
        return $this->render('nouvelles/index.html.twig', [
            'controller_name' => 'NouvellesController',
        ]);
    }
}
