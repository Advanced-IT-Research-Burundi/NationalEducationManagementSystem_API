<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use App\Models\CampagneCollecte;
use App\Models\FormulaireCollecte;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FormulaireCollecteController extends Controller
{
    public function index(CampagneCollecte $campagne): JsonResponse
    {
        $formulaires = $campagne->formulaires()->orderBy('ordre')->get();

        return response()->json(['data' => $formulaires]);
    }

    public function store(Request $request, CampagneCollecte $campagne): JsonResponse
    {
        if ($campagne->statut !== CampagneCollecte::STATUT_BROUILLON) {
            return response()->json(['message' => 'Impossible d\'ajouter un formulaire à une campagne ouverte'], 422);
        }

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'champs' => 'required|array',
            'champs.*.name' => 'required|string|max:100',
            'champs.*.label' => 'required|string|max:255',
            'champs.*.type' => 'required|in:text,number,date,select,checkbox,textarea',
            'champs.*.required' => 'boolean',
            'champs.*.options' => 'nullable|array',
            'champs.*.ordre' => 'integer',
            'ordre' => 'integer',
        ]);

        $validated['campagne_id'] = $campagne->id;
        $validated['ordre'] = $validated['ordre'] ?? $campagne->formulaires()->max('ordre') + 1;
        $validated['created_by'] = auth()->id();

        $formulaire = FormulaireCollecte::create($validated);

        return response()->json([
            'message' => 'Formulaire créé',
            'data' => $formulaire,
        ], 201);
    }

    public function show(CampagneCollecte $campagne, FormulaireCollecte $formulaire): JsonResponse
    {
        if ($formulaire->campagne_id !== $campagne->id) {
            abort(404);
        }

        return response()->json(['data' => $formulaire]);
    }

    public function update(Request $request, CampagneCollecte $campagne, FormulaireCollecte $formulaire): JsonResponse
    {
        if ($formulaire->campagne_id !== $campagne->id) {
            abort(404);
        }
        if ($campagne->statut !== CampagneCollecte::STATUT_BROUILLON) {
            return response()->json(['message' => 'Impossible de modifier un formulaire d\'une campagne ouverte'], 422);
        }

        $validated = $request->validate([
            'titre' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'champs' => 'sometimes|array',
            'champs.*.name' => 'required_with:champs|string|max:100',
            'champs.*.label' => 'required_with:champs|string|max:255',
            'champs.*.type' => 'required_with:champs|in:text,number,date,select,checkbox,textarea',
            'champs.*.required' => 'boolean',
            'champs.*.options' => 'nullable|array',
            'ordre' => 'sometimes|integer',
        ]);

        $formulaire->update($validated);

        return response()->json([
            'message' => 'Formulaire mis à jour',
            'data' => $formulaire,
        ]);
    }

    public function destroy(CampagneCollecte $campagne, FormulaireCollecte $formulaire): JsonResponse
    {
        if ($formulaire->campagne_id !== $campagne->id) {
            abort(404);
        }
        if ($campagne->statut !== CampagneCollecte::STATUT_BROUILLON) {
            return response()->json(['message' => 'Impossible de supprimer un formulaire d\'une campagne ouverte'], 422);
        }

        $formulaire->delete();

        return response()->json(['message' => 'Formulaire supprimé']);
    }
}
