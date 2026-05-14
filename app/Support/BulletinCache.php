<?php

namespace App\Support;

use App\Models\Trimestre;
use Illuminate\Support\Facades\Cache;

final class BulletinCache
{
    public static function key(int $classeId, int $anneeScolaireId, string $suffix): string
    {
        return "bulletin:{$classeId}:{$anneeScolaireId}:{$suffix}";
    }

    /**
     * Invalide toutes les entrées de cache bulletins pour une classe et une année
     * (modes annuel, courant, et chaque id de trimestre pour cette année).
     */
    public static function forgetClasseAnnee(int $classeId, int $anneeScolaireId): void
    {
        Cache::forget(self::key($classeId, $anneeScolaireId, 'annual'));
        Cache::forget(self::key($classeId, $anneeScolaireId, 'current'));

        $trimestreIds = Trimestre::query()
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->pluck('id');

        foreach ($trimestreIds as $id) {
            Cache::forget(self::key($classeId, $anneeScolaireId, (string) $id));
        }
    }
}
