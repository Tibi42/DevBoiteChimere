<?php

namespace App\Controller\Admin;

use App\Entity\Activity;
use App\Entity\Inscription;
use App\Form\InscriptionType;
use App\Repository\ActivityRepository;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ActivityRegisterController extends AbstractController
{
    #[Route('/activite/{id}/inscrire', name: 'app_activity_register', requirements: ['id' => '\d+'])]
    public function register(
        int $id,
        Request $request,
        ActivityRepository $activityRepository,
        InscriptionRepository $inscriptionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $activity = $activityRepository->find($id);
        if (!$activity) {
            throw $this->createNotFoundException('Cet événement n\'existe pas.');
        }

        if ($activity->getStartAt() < new \DateTimeImmutable()) {
            $this->addFlash('warning', 'Les inscriptions pour cet événement sont fermées (date dépassée).');
            return $this->redirectToRoute('app_home');
        }

        $inscription = new Inscription();
        $inscription->setActivity($activity);

        $form = $this->createForm(InscriptionType::class, $inscription);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($inscriptionRepository->hasAlreadyRegistered($activity->getId(), $inscription->getParticipantEmail())) {
                $this->addFlash('warning', 'Vous êtes déjà inscrit à cet événement avec cette adresse email.');
                return $this->redirectToRoute('app_activity_register', ['id' => $id]);
            }

            $entityManager->persist($inscription);
            $entityManager->flush();

            $this->addFlash('success', 'Votre inscription a bien été enregistrée. À bientôt !');
            return $this->redirectToRoute('app_home', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/activity_register/register.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }
}
