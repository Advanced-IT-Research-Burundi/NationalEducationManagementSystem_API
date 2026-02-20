<?php

namespace App\Enums;

enum StatutMouvement: string
{
    case EnAttente = 'en_attente';
    case Valide = 'valide';
    case Rejete = 'rejete';
    case Annule = 'annule';

    public function label(): string
    {
        return match ($this) {
            self::EnAttente => 'En attente de validation',
            self::Valide => 'Validé',
            self::Rejete => 'Rejeté',
            self::Annule => 'Annulé',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::EnAttente => 'warning',
            self::Valide => 'success',
            self::Rejete => 'danger',
        };
    }

    public function isPending(): bool
    {
        return $this === self::EnAttente;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Valide, self::Rejete]);
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
