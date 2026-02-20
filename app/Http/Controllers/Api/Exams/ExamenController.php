<?php

namespace App\Http\Controllers\Api\Exams;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExamenRequest;
use App\Http\Requests\UpdateExamenRequest;
use App\Models\Examen;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Examen Controller
 *
 * Manages national and provincial exams.
 */
class ExamenController extends Controller
{
    public function index(): JsonResponse
    {
        $examens = Examen::with(['niveau', 'anneeScolaire'])->paginate(15);

        return response()->json([
            'message' => 'Liste des examens récupérée avec succès',
            'data' => $examens
        ]);
    }

    public function store(StoreExamenRequest $request): JsonResponse
    {
        $examen = Examen::create($request->validated());

        return response()->json([
            'message' => 'Examen créé avec succès',
            'data' => $examen->load(['niveau', 'anneeScolaire'])
        ], 201);
    }

    public function show(Examen $examen): JsonResponse
    {
        return response()->json([
            'data' => $examen->load(['niveau', 'anneeScolaire', 'sessions'])
        ]);
    }

    public function update(UpdateExamenRequest $request, Examen $examen): JsonResponse
    {
        $examen->update($request->validated());

        return response()->json([
            'message' => 'Examen mis à jour avec succès',
            'data' => $examen->load(['niveau', 'anneeScolaire'])
        ]);
    }

    public function destroy(Examen $examen): JsonResponse
    {
        $examen->delete();

        return response()->json([
            'message' => 'Examen supprimé avec succès'
        ]);
    }

    public function publish(Examen $examen): JsonResponse
    {
        // Example logic for publishing an exam (e.g. notifying schools)
        return response()->json([
            'message' => 'Examen publié avec succès'
        ]);
    }
}
