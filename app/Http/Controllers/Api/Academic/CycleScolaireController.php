<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCycleScolaireRequest;
use App\Http\Requests\UpdateCycleScolaireRequest;
use App\Models\CycleScolaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CycleScolaireController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CycleScolaire::query()->with('typeScolaire:id,nom');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('type_id')) {
            $query->where('type_id', $request->integer('type_id'));
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        $cycles = $query->latest()->paginate($request->integer('per_page', 15));

        return sendResponse($cycles, 'Cycles scolaires récupérés avec succès');
    }

    public function list(Request $request): JsonResponse
    {
        $query = CycleScolaire::query()->actif()->orderBy('nom');

        if ($request->filled('type_id')) {
            $query->where('type_id', $request->integer('type_id'));
        }

        return response()->json($query->get(['id', 'nom', 'type_id']));
    }

    public function store(StoreCycleScolaireRequest $request): JsonResponse
    {
        $cycle = CycleScolaire::create($request->validated());

        return response()->json([
            'message' => 'Cycle scolaire créé avec succès',
            'cycle_scolaire' => $cycle->load('typeScolaire:id,nom'),
        ], 201);
    }

    public function show(CycleScolaire $cycleScolaire): JsonResponse
    {
        return response()->json($cycleScolaire->load(['typeScolaire:id,nom'])->loadCount('niveaux'));
    }

    public function update(UpdateCycleScolaireRequest $request, CycleScolaire $cycleScolaire): JsonResponse
    {
        $cycleScolaire->update($request->validated());

        return response()->json([
            'message' => 'Cycle scolaire mis à jour avec succès',
            'cycle_scolaire' => $cycleScolaire->load('typeScolaire:id,nom'),
        ]);
    }

    public function destroy(CycleScolaire $cycleScolaire): JsonResponse
    {
        if ($cycleScolaire->niveaux()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce cycle scolaire car il est déjà utilisé.',
            ], 422);
        }

        $cycleScolaire->delete();

        return response()->json(['message' => 'Cycle scolaire supprimé avec succès']);
    }
}
