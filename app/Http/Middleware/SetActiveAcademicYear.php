<?php

namespace App\Http\Middleware;

use App\Models\AnneeScolaire;
use App\Services\AcademicYearService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveAcademicYear
{
    /**
     * Handle an incoming request.
     *
     * Sets the request academic year Context for global scopes and route model binding.
     * Priority matches {@see \App\Traits\ResolvesAnneeScolaire}: query params
     * `annee_scolaire_id` / `annee_scolaire`, then `X-Annee-Scolaire-Id` header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $applyIfValid = static function (int $id): bool {
            if (! AnneeScolaire::withoutGlobalScopes()->where('id', $id)->exists()) {
                return false;
            }
            AcademicYearService::setCurrent($id);

            return true;
        };

        foreach (['annee_scolaire_id', 'annee_scolaire'] as $key) {
            $raw = $request->query($key);
            if ($raw === null || $raw === '' || ! is_numeric($raw)) {
                continue;
            }
            if ($applyIfValid((int) $raw)) {
                return $next($request);
            }
        }

        $headerYearId = $request->header('X-Annee-Scolaire-Id');

        if ($headerYearId !== null && is_numeric($headerYearId)) {
            $applyIfValid((int) $headerYearId);
        }

        return $next($request);
    }
}
