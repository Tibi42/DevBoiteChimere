<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

/**
 * Gestionnaire d'échec d'authentification.
 *
 * Redirige l'utilisateur vers la page d'accueil avec le paramètre ?open=login
 * pour rouvrir automatiquement la modale de connexion, et stocke l'exception
 * en session pour afficher le message d'erreur dans le formulaire.
 */
class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): RedirectResponse
    {
        $url = $this->urlGenerator->generate('app_home', ['open' => 'login']);
        $request->getSession()->set('_security.last_error', $exception);
        return new RedirectResponse($url);
    }
}
