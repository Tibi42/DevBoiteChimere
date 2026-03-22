<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Repository\ActivityRepository;
use App\Repository\InscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class UserDashboardController extends AbstractController
{
    #[Route('/mon-espace', name: 'app_user_dashboard')]
    public function index(InscriptionRepository $inscriptionRepository, ActivityRepository $activityRepository): Response
    {
        $user = $this->getUser();
        $email = $user->getEmail();

        $username = $user->getUsername();

        // Inscriptions de l'utilisateur (par email OU par nom)
        $inscriptions = $inscriptionRepository->createQueryBuilder('i')
            ->leftJoin('i.activity', 'a')
            ->addSelect('a')
            ->where('i.participantEmail = :email OR i.participantName = :username')
            ->setParameter('email', $email)
            ->setParameter('username', $username)
            ->orderBy('a.startAt', 'DESC')
            ->getQuery()
            ->getResult();

        // Séparer inscriptions à venir / passées
        $now = new \DateTimeImmutable();
        $upcoming = [];
        $past = [];
        foreach ($inscriptions as $inscription) {
            $activity = $inscription->getActivity();
            if ($activity && $activity->getStartAt() >= $now) {
                $upcoming[] = $inscription;
            } else {
                $past[] = $inscription;
            }
        }

        return $this->render('user_dashboard/index.html.twig', [
            'upcoming' => $upcoming,
            'past' => $past,
        ]);
    }

    #[Route('/mon-espace/changer-mot-de-passe', name: 'app_user_change_password', methods: ['POST'])]
    public function changePassword(Request $request, EntityManagerInterface $em, \Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface $passwordHasher): Response
    {
        if (!$this->isCsrfTokenValid('change_password', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        $user = $this->getUser();
        $currentPassword = $request->request->get('current_password', '');
        $newPassword = $request->request->get('new_password', '');
        $confirmPassword = $request->request->get('confirm_password', '');

        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            $this->addFlash('error', 'Le mot de passe actuel est incorrect.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        if (strlen($newPassword) < 6) {
            $this->addFlash('error', 'Le nouveau mot de passe doit contenir au moins 6 caractères.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        if ($newPassword !== $confirmPassword) {
            $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
        $em->flush();

        $this->addFlash('success', 'Votre mot de passe a été mis à jour.');

        return $this->redirectToRoute('app_user_dashboard');
    }

    #[Route('/mon-espace/changer-email', name: 'app_user_change_email', methods: ['POST'])]
    public function changeEmail(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('change_email', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        $newEmail = trim($request->request->get('new_email', ''));
        if (!$newEmail || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Veuillez saisir une adresse email valide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        $user = $this->getUser();

        if ($newEmail === $user->getEmail()) {
            $this->addFlash('warning', 'C\'est déjà votre adresse email actuelle.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        // Vérifier qu'aucun autre utilisateur n'utilise cet email
        $existing = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => $newEmail]);
        if ($existing) {
            $this->addFlash('error', 'Cette adresse email est déjà utilisée par un autre compte.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        $user->setEmail($newEmail);
        $em->flush();

        $this->addFlash('success', 'Votre adresse email a été mise à jour.');

        return $this->redirectToRoute('app_user_dashboard');
    }

    #[Route('/mon-espace/desinscription/{id}', name: 'app_user_unregister', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function unregister(Inscription $inscription, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('unregister' . $inscription->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        $user = $this->getUser();
        // Vérifier que l'inscription appartient bien à cet utilisateur
        if ($inscription->getParticipantEmail() !== $user->getEmail() && $inscription->getParticipantName() !== $user->getUsername()) {
            $this->addFlash('error', 'Cette inscription ne vous appartient pas.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        $activityTitle = $inscription->getActivity()?->getTitle() ?? 'activité';
        $em->remove($inscription);
        $em->flush();

        $this->addFlash('success', 'Vous êtes désinscrit de « ' . $activityTitle . ' ».');

        return $this->redirectToRoute('app_user_dashboard');
    }

    #[Route('/mon-espace/supprimer', name: 'app_user_delete_account', methods: ['POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Les administrateurs ne peuvent pas supprimer leur compte depuis cette page.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        if (!$this->isCsrfTokenValid('delete_my_account', $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide.');
            return $this->redirectToRoute('app_user_dashboard');
        }

        $user = $this->getUser();

        // Invalider la session avant suppression
        $request->getSession()->invalidate();
        $this->container->get('security.token_storage')->setToken(null);

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Votre compte a été supprimé.');

        return $this->redirectToRoute('app_home');
    }
}
