<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Contrôleur d'inscription des nouveaux utilisateurs.
 *
 * Traite uniquement les requêtes POST /register soumises depuis la modale
 * d'inscription de la page d'accueil. Après validation et création du
 * compte, un email de bienvenue est envoyé au nouvel utilisateur et une
 * notification est adressée aux administrateurs.
 */
final class RegistrationController extends AbstractController
{
    /**
     * Crée un nouveau compte utilisateur depuis le formulaire d'inscription.
     *
     * Étapes :
     *  1. Validation du jeton CSRF
     *  2. Validation des champs (email, nom d'utilisateur, mot de passe ≥ 12 caractères)
     *  3. Vérification de l'unicité de l'email et du nom d'utilisateur
     *  4. Création et persistance de l'entité User avec mot de passe haché
     *  5. Envoi d'un email de bienvenue + notification admin
     *  6. Connexion automatique du nouvel utilisateur
     */
    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository,
        Security $security,
        MailerInterface $mailer,
    ): Response {
        $email = trim((string) $request->request->get('email'));
        $username = trim((string) $request->request->get('username'));
        $password = (string) $request->request->get('password');
        $csrfToken = (string) $request->request->get('_csrf_token');

        // CSRF validation
        if (!$this->isCsrfTokenValid('register', $csrfToken)) {
            $this->addFlash('register_error', 'Jeton de sécurité invalide. Veuillez réessayer.');
            return $this->redirectToRoute('app_home', ['open' => 'register']);
        }

        // Validation
        $errors = [];
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Veuillez entrer une adresse email valide.';
        }
        if (!$username || mb_strlen($username) < 3 || !preg_match('/^[\w\-.\ ]+$/u', $username)) {
            $errors[] = 'Le nom d\'utilisateur doit faire au moins 3 caractères et ne contenir que des lettres, chiffres, espaces, tirets, points et underscores.';
        }
        if (mb_strlen($password) < 12) {
            $errors[] = 'Le mot de passe doit faire au moins 12 caractères.';
        }

        if ($errors) {
            foreach ($errors as $error) {
                $this->addFlash('register_error', $error);
            }
            return $this->redirectToRoute('app_home', ['open' => 'register', 'email' => $email]);
        }

        // Unicité
        if ($userRepository->findOneBy(['email' => $email])) {
            $this->addFlash('register_error', 'Cette adresse email est déjà utilisée.');
            return $this->redirectToRoute('app_home', ['open' => 'login']);
        }
        if ($userRepository->findOneBy(['username' => $username])) {
            $this->addFlash('register_error', 'Ce nom d\'utilisateur est déjà pris.');
            return $this->redirectToRoute('app_home', ['open' => 'register', 'email' => $email]);
        }

        // Création du compte
        $user = new User();
        $user->setEmail($email);
        $user->setUsername($username);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $entityManager->persist($user);
        $entityManager->flush();

        // Email de bienvenue au nouvel inscrit
        $siteUrl = $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $welcomeEmail = (new TemplatedEmail())
            ->from('noreply@laboiteachimere.fr')
            ->to($user->getEmail())
            ->subject('Bienvenue à La Boîte à Chimère !')
            ->htmlTemplate('emails/registration_welcome.html.twig')
            ->context([
                'username' => $user->getUsername(),
                'userEmail' => $user->getEmail(),
                'siteUrl' => $siteUrl,
            ]);
        $mailer->send($welcomeEmail);

        // Notification aux admins
        $admins = $userRepository->findAdmins();
        if ($admins) {
            $adminUrl = $this->generateUrl('admin', [], UrlGeneratorInterface::ABSOLUTE_URL);
            $adminNotif = (new TemplatedEmail())
                ->from('noreply@laboiteachimere.fr')
                ->subject('Nouvelle inscription : ' . $user->getUsername())
                ->htmlTemplate('emails/registration_admin_notify.html.twig')
                ->context([
                    'username' => $user->getUsername(),
                    'userEmail' => $user->getEmail(),
                    'registeredAt' => new \DateTimeImmutable(),
                    'adminUrl' => $adminUrl,
                ]);
            foreach ($admins as $admin) {
                $adminNotif->addTo($admin->getEmail());
            }
            $mailer->send($adminNotif);
        }

        // Connexion automatique après inscription
        $security->login($user);

        return $this->redirectToRoute('app_home');
    }
}
