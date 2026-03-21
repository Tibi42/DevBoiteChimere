<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NosSoireeBihebController extends AbstractController
{
    #[Route('/nos/soiree/biheb', name: 'app_nos_soiree_biheb')]
    public function index(): Response
    {
        return $this->render('nos_soiree_biheb/index.html.twig', [
            'controller_name' => 'NosSoireeBihebController',
        ]);
    }
}
