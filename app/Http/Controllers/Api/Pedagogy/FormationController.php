<?php

namespace App\Http\Controllers\Api\Pedagogy;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFormationRequest;
use App\Http\Requests\UpdateFormationRequest;
use App\Models\Formation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Formation Controller
 *
 * Manages training sessions for teachers and staff.
 */
class FormationController extends Controller
{
    public function index(): JsonResponse
    {
        $formations = Formation::with('formateur')->paginate(15);

        return response()->json([
            'message' => 'Module Pedagogy - Formations',
            'data' => $formations,
        ]);
    }

    public function store(StoreFormationRequest $request): JsonResponse
    {
        $formation = Formation::create($request->validated());

        return response()->json([
            'message' => 'Formation créée avec succès',
            'data' => $formation->load('formateur'),
        ], 201);
    }

    public function show(Formation $formation): JsonResponse
    {
        return response()->json([
            'data' => $formation->load(['formateur', 'participants']),
        ]);
    }

    public function update(UpdateFormationRequest $request, Formation $formation): JsonResponse
    {
        $formation->update($request->validated());

        return response()->json([
            'message' => 'Formation mise à jour avec succès',
            'data' => $formation->load('formateur'),
        ]);
    }

    public function destroy(Formation $formation): JsonResponse
    {
        $formation->delete();

        return response()->json([
            'message' => 'Formation supprimée avec succès',
        ]);
    }

    public function register(Request $request, Formation $formation): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $formation->participants()->syncWithoutDetaching([$request->user_id]);

        return response()->json([
            'message' => 'Inscription à la formation réussie',
            'data' => $formation->load('participants'),
        ]);
    }

    public function participants(Formation $formation): JsonResponse
    {
        return response()->json([
            'data' => $formation->participants,
        ]);
    }

    public function complete(Formation $formation): JsonResponse
    {
        $formation->update(['statut' => 'terminee']);

        return response()->json([
            'message' => 'Formation marquée comme terminée',
            'data' => $formation,
        ]);
    }
}
