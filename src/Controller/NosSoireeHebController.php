<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NosSoireeHebController extends AbstractController
{
    #[Route('/nos/soiree/heb', name: 'app_nos_soiree_heb')]
    public function index(): Response
    {
        return $this->render('nos_soiree_heb/index.html.twig', [
            'controller_name' => 'NosSoireeHebController',
        ]);
    }
}
