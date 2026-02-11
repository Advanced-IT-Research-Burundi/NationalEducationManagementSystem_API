<?php

namespace App\Enums;

enum CampagneStatut: string
{
    case Planifiee = 'planifiee';
    case Ouverte = 'ouverte';
    case Cloturee = 'cloturee';

    public function label(): string
    {
        return match ($this) {
            self::Planifiee => 'Planifiée',
            self::Ouverte => 'Ouverte',
            self::Cloturee => 'Clôturée',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Planifiee => 'secondary',
            self::Ouverte => 'success',
            self::Cloturee => 'muted',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
