<?php

namespace App\Observers;

use App\Models\Note;
use App\Support\BulletinCache;

class NoteCacheObserver
{
    public function saved(Note $note): void
    {
        $this->invalidate($note);
    }

    public function deleted(Note $note): void
    {
        $this->invalidate($note);
    }

    private function invalidate(Note $note): void
    {
        $evaluation = $note->evaluation;

        if (! $evaluation) {
            return;
        }

        $classeId = $evaluation->classe_id;
        $anneeScolaireId = $evaluation->annee_scolaire_id;

        if ($classeId && $anneeScolaireId) {
            BulletinCache::forgetClasseAnnee((int) $classeId, (int) $anneeScolaireId);
        }
    }
}
