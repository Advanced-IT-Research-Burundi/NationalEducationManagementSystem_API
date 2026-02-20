<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\AffectationEnseignant;
use App\Models\Resultat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MatiereController extends Controller
{
    /**
     * Get a list of all unique subjects.
     */
    public function index(Request $request): JsonResponse
    {
        // Get subjects from affectations
        $fromAffectations = AffectationEnseignant::select('matiere')
            ->whereNotNull('matiere')
            ->distinct()
            ->pluck('matiere')
            ->toArray();

        // Get subjects from exam results
        $fromResults = Resultat::select('matiere')
            ->whereNotNull('matiere')
            ->distinct()
            ->pluck('matiere')
            ->toArray();

        // Merge and unique
        $allMatieres = array_unique(array_merge($fromAffectations, $fromResults));
        sort($allMatieres);

        // Format for frontend (mimic a real Subject entity)
        $formatted = array_map(function ($name) {
            return [
                'id' => $name, // Use name as ID since we don't have a table
                'name' => $name,
                'code' => strtoupper(substr($name, 0, 3)),
                'level' => 'N/A',
                'teachers' => AffectationEnseignant::where('matiere', $name)->count(),
                'hours' => 0 // Mocked for now
            ];
        }, $allMatieres);

        return response()->json($formatted);
    }
}
