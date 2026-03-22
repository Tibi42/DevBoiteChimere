<?php

namespace App\Controller\Admin;

use App\Entity\Activity;
use App\Entity\User;
use App\Form\ActivityType;
use App\Repository\ActivityRepository;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/activites', name: 'app_activity_')]
class ActivityController extends AbstractController
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
        private readonly InscriptionRepository $inscriptionRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $status = $request->query->get('status');
        if ($status !== null && !in_array($status, [Activity::STATUS_PUBLISHED, Activity::STATUS_PENDING], true)) {
            $status = null;
        }

        $allowedTypes = ['JDS', 'JDR', 'GN', 'JDF', 'AG', 'Play Test'];
        $filterType = $request->query->get('type');
        if ($filterType !== null && !in_array($filterType, $allowedTypes, true)) {
            $filterType = null;
        }

        $allowedLocations = ['L\'auberge de jeunesse Yves Robert', 'Le Natema'];
        $filterLocation = $request->query->get('location');
        if ($filterLocation !== null && !in_array($filterLocation, $allowedLocations, true)) {
            $filterLocation = null;
        }

        $activities = $this->activityRepository->findAllOrderByStartDesc($status, $filterType, $filterLocation);
        $inscriptionCounts = $this->inscriptionRepository->countByActivity();

        return $this->render('admin/activity/index.html.twig', [
            'activities' => $activities,
            'inscriptionCounts' => $inscriptionCounts,
            'currentStatus' => $status,
            'currentType' => $filterType,
            'currentLocation' => $filterLocation,
        ]);
    }

    #[Route('/export-csv', name: 'export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): StreamedResponse
    {
        $status = $request->query->get('status');
        if ($status !== null && !in_array($status, [Activity::STATUS_PUBLISHED, Activity::STATUS_PENDING], true)) {
            $status = null;
        }

        $allowedTypes = ['JDS', 'JDR', 'GN', 'JDF', 'AG', 'Play Test'];
        $filterType = $request->query->get('type');
        if ($filterType !== null && !in_array($filterType, $allowedTypes, true)) {
            $filterType = null;
        }

        $allowedLocations = ['L\'auberge de jeunesse Yves Robert', 'Le Natema'];
        $filterLocation = $request->query->get('location');
        if ($filterLocation !== null && !in_array($filterLocation, $allowedLocations, true)) {
            $filterLocation = null;
        }

        $activities = $this->activityRepository->findAllOrderByStartDesc($status, $filterType, $filterLocation);

        $response = new StreamedResponse(function () use ($activities) {
            $handle = fopen('php://output', 'w');
            // BOM UTF-8 pour Excel
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['Date', 'Titre', 'Type', 'Lieu', 'Statut', 'Proposé par', 'Max participants'], ';');

            foreach ($activities as $activity) {
                fputcsv($handle, [
                    $activity->getStartAt()->format('d/m/Y'),
                    $activity->getTitle(),
                    $activity->getType() ?? '',
                    $activity->getLocation() ?? '',
                    $activity->getStatus() === Activity::STATUS_PUBLISHED ? 'Publiée' : 'En attente',
                    $activity->getProposedBy()?->getEmail() ?? '',
                    $activity->getMaxParticipants() ?? '',
                ], ';');
            }

            fclose($handle);
        });

        $filename = 'activites_' . date('Y-m-d') . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    #[Route('/nouvelle', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $activity = new Activity();
        $form = $this->createForm(ActivityType::class, $activity, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            if ($user instanceof User) {
                $activity->setCreatedBy($user);
            }
            $this->entityManager->persist($activity);
            $this->entityManager->flush();
            $this->addFlash('success', 'L\'activité « ' . $activity->getTitle() . ' » a été créée.');

            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/activity/new.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Activity $activity): Response
    {
        $form = $this->createForm(ActivityType::class, $activity, ['is_admin' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();
            $this->addFlash('success', 'L\'activité « ' . $activity->getTitle() . ' » a été mise à jour.');

            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/activity/edit.html.twig', [
            'activity' => $activity,
            'form' => $form,
        ], new Response(null, $form->isSubmitted() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK));
    }

    #[Route('/{id}/inscrits', name: 'inscrits', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function inscrits(Activity $activity): Response
    {
        $inscriptions = $this->inscriptionRepository->findByActivity($activity);

        return $this->render('admin/activity/inscrits.html.twig', [
            'activity' => $activity,
            'inscriptions' => $inscriptions,
        ]);
    }

    #[Route('/{id}/approuver', name: 'approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function approve(Request $request, Activity $activity): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('approve' . $activity->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        $activity->setStatus(Activity::STATUS_PUBLISHED);
        $this->entityManager->flush();
        $this->addFlash('success', 'L\'activité « ' . $activity->getTitle() . ' » a été approuvée et est maintenant visible.');

        return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/rejeter', name: 'reject', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function reject(Request $request, Activity $activity): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('reject' . $activity->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        $title = $activity->getTitle();
        $this->entityManager->remove($activity);
        $this->entityManager->flush();
        $this->addFlash('success', 'La proposition « ' . $title . ' » a été rejetée et supprimée.');

        return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Activity $activity): Response
    {
        if ($activity->getStartAt() <= new \DateTimeImmutable()) {
            $this->addFlash('error', 'Impossible de supprimer une activité passée.');
            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete' . $activity->getId(), $token)) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
        }

        $title = $activity->getTitle();
        $this->entityManager->remove($activity);
        $this->entityManager->flush();
        $this->addFlash('success', 'L\'activité « ' . $title . ' » a été supprimée.');

        return $this->redirectToRoute('app_activity_index', [], Response::HTTP_SEE_OTHER);
    }
}
