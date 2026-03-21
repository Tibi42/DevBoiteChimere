<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class JdrController extends AbstractController
{
    #[Route('/jdr', name: 'app_jdr')]
    public function index(): Response
    {
        return $this->render('jdr/index.html.twig', [
            'controller_name' => 'JdrController',
        ]);
    }
}
