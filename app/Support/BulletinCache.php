<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

final class BulletinCache
{
    public const DATA_TTL_SECONDS = 60;

    private const VERSION_PREFIX = 'bulletin:version';

    public static function versionKey(int $classeId, int $anneeScolaireId): string
    {
        return self::VERSION_PREFIX . ":{$classeId}:{$anneeScolaireId}";
    }

    /**
     * Version de cache pour une classe / année (incrémentée à chaque invalidation).
     */
    public static function version(int $classeId, int $anneeScolaireId): int
    {
        return (int) Cache::get(self::versionKey($classeId, $anneeScolaireId), 1);
    }

    /**
     * Clé utilisée par BulletinController::generate / preparePrintableBulletin.
     *
     * @param  list<string>  $displayTrimestres
     */
    public static function dataKey(
        int $classeId,
        int $anneeScolaireId,
        string $cachePeriod,
        string $layoutSignature,
        array $displayTrimestres
    ): string {
        $version = self::version($classeId, $anneeScolaireId);

        return sprintf(
            'bulletin:%d:%d:v%d:%s:%s:%s',
            $classeId,
            $anneeScolaireId,
            $version,
            $cachePeriod,
            $layoutSignature,
            implode(',', $displayTrimestres)
        );
    }

    /**
     * Invalide toutes les entrées bulletin pour une classe et une année
     * (tous modes, trimestres et signatures de mise en page).
     */
    public static function forgetClasseAnnee(int $classeId, int $anneeScolaireId): void
    {
        $versionKey = self::versionKey($classeId, $anneeScolaireId);
        $current = (int) Cache::get($versionKey, 1);
        Cache::forever($versionKey, $current + 1);
    }
}