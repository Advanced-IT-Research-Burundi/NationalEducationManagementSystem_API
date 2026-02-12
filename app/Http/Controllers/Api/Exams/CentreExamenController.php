<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCentreExamenRequest;
use App\Http\Requests\UpdateCentreExamenRequest;
use App\Models\CentreExamen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Centre Examen Controller
 *
 * Manages exam centers and school assignments.
 */
class CentreExamenController extends Controller
{
    public function index(): JsonResponse
    {
        $centres = CentreExamen::with(['ecole', 'session.examen', 'responsable'])->paginate(15);

        return response()->json([
            'message' => 'Liste des centres d\'examen récupérée avec succès',
            'data' => $centres
        ]);
    }

    public function store(StoreCentreExamenRequest $request): JsonResponse
    {
        $centre = CentreExamen::create($request->validated());

        return response()->json([
            'message' => 'Centre d\'examen créé avec succès',
            'data' => $centre->load(['ecole', 'session.examen', 'responsable'])
        ], 201);
    }

    public function show(CentreExamen $centre): JsonResponse
    {
        return response()->json([
            'data' => $centre->load(['ecole', 'session.examen', 'responsable', 'inscriptions'])
        ]);
    }

    public function update(UpdateCentreExamenRequest $request, CentreExamen $centre): JsonResponse
    {
        $centre->update($request->validated());

        return response()->json([
            'message' => 'Centre d\'examen mis à jour avec succès',
            'data' => $centre->load(['ecole', 'session.examen', 'responsable'])
        ]);
    }

    public function destroy(CentreExamen $centre): JsonResponse
    {
        $centre->delete();

        return response()->json([
            'message' => 'Centre d\'examen supprimé avec succès'
        ]);
    }

    public function assignSchools(Request $request, CentreExamen $centre): JsonResponse
    {
        // Example logic for assigning schools to a center
        return response()->json([
            'message' => 'Écoles affectées au centre avec succès'
        ]);
    }
}
