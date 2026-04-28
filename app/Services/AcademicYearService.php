<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;

class AcademicYearService
{
    private const CACHE_KEY = 'active_annee_scolaire_id';

    private const CACHE_TTL = 3600;

    /**
     * Get the current academic year ID from Context, Cache, or DB.
     */
    public static function currentId(): ?int
    {
        $fromContext = Context::get('annee_scolaire_id');

        if ($fromContext !== null) {
            return (int) $fromContext;
        }

        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): ?int {
            return AnneeScolaire::withoutGlobalScopes()->active()->value('id');
        });
    }

    /**
     * Set the academic year ID for the current request via Context.
     */
    public static function setCurrent(int $id): void
    {
        Context::add('annee_scolaire_id', $id);
    }

    /**
     * Invalidate the cached active academic year ID.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
