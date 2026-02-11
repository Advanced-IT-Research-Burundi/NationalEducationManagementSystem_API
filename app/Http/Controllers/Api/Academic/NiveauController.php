<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNiveauRequest;
use App\Http\Requests\UpdateNiveauRequest;
use App\Models\Niveau;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NiveauController extends Controller
{
    /**
     * Display a listing of niveaux.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Niveau::query();

        // Search filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'LIKE', "%{$request->search}%")
                    ->orWhere('code', 'LIKE', "%{$request->search}%");
            });
        }

        // Cycle filter
        if ($request->filled('cycle')) {
            $query->byCycle($request->cycle);
        }

        // Active filter
        if ($request->filled('actif')) {
            $query->where('actif', filter_var($request->actif, FILTER_VALIDATE_BOOLEAN));
        }

        $niveaux = $query->ordered()->paginate($request->get('per_page', 15));

        return sendResponse($niveaux, 'Niveaux récupérés avec succès');
    }

    /**
     * Store a newly created niveau.
     */
    public function store(StoreNiveauRequest $request): JsonResponse
    {
        $data = $request->validated();

        $niveau = Niveau::create($data);

        return response()->json([
            'message' => 'Niveau créé avec succès',
            'niveau' => $niveau,
        ], 201);
    }

    /**
     * Display the specified niveau.
     */
    public function show(Niveau $niveau): JsonResponse
    {
        return response()->json($niveau->load('classes'));
    }

    /**
     * Update the specified niveau.
     */
    public function update(UpdateNiveauRequest $request, Niveau $niveau): JsonResponse
    {
        $niveau->update($request->validated());

        return response()->json([
            'message' => 'Niveau mis à jour avec succès',
            'niveau' => $niveau,
        ]);
    }

    /**
     * Remove the specified niveau.
     */
    public function destroy(Niveau $niveau): JsonResponse
    {
        // Check if niveau has classes
        if ($niveau->classes()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce niveau car il contient des classes.',
            ], 422);
        }

        $niveau->delete();

        return response()->json(['message' => 'Niveau supprimé avec succès']);
    }

    /**
     * Get all active niveaux (for dropdowns).
     */
    public function list(): JsonResponse
    {
        $niveaux = Niveau::actif()->ordered()->get(['id', 'nom', 'code', 'cycle']);

        return response()->json($niveaux);
    }
}
