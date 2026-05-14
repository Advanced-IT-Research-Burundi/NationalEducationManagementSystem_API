<?php

namespace App\Observers;

use App\Models\NoteConduite;
use App\Support\BulletinCache;

class BulletinCacheObserver
{
    public function saved(NoteConduite $noteConduite): void
    {
        $this->invalidate($noteConduite);
    }

    public function deleted(NoteConduite $noteConduite): void
    {
        $this->invalidate($noteConduite);
    }

    private function invalidate(NoteConduite $noteConduite): void
    {
        $classeId = $noteConduite->classe_id;
        $anneeScolaireId = $noteConduite->annee_scolaire_id;

        if ($classeId && $anneeScolaireId) {
            BulletinCache::forgetClasseAnnee((int) $classeId, (int) $anneeScolaireId);
        }
    }
}
