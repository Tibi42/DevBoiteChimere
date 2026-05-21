<?php

namespace App\Controller;

use App\Entity\Article;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ArticleController extends AbstractController
{
    #[Route('/nouvelles/article/{id}', name: 'app_article_show', requirements: ['id' => '\d+'])]
    public function show(Article $article): Response
    {
        if (!$article->isActive()) {
            throw $this->createNotFoundException('Cet article n\'est pas disponible.');
        }

        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }
}
