<?php

namespace App\Traits;

use App\Models\AnneeScolaire;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait EnsuresActiveAcademicYear
{
    /**
     * Ensure the given school year is the currently active one.
     * Throws 403 if the year is not active (data is read-only).
     */
    protected function ensureActiveYear(int $anneeScolaireId): void
    {
        $active = AnneeScolaire::current();

        if (! $active || $active->id !== $anneeScolaireId) {
            throw new AccessDeniedHttpException(
                'Modification interdite : cette année scolaire n\'est plus active. Les données des années précédentes sont en lecture seule.'
            );
        }
    }

    /**
     * Check if a school year is the active one (non-throwing version).
     */
    protected function isActiveYear(int $anneeScolaireId): bool
    {
        $active = AnneeScolaire::current();

        return $active && $active->id === $anneeScolaireId;
    }
}
