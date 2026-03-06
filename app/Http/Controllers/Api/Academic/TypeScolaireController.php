<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTypeScolaireRequest;
use App\Http\Requests\UpdateTypeScolaireRequest;
use App\Models\TypeScolaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TypeScolaireController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = TypeScolaire::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        $types = $query->latest()->paginate($request->integer('per_page', 15));

        return sendResponse($types, 'Types scolaires récupérés avec succès');
    }

    public function list(): JsonResponse
    {
        $types = TypeScolaire::actif()->orderBy('nom')->get(['id', 'nom']);

        return response()->json($types);
    }

    public function store(StoreTypeScolaireRequest $request): JsonResponse
    {
        $type = TypeScolaire::create($request->validated());

        return response()->json([
            'message' => 'Type scolaire créé avec succès',
            'type_scolaire' => $type,
        ], 201);
    }

    public function show(TypeScolaire $typeScolaire): JsonResponse
    {
        return response()->json($typeScolaire->loadCount(['cycles', 'sections', 'niveaux']));
    }

    public function update(UpdateTypeScolaireRequest $request, TypeScolaire $typeScolaire): JsonResponse
    {
        $typeScolaire->update($request->validated());

        return response()->json([
            'message' => 'Type scolaire mis à jour avec succès',
            'type_scolaire' => $typeScolaire,
        ]);
    }

    public function destroy(TypeScolaire $typeScolaire): JsonResponse
    {
        if ($typeScolaire->cycles()->exists() || $typeScolaire->sections()->exists() || $typeScolaire->niveaux()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce type scolaire car il est déjà utilisé.',
            ], 422);
        }

        $typeScolaire->delete();

        return response()->json(['message' => 'Type scolaire supprimé avec succès']);
    }
}
