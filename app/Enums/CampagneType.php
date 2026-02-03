<?php

namespace App\Enums;

enum CampagneType: string
{
    case Nouvelle = 'nouvelle';
    case Reinscription = 'reinscription';

    public function label(): string
    {
        return match ($this) {
            self::Nouvelle => 'Nouvelle inscription',
            self::Reinscription => 'Réinscription',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
