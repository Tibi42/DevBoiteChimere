<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SocietesController extends AbstractController
{
    #[Route('/societes', name: 'app_societes')]
    public function index(): Response
    {
        return $this->render('societes/index.html.twig', [
            'controller_name' => 'SocietesController',
        ]);
    }
}
