<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber ajoutant les en-têtes de sécurité HTTP à chaque réponse principale.
 *
 * En-têtes définis :
 *  - X-Content-Type-Options   : empêche le MIME-sniffing
 *  - X-XSS-Protection         : active le filtre XSS des anciens navigateurs
 *  - Content-Security-Policy  : restreint les sources de scripts, styles, images
 *  - X-Frame-Options           : protection anti-clickjacking
 *  - Referrer-Policy           : contrôle les informations de référent envoyées
 *
 * Note : HSTS (Strict-Transport-Security) est commenté et doit être activé en production HTTPS.
 */
final class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;

        // XSS protection
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-XSS-Protection', '1; mode=block');
        $headers->set('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' data:; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self' https://www.helloasso.com");

        // Clickjacking protection
        $headers->set('X-Frame-Options', 'DENY');

        // Referrer policy
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HTTPS enforcement (uncomment in production with HTTPS)
        // $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }
}
