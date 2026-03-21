<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JdsController extends AbstractController
{
    #[Route('/jds', name: 'app_jds')]
    public function index(): Response
    {
        return $this->render('jds/index.html.twig', [
            'controller_name' => 'JdsController',
        ]);
    }
}
