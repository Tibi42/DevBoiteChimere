<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Form\ActivityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ActivityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/activite/nouvelle', name: 'app_activity_new_public', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request): Response
    {
        $activity = new Activity();

        // Pré-remplir startAt depuis le paramètre GET ?date=Y-m-d
        $dateParam = $request->query->get('date', '');
        $startAt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateParam);
        if (!$startAt) {
            $startAt = new \DateTimeImmutable('today');
        }
        $activity->setStartAt($startAt);

        $form = $this->createForm(ActivityType::class, $activity, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                $activity->setStatus(Activity::STATUS_PUBLISHED);
                $activity->setProposedBy(null);
                $this->addFlash('success', 'L\'activité « ' . $activity->getTitle() . ' » a été créée.');
            } else {
                $activity->setStatus(Activity::STATUS_PENDING);
                $activity->setProposedBy($this->getUser());
                $this->addFlash('success', 'Votre proposition « ' . $activity->getTitle() . ' » a été envoyée et sera examinée par un administrateur.');
            }

            $this->entityManager->persist($activity);
            $this->entityManager->flush();

            return $this->redirectToRoute('app_home');
        }

        // GET : fragment nu pour Turbo Frame
        if ($request->isMethod('GET')) {
            return $this->render('activity/modal_form.html.twig', [
                'form' => $form,
                'date' => $dateParam,
            ]);
        }

        // POST invalide : page complète avec erreurs
        return $this->render('activity/modal_form_page.html.twig', [
            'form' => $form,
        ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
    }
}
