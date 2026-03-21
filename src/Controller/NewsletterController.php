<?php

namespace App\Controller;

use App\Entity\NewsletterSubscriber;
use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/newsletter')]
final class NewsletterController extends AbstractController
{
    public function __construct(
        private readonly NewsletterSubscriberRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/subscribe', name: 'app_newsletter_subscribe', methods: ['POST'])]
    public function subscribe(Request $request, MailerInterface $mailer): Response
    {
        $email = trim((string) $request->request->get('email'));

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('newsletter_error', 'Veuillez entrer une adresse email valide.');
            return $this->redirectToReferer($request);
        }

        $existing = $this->repository->findByEmail($email);

        if ($existing) {
            if ($existing->getStatus() === NewsletterSubscriber::STATUS_CONFIRMED) {
                $this->addFlash('newsletter_info', 'Cette adresse est déjà inscrite à notre newsletter.');
                return $this->redirectToReferer($request);
            }

            if ($existing->getStatus() === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
                $existing->setStatus(NewsletterSubscriber::STATUS_PENDING);
                $existing->setUnsubscribedAt(null);
                $existing->regenerateToken();
                $this->entityManager->flush();
                $this->sendConfirmationEmail($mailer, $existing);
                $this->addFlash('newsletter_success', 'Un email de confirmation vous a été envoyé.');
                return $this->redirectToReferer($request);
            }

            // Already pending — resend confirmation
            $existing->regenerateToken();
            $this->entityManager->flush();
            $this->sendConfirmationEmail($mailer, $existing);
            $this->addFlash('newsletter_success', 'Un email de confirmation vous a été renvoyé.');
            return $this->redirectToReferer($request);
        }

        $subscriber = new NewsletterSubscriber();
        $subscriber->setEmail($email);
        $this->entityManager->persist($subscriber);
        $this->entityManager->flush();

        $this->sendConfirmationEmail($mailer, $subscriber);

        $this->addFlash('newsletter_success', 'Merci ! Un email de confirmation vous a été envoyé.');
        return $this->redirectToReferer($request);
    }

    #[Route('/confirm/{token}', name: 'app_newsletter_confirm', methods: ['GET'])]
    public function confirm(string $token): Response
    {
        $subscriber = $this->repository->findByToken($token);

        if (!$subscriber) {
            return $this->render('newsletter/result.html.twig', [
                'title' => 'Lien invalide',
                'message' => 'Ce lien de confirmation est invalide ou a expiré.',
                'success' => false,
            ]);
        }

        if ($subscriber->getStatus() === NewsletterSubscriber::STATUS_CONFIRMED) {
            return $this->render('newsletter/result.html.twig', [
                'title' => 'Déjà confirmé',
                'message' => 'Votre inscription est déjà confirmée.',
                'success' => true,
            ]);
        }

        $subscriber->confirm();
        $this->entityManager->flush();

        return $this->render('newsletter/result.html.twig', [
            'title' => 'Inscription confirmée',
            'message' => 'Votre inscription à la newsletter de La Boîte à Chimère est confirmée. Merci !',
            'success' => true,
        ]);
    }

    #[Route('/unsubscribe/{token}', name: 'app_newsletter_unsubscribe', methods: ['GET'])]
    public function unsubscribe(string $token): Response
    {
        $subscriber = $this->repository->findByToken($token);

        if (!$subscriber) {
            return $this->render('newsletter/result.html.twig', [
                'title' => 'Lien invalide',
                'message' => 'Ce lien de désinscription est invalide ou a expiré.',
                'success' => false,
            ]);
        }

        $subscriber->unsubscribe();
        $this->entityManager->flush();

        return $this->render('newsletter/result.html.twig', [
            'title' => 'Désinscription confirmée',
            'message' => 'Vous avez été désinscrit de la newsletter. Vous ne recevrez plus nos communications.',
            'success' => true,
        ]);
    }

    private function sendConfirmationEmail(MailerInterface $mailer, NewsletterSubscriber $subscriber): void
    {
        $confirmUrl = $this->generateUrl('app_newsletter_confirm', [
            'token' => $subscriber->getToken(),
        ], UrlGeneratorInterface::ABSOLUTE_URL);

        $email = (new TemplatedEmail())
            ->from('newsletter@laboiteachimere.fr')
            ->to($subscriber->getEmail())
            ->subject('Confirmez votre inscription à la newsletter - La Boîte à Chimère')
            ->htmlTemplate('emails/newsletter_confirmation.html.twig')
            ->context([
                'confirmUrl' => $confirmUrl,
            ]);

        $mailer->send($email);
    }

    private function redirectToReferer(Request $request): Response
    {
        $referer = $request->headers->get('referer', $this->generateUrl('app_home'));
        return $this->redirect($referer);
    }
}
