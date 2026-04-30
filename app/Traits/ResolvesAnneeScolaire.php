<?php

namespace App\Traits;

use App\Services\AcademicYearService;
use Illuminate\Http\Request;

trait ResolvesAnneeScolaire
{
    /**
     * Resolve the année scolaire ID from the request.
     *
     * Priority: query/body param > AcademicYearService (Context from header > cache/DB).
     *
     * When an explicit param is provided, we also push it into AcademicYearService
     * so that global scopes (AcademicYearScope) on Classe, Evaluation, Note, etc.
     * stay aligned with the controller's explicit filter — prevents empty results
     * when the param year differs from the header year.
     */
    protected function resolveAnneeScolaireId(Request $request): ?int
    {
        if ($request->filled('annee_scolaire_id')) {
            $yearId = $request->integer('annee_scolaire_id');
            AcademicYearService::setCurrent($yearId);

            return $yearId;
        }

        return AcademicYearService::currentId();
    }
}
