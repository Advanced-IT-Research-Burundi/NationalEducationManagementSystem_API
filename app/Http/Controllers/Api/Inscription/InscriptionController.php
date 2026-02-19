<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Controller;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Inscription::query()
            ->with(['eleve', 'anneeScolaire', 'ecole', 'niveauDemande', 'campagne']);

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('eleve', function ($q) use ($search) {
                $q->where('nom', 'like', "%{$search}%")
                  ->orWhere('prenom', 'like', "%{$search}%")
                  ->orWhere('matricule', 'like', "%{$search}%");
            });
        }

        $inscriptions = $query->latest('date_inscription')->paginate($request->per_page ?? 15);

        return response()->json($inscriptions);
    }

    public function store(Request $request): JsonResponse
    {
        // Basic validation - should be moved to FormRequest
        $validated = $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'campagne_id' => 'required|exists:campagnes_inscription,id',
            'school_id' => 'required|exists:schools,id',
            'annee_scolaire_id' => 'required|exists:annee_scolaires,id',
            'niveau_demande_id' => 'required|exists:niveaux_scolaires,id',
            'type_inscription' => ['required', Rule::in(['nouvelle', 'reinscription', 'transfert_entrant'])],
            'pieces_fournies' => 'nullable|array',
            'observations' => 'nullable|string',
        ]);

        // Generate numero_inscription logic here or inside model boot
        // For now, simplify assignment
        $validated['numero_inscription'] = 'INS-' . date('Y') . '-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $validated['date_inscription'] = now();
        $validated['statut'] = 'brouillon';
        $validated['created_by'] = $request->user()->id;

        $inscription = Inscription::create($validated);

        return response()->json($inscription, 201);
    }

    public function show(Inscription $inscription): JsonResponse
    {
        $inscription->load(['eleve', 'anneeScolaire', 'ecole', 'niveauDemande', 'campagne', 'creator']);
        return response()->json($inscription);
    }

    public function update(Request $request, Inscription $inscription): JsonResponse
    {
        // Only allow update if status is brouillon or rejete
        if (!in_array($inscription->statut, ['brouillon', 'rejete'])) {
            return response()->json(['message' => 'Impossible de modifier une inscription en cours de traitement ou validée.'], 403);
        }

        $validated = $request->validate([
            'niveau_demande_id' => 'sometimes|exists:niveaux_scolaires,id',
            'type_inscription' => ['sometimes', Rule::in(['nouvelle', 'reinscription', 'transfert_entrant'])],
            'pieces_fournies' => 'nullable|array',
            'observations' => 'nullable|string',
        ]);

        $inscription->update($validated);

        return response()->json($inscription);
    }

    public function destroy(Inscription $inscription): JsonResponse
    {
        if ($inscription->statut !== 'brouillon') {
             return response()->json(['message' => 'Seules les inscriptions brouillon peuvent être supprimées.'], 403);
        }

        $inscription->delete();
        return response()->json(null, 204);
    }

    // Workflow Actions
    public function soumettre(Inscription $inscription, Request $request): JsonResponse
    {
        if ($inscription->statut !== 'brouillon' && $inscription->statut !== 'rejete') {
            return response()->json(['message' => 'Statut invalide pour soumission.'], 400);
        }

        $inscription->update([
            'statut' => 'soumis',
            'date_soumission' => now(),
            'soumis_par' => $request->user()->id,
        ]);

        return response()->json($inscription);
    }

    public function valider(Inscription $inscription, Request $request): JsonResponse
    {
        if ($inscription->statut !== 'soumis') {
            return response()->json(['message' => 'L\'inscription doit être soumise pour être validée.'], 400);
        }

        // TODO: Check permissions

        $inscription->update([
            'statut' => 'valide',
            'date_validation' => now(),
            'valide_par' => $request->user()->id,
        ]);

        // Update Eleve status to INSCRIT?
        $inscription->eleve->update(['statut_global' => 'actif']); // Or whatever the logic is

        return response()->json($inscription);
    }

    public function rejeter(Inscription $inscription, Request $request): JsonResponse
    {
        $request->validate(['motif_rejet' => 'required|string']);

        if ($inscription->statut !== 'soumis') {
             return response()->json(['message' => 'L\'inscription doit être soumise pour être rejetée.'], 400);
        }

        $inscription->update([
            'statut' => 'rejete',
            'motif_rejet' => $request->input('motif_rejet'),
            'valide_par' => $request->user()->id, // Person rejecting
        ]);

        return response()->json($inscription);
    }
}
