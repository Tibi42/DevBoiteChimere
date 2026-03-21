<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class NosSoireeMensuelleController extends AbstractController
{
    #[Route('/nos/soiree/mensuelle', name: 'app_nos_soiree_mensuelle')]
    public function index(): Response
    {
        return $this->render('nos_soiree_mensuelle/index.html.twig', [
            'controller_name' => 'NosSoireeMensuelleController',
        ]);
    }
}
