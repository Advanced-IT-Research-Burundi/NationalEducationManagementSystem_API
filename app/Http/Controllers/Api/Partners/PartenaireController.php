<?php

namespace App\Http\Controllers\Api\Partners;

use App\Http\Controllers\Controller;
use App\Models\Partenaire;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartenaireController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Partenaire::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('pays')) {
            $query->where('pays', $request->pays);
        }

        $perPage = $request->get('per_page', 20);
        $partenaires = $query->latest()->paginate($perPage);

        return response()->json($partenaires);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'type' => 'required|in:PTF,ONG,UNIVERSITE,ENTREPRISE,AUTRE',
            'pays' => 'nullable|string|max:255',
            'contact_nom' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_telephone' => 'nullable|string|max:255',
            'adresse' => 'nullable|string',
            'description' => 'nullable|string',
            'date_debut_partenariat' => 'nullable|date',
            'statut' => 'nullable|in:ACTIF,INACTIF,SUSPENDU',
        ]);

        $partenaire = Partenaire::create($validated);

        return response()->json([
            'message' => 'Partenaire créé avec succès',
            'data' => $partenaire,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Partenaire $partenaire): JsonResponse
    {
        return response()->json(['data' => $partenaire]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Partenaire $partenaire): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:PTF,ONG,UNIVERSITE,ENTREPRISE,AUTRE',
            'pays' => 'nullable|string|max:255',
            'contact_nom' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_telephone' => 'nullable|string|max:255',
            'adresse' => 'nullable|string',
            'description' => 'nullable|string',
            'date_debut_partenariat' => 'nullable|date',
            'statut' => 'nullable|in:ACTIF,INACTIF,SUSPENDU',
        ]);

        $partenaire->update($validated);

        return response()->json([
            'message' => 'Partenaire mis à jour avec succès',
            'data' => $partenaire,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Partenaire $partenaire): JsonResponse
    {
        $partenaire->delete();

        return response()->json([
            'message' => 'Partenaire supprimé avec succès',
        ]);
    }

    /**
     * Get statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_partenaires' => Partenaire::count(),
            'total_projets' => \App\Models\ProjetPartenariat::count(),
            'projets_actifs' => \App\Models\ProjetPartenariat::where('statut', 'EN_COURS')->count(),
            'total_financements' => \App\Models\Financement::sum('montant'),
            'partenaires_par_type' => Partenaire::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ];

        return response()->json(['data' => $stats]);
    }
}
