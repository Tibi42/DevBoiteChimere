<?php

namespace App\Controller;

use App\Entity\Activity;
use App\Entity\Inscription;
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
            $publishNow = $this->isGranted('ROLE_ADMIN') && $request->request->get('publish_now');
            $activity->setStatus($publishNow ? Activity::STATUS_PUBLISHED : Activity::STATUS_PENDING);
            $activity->setProposedBy($this->getUser());
            $this->addFlash('success', $publishNow
                ? 'L\'événement « ' . $activity->getTitle() . ' » a été créé et publié.'
                : 'Votre proposition « ' . $activity->getTitle() . ' » a été envoyée et sera examinée par un administrateur.'
            );

            $this->entityManager->persist($activity);

            $user = $this->getUser();
            $inscription = new Inscription();
            $inscription->setActivity($activity);
            $inscription->setParticipantName($user->getUsername());
            $inscription->setParticipantEmail($user->getEmail());
            $this->entityManager->persist($inscription);

            $this->entityManager->flush();

            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
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
