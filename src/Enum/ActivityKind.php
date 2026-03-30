<?php

namespace App\Enum;

/**
 * Énumération des types d'activités de l'association.
 *
 * La valeur (string) est stockée telle quelle en base de données
 * dans le champ Activity::$type.
 */
enum ActivityKind: string
{
    /** Jeux de Société */
    case JDS      = 'JDS';

    /** Jeux de Rôle */
    case JDR      = 'JDR';

    /** Grandeur Nature (LARP) */
    case GN       = 'GN';

    /** Jeux de Figurines */
    case JDF      = 'JDF';

    /** Assemblée Générale (visible uniquement pour les admins dans les formulaires) */
    case AG       = 'AG';

    /** Séance de play test d'un jeu en développement */
    case PlayTest = 'Play Test';

    /**
     * Retourne le libellé lisible du type (utilisé dans les formulaires et l'interface).
     */
    public function label(): string
    {
        return match($this) {
            self::JDS      => 'JDS (Jeux de Société)',
            self::JDR      => 'JDR (Jeux de Rôle)',
            self::GN       => 'GN (Grandeur Nature)',
            self::JDF      => 'JDF (Jeux de Figurines)',
            self::AG       => 'AG (Assemblée Générale)',
            self::PlayTest => 'Play Test',
        };
    }

    /**
     * Retourne la liste de toutes les valeurs de l'enum (utilisé pour la validation
     * des paramètres de filtre dans les contrôleurs).
     *
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
