<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $email = $request->request->get('_username', '');

        // Si l'email n'existe pas en base → ouvrir la modale d'inscription
        if ($email && !$this->userRepository->findOneBy(['email' => $email])) {
            $url = $this->urlGenerator->generate('app_home', [
                'open' => 'register',
                'email' => $email,
            ]);
            return new RedirectResponse($url);
        }

        // Sinon → comportement normal (mauvais mot de passe)
        $url = $this->urlGenerator->generate('app_home', ['open' => 'login']);
        $request->getSession()->set('_security.last_error', $exception);
        return new RedirectResponse($url);
    }
}
