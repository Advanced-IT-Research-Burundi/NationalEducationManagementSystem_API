<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use App\Models\ProjetPartenariat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjetPartenaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProjetPartenariat::query()->with(['partenaire', 'responsable']);

        if ($request->has('partenaire_id')) {
            $query->where('partenaire_id', $request->partenaire_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('secteur')) {
            $query->where('secteur', $request->secteur);
        }

        $perPage = $request->get('per_page', 20);
        $projets = $query->latest()->paginate($perPage);

        return response()->json($projets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partenaire_id' => 'required|exists:partenaires,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'secteur' => 'nullable|string',
            'budget_total' => 'nullable|numeric|min:0',
            'date_debut' => 'required|date',
            'date_fin' => 'nullable|date|after:date_debut',
            'responsable_id' => 'nullable|exists:users,id',
            'statut' => 'required|in:EN_PREPARATION,EN_COURS,TERMINE,SUSPENDU,ANNULE',
        ]);

        $projet = ProjetPartenariat::create($validated);
        $projet->load(['partenaire', 'responsable']);

        return response()->json([
            'message' => 'Projet créé avec succès',
            'data' => $projet,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $projet = ProjetPartenariat::with(['partenaire', 'responsable', 'financements'])->findOrFail($id);
        
        return response()->json(['data' => $projet]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $projet = ProjetPartenariat::findOrFail($id);
        
        $validated = $request->validate([
            'partenaire_id' => 'sometimes|exists:partenaires,id',
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'secteur' => 'nullable|string',
            'budget_total' => 'nullable|numeric|min:0',
            'date_debut' => 'sometimes|date',
            'date_fin' => 'nullable|date',
            'responsable_id' => 'nullable|exists:users,id',
            'statut' => 'sometimes|in:EN_PREPARATION,EN_COURS,TERMINE,SUSPENDU,ANNULE',
        ]);

        $projet->update($validated);
        $projet->load(['partenaire', 'responsable']);

        return response()->json([
            'message' => 'Projet mis à jour avec succès',
            'data' => $projet,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $projet = ProjetPartenariat::findOrFail($id);
        $projet->delete();

        return response()->json([
            'message' => 'Projet supprimé avec succès',
        ]);
    }
}
