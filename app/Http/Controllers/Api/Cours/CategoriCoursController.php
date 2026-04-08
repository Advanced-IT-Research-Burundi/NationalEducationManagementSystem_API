<?php

namespace App\Http\Controllers\Api\Cours;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoriCoursRequest;
use App\Http\Requests\UpdateCategoriCoursRequest;
use App\Models\CategorieCours;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriCoursController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CategorieCours::query()->withCount('cours');

        if ($request->filled('search')) {
            $query->where('nom', 'LIKE', "%{$request->search}%");
        }

        $categories = $query->ordered()->paginate($request->get('per_page', 15));

        return response()->json($categories);
    }

    public function list(): JsonResponse
    {
        $categories = CategorieCours::ordered()->get(['id', 'nom', 'ordre', 'afficher_bulletin']);

        return response()->json($categories);
    }

    public function store(StoreCategoriCoursRequest $request): JsonResponse
    {
        $categorie = CategorieCours::create($request->validated());

        return response()->json([
            'message' => 'Catégorie créée avec succès',
            'data' => $categorie,
        ], 201);
    }

    public function show(CategorieCours $categories_cour): JsonResponse
    {
        return response()->json([
            'data' => $categories_cour->loadCount('cours'),
        ]);
    }

    public function update(UpdateCategoriCoursRequest $request, CategorieCours $categories_cour): JsonResponse
    {
        $categories_cour->update($request->validated());

        return response()->json([
            'message' => 'Catégorie mise à jour avec succès',
            'data' => $categories_cour,
        ]);
    }

    public function destroy(CategorieCours $categories_cour): JsonResponse
    {
        if ($categories_cour->cours()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette catégorie car elle contient des cours.',
            ], 422);
        }

        $categories_cour->delete();

        return response()->json(['message' => 'Catégorie supprimée avec succès']);
    }
}
