<?php

namespace App\Enums;

enum TypeMouvement: string
{
    case TransfertSortant = 'transfert_sortant';
    case TransfertEntrant = 'transfert_entrant';
    case Abandon = 'abandon';
    case Exclusion = 'exclusion';
    case Deces = 'deces';
    case Passage = 'passage';
    case Redoublement = 'redoublement';
    case Reintegration = 'reintegration';
    case Autre = 'autre';

    public function label(): string
    {
        return match ($this) {
            self::TransfertSortant => 'Transfert sortant',
            self::TransfertEntrant => 'Transfert entrant',
            self::Abandon => 'Abandon scolaire',
            self::Exclusion => 'Exclusion disciplinaire',
            self::Deces => 'Décès',
            self::Passage => 'Passage au niveau supérieur',
            self::Redoublement => 'Redoublement',
            self::Reintegration => 'Réintégration',
            self::Autre => 'Autre',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TransfertSortant => 'warning',
            self::TransfertEntrant => 'info',
            self::Abandon => 'danger',
            self::Exclusion => 'danger',
            self::Deces => 'muted',
            self::Passage => 'success',
            self::Redoublement => 'warning',
            self::Reintegration => 'success',
            self::Autre => 'muted',
        };
    }

    /**
     * Indique si ce type de mouvement nécessite une validation hiérarchique.
     */
    public function requiresValidation(): bool
    {
        return match ($this) {
            self::TransfertSortant, self::TransfertEntrant => true,
            self::Exclusion => true,
            default => false,
        };
    }

    /**
     * Indique si ce type de mouvement affecte le statut global de l'élève.
     */
    public function affectsEleveStatus(): bool
    {
        return match ($this) {
            self::TransfertSortant => true,
            self::Abandon => true,
            self::Exclusion => true,
            self::Deces => true,
            self::Reintegration => true,
            default => false,
        };
    }

    /**
     * Retourne le statut élève résultant de ce mouvement.
     */
    public function resultingEleveStatus(): ?string
    {
        return match ($this) {
            self::TransfertSortant => 'transfere',
            self::Abandon => 'abandonne',
            self::Exclusion => 'inactif',
            self::Deces => 'decede',
            self::Reintegration => 'actif',
            default => null,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Types de mouvement qui terminent la scolarité dans l'école.
     */
    public static function typesFinScolarite(): array
    {
        return [
            self::TransfertSortant,
            self::Abandon,
            self::Exclusion,
            self::Deces,
        ];
    }
}
