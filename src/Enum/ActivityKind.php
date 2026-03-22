<?php

namespace App\Enum;

enum ActivityKind: string
{
    case JDS      = 'JDS';
    case JDR      = 'JDR';
    case GN       = 'GN';
    case JDF      = 'JDF';
    case AG       = 'AG';
    case PlayTest = 'Play Test';

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

    /** @return string[] */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
