<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Controller;
use App\Models\CampagneInscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CampagneInscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = CampagneInscription::query()->with(['anneeScolaire', 'ecole']);

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $campagnes = $query->latest('date_ouverture')->paginate($request->per_page ?? 15);

        return response()->json($campagnes);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'school_id' => 'required|exists:schools,id',
            'type' => 'required|in:nouvelle,reinscription',
            'date_ouverture' => 'required|date',
            'date_cloture' => 'required|date|after:date_ouverture',
            'quota_max' => 'nullable|integer|min:1',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['statut'] = 'planifiee';

        $campagne = CampagneInscription::create($validated);

        return response()->json($campagne, 201);
    }

    public function show(CampagneInscription $campagneInscription): JsonResponse
    {
        $campagneInscription->load(['anneeScolaire', 'ecole', 'creator']);
        return response()->json($campagneInscription);
    }

    public function update(Request $request, CampagneInscription $campagneInscription): JsonResponse
    {
        $validated = $request->validate([
            'date_ouverture' => 'sometimes|date',
            'date_cloture' => 'sometimes|date|after:date_ouverture',
            'quota_max' => 'nullable|integer|min:1',
            'type' => 'sometimes|in:nouvelle,reinscription',
        ]);

        $campagneInscription->update($validated);

        return response()->json($campagneInscription);
    }

    public function destroy(CampagneInscription $campagneInscription): JsonResponse
    {
        if ($campagneInscription->inscriptions()->exists()) {
            return response()->json(['message' => 'Impossible de supprimer une campagne ayant déjà des inscriptions.'], 400);
        }

        $campagneInscription->delete();
        return response()->json(null, 204);
    }

    public function ouvrir(CampagneInscription $campagneInscription): JsonResponse
    {
        $campagneInscription->update(['statut' => 'ouverte']);
        return response()->json($campagneInscription);
    }

    public function cloturer(CampagneInscription $campagneInscription): JsonResponse
    {
        $campagneInscription->update(['statut' => 'cloturee']);
        return response()->json($campagneInscription);
    }
}
