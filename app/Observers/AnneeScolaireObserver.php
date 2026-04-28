<?php

namespace App\Observers;

use App\Models\AnneeScolaire;
use App\Services\AcademicYearService;

class AnneeScolaireObserver
{
    public function updated(AnneeScolaire $anneeScolaire): void
    {
        if ($anneeScolaire->isDirty('est_active')) {
            AcademicYearService::clearCache();
        }
    }

    public function deleted(AnneeScolaire $anneeScolaire): void
    {
        if ($anneeScolaire->est_active) {
            AcademicYearService::clearCache();
        }
    }
}
