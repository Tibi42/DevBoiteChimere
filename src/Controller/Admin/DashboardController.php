<?php

namespace App\Controller\Admin;

use App\Entity\NewsletterSubscriber;
use App\Repository\ActivityRepository;
use App\Repository\InscriptionRepository;
use App\Repository\NewsletterSubscriberRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly InscriptionRepository $inscriptionRepository,
        private readonly ActivityRepository $activityRepository,
        private readonly NewsletterSubscriberRepository $newsletterRepository,
    ) {
    }

    public function index(): Response
    {
        $inscriptionsTotal = $this->inscriptionRepository->countAll();
        $latestInscriptions = $this->inscriptionRepository->findLatestWithActivity(10);
        $pendingActivitiesCount = $this->activityRepository->countPendingApproval();

        $now = new \DateTimeImmutable();
        $monthStart = $now->modify('first day of this month')->setTime(0, 0, 0);
        $nextMonthStart = $monthStart->modify('+1 month');
        $topProposers = $this->activityRepository->findTopProposersBetween($monthStart, $nextMonthStart, 5);

        $newsletterConfirmed = $this->newsletterRepository->countByStatus(NewsletterSubscriber::STATUS_CONFIRMED);
        $newsletterPending = $this->newsletterRepository->countByStatus(NewsletterSubscriber::STATUS_PENDING);

        return $this->render('admin/dashboard.html.twig', [
            'inscriptionsTotal' => $inscriptionsTotal,
            'latestInscriptions' => $latestInscriptions,
            'topProposers' => $topProposers,
            'pendingActivitiesCount' => $pendingActivitiesCount,
            'newsletterConfirmed' => $newsletterConfirmed,
            'newsletterPending' => $newsletterPending,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('La Boîte à Chimère');
    }

    public function configureMenuItems(): iterable
    {
        $pendingActivitiesCount = $this->activityRepository->countPendingApproval();
        $activitiesLabel = sprintf('Activités (%d en attente d\'approbation)', $pendingActivitiesCount);

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToRoute($activitiesLabel, 'fa fa-calendar', 'app_activity_index');
        yield MenuItem::linkToRoute('Carousel', 'fa fa-images', 'app_admin_carousel_index');
        yield MenuItem::linkToRoute('Utilisateurs', 'fa fa-users', 'app_admin_user_index');
        yield MenuItem::linkToRoute('Newsletter', 'fa fa-envelope', 'app_admin_newsletter_index');
    }
}
