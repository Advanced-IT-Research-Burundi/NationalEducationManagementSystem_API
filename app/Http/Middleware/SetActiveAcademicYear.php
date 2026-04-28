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
     * Reads the academic year from the X-Annee-Scolaire-Id header
     * or falls back to the currently active year in the database.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $headerYearId = $request->header('X-Annee-Scolaire-Id');

        if ($headerYearId !== null && is_numeric($headerYearId)) {
            $exists = AnneeScolaire::withoutGlobalScopes()
                ->where('id', (int) $headerYearId)
                ->exists();

            if ($exists) {
                AcademicYearService::setCurrent((int) $headerYearId);
            }
        }

        return $next($request);
    }
}
