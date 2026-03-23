<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class LocaleDateExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('date_fr', $this->formatDateFr(...)),
        ];
    }

    public function formatDateFr(\DateTimeInterface $date, string $pattern = 'EEEE d MMMM yyyy'): string
    {
        $fmt = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::FULL, \IntlDateFormatter::NONE, null, null, $pattern);

        return $fmt->format($date) ?: $date->format('d/m/Y');
    }
}
