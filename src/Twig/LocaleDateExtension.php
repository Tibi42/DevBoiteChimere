<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Extension Twig fournissant le filtre date_fr.
 *
 * Formate une date en français via IntlDateFormatter.
 * Exemple : {{ activity.startAt | date_fr }} → "samedi 12 avril 2025"
 */
class LocaleDateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('date_fr', $this->formatDateFr(...)),
        ];
    }

    /**
     * Formate une date selon un pattern ICU en français.
     *
     * @param \DateTimeInterface $date    La date à formater.
     * @param string             $pattern Pattern ICU (défaut : "EEEE d MMMM yyyy").
     *
     * @return string La date formatée, ou le format de secours "d/m/Y" si IntlDateFormatter échoue.
     */
    public function formatDateFr(\DateTimeInterface $date, string $pattern = 'EEEE d MMMM yyyy'): string
    {
        $fmt = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, null, null, $pattern);

        return $fmt->format($date) ?: $date->format('d/m/Y');
    }
}
