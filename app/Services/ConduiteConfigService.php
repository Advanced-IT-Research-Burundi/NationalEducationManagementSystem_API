<?php

namespace App\Services;

use App\Models\Classe;

class ConduiteConfigService
{
    public const CONDUITE_MAX_PRIMARY = 10;

    public const CONDUITE_MAX_SECONDARY = 60;

    public const ORDRE_CUTOFF = 10;

    /**
     * @return array{max_note: int, thresholds: array<string, int>}
     */
    public static function resolveForClasse(int|Classe $classe): array
    {
        if (is_int($classe)) {
            $classe = Classe::with('niveau')->findOrFail($classe);
        } elseif (! $classe->relationLoaded('niveau')) {
            $classe->load('niveau');
        }

        $maxNote = self::getMaxNote($classe);

        return [
            'max_note' => $maxNote,
            'thresholds' => self::getThresholds($maxNote),
        ];
    }

    public static function getMaxNote(Classe $classe): int
    {
        if (! $classe->relationLoaded('niveau')) {
            $classe->load('niveau');
        }

        $ordre = $classe->niveau?->ordre ?? 0;

        return $ordre >= self::ORDRE_CUTOFF
            ? self::CONDUITE_MAX_SECONDARY
            : self::CONDUITE_MAX_PRIMARY;
    }

    public static function isSecondary(Classe $classe): bool
    {
        if (! $classe->relationLoaded('niveau')) {
            $classe->load('niveau');
        }

        return ($classe->niveau?->ordre ?? 0) >= self::ORDRE_CUTOFF;
    }

    /**
     * @return array<string, int>
     */
    public static function getThresholds(int $maxNote): array
    {
        if ($maxNote === self::CONDUITE_MAX_SECONDARY) {
            return [
                'Excellent' => 50,
                'Bon' => 40,
                'Passable' => 30,
                'Mauvais' => 20,
            ];
        }

        return [
            'Excellent' => 8,
            'Bon' => 7,
            'Passable' => 5,
            'Mauvais' => 3,
        ];
    }

    public static function buildAppreciation(float|int $noteValue, int $maxNote): string
    {
        $thresholds = self::getThresholds($maxNote);

        foreach ($thresholds as $label => $threshold) {
            if ($noteValue >= $threshold) {
                return $label;
            }
        }

        return 'Très mauvais';
    }
}
