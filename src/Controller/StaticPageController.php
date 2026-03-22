<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class StaticPageController extends AbstractController
{
    #[Route('/jds', name: 'app_jds')]
    public function jds(): Response { return $this->render('jds/index.html.twig'); }

    #[Route('/jdr', name: 'app_jdr')]
    public function jdr(): Response { return $this->render('jdr/index.html.twig'); }

    #[Route('/gn', name: 'app_gn')]
    public function gn(): Response { return $this->render('gn/index.html.twig'); }

    #[Route('/association', name: 'app_association')]
    public function association(): Response { return $this->render('association/index.html.twig'); }

    #[Route('/nouvelles', name: 'app_nouvelles')]
    public function nouvelles(): Response { return $this->render('nouvelles/index.html.twig'); }

    #[Route('/qui-sommes-nous', name: 'app_qui_sommes_nous')]
    public function quiSommesNous(): Response { return $this->render('qui_sommes_nous/index.html.twig'); }

    #[Route('/societes', name: 'app_societes')]
    public function societes(): Response { return $this->render('societes/index.html.twig'); }

    #[Route('/evenements', name: 'app_evenements')]
    public function evenements(): Response { return $this->render('evenements/index.html.twig'); }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response { return $this->render('mentions_legales/index.html.twig'); }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response { return $this->render('contact/index.html.twig'); }

    #[Route('/nos/soiree/heb', name: 'app_nos_soiree_heb')]
    public function nosSoireeHeb(): Response { return $this->render('nos_soiree_heb/index.html.twig'); }

    #[Route('/nos/soiree/biheb', name: 'app_nos_soiree_biheb')]
    public function nosSoireeBiheb(): Response { return $this->render('nos_soiree_biheb/index.html.twig'); }

    #[Route('/nos/soiree/mensuelle', name: 'app_nos_soiree_mensuelle')]
    public function nosSoireeMensuelle(): Response { return $this->render('nos_soiree_mensuelle/index.html.twig'); }
}
