<?php

namespace App\Enums;

enum StatutAcademique: string
{
    case EnCours = 'en_cours';
    case Admis = 'admis';
    case Redouble = 'redouble';
    case Transfere = 'transfere';
    case Abandonne = 'abandonne';
    case Exclu = 'exclu';

    public function label(): string
    {
        return match ($this) {
            self::EnCours => 'En cours',
            self::Admis => 'Admis',
            self::Redouble => 'Redoublant',
            self::Transfere => 'Transféré',
            self::Abandonne => 'Abandonné',
            self::Exclu => 'Exclu',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EnCours => 'info',
            self::Admis => 'success',
            self::Redouble => 'warning',
            self::Transfere => 'secondary',
            self::Abandonne => 'danger',
            self::Exclu => 'danger',
        };
    }

    public function isFinal(): bool
    {
        return $this !== self::EnCours;
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
