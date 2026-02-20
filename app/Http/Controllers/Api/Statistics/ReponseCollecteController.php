<?php

namespace App\Http\Controllers\Api\Statistics;

use App\Http\Controllers\Controller;
use App\Models\CampagneCollecte;
use App\Models\FormulaireCollecte;
use App\Models\ReponseCollecte;
use App\Models\School;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReponseCollecteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $campagneId = $request->campagne_id;
        $query = ReponseCollecte::with(['formulaire.campagne', 'ecole', 'soumisPar', 'validePar'])
            ->when($campagneId, fn ($q) => $q->whereHas('formulaire', fn ($sq) => $sq->where('campagne_id', $campagneId)))
            ->when($request->filled('school_id'), fn ($q) => $q->where('school_id', $request->school_id))
            ->when($request->filled('formulaire_id'), fn ($q) => $q->where('formulaire_id', $request->formulaire_id))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->latest();

        $reponses = $query->paginate($request->get('per_page', 15));

        return response()->json($reponses);
    }

    public function store(Request $request, FormulaireCollecte $formulaire): JsonResponse
    {
        $ecoleId = $request->school_id ?? auth()->user()?->school_id;
        if (!$ecoleId) {
            return response()->json(['message' => 'École requise'], 422);
        }

        if (!$formulaire->campagne->isOpen()) {
            return response()->json(['message' => 'Campagne fermée ou non ouverte'], 422);
        }

        $existing = ReponseCollecte::where('formulaire_id', $formulaire->id)->where('school_id', $ecoleId)->first();
        if ($existing && $existing->statut !== ReponseCollecte::STATUT_BROUILLON) {
            return response()->json(['message' => 'Une réponse existe déjà pour cette école'], 422);
        }

        $validated = $request->validate([
            'donnees' => 'required|array',
            'school_id' => 'sometimes|exists:schools,id',
        ]);

        $validated['formulaire_id'] = $formulaire->id;
        $validated['school_id'] = $ecoleId;
        $validated['statut'] = ReponseCollecte::STATUT_BROUILLON;
        $validated['created_by'] = auth()->id();

        $reponse = ReponseCollecte::updateOrCreate(
            ['formulaire_id' => $formulaire->id, 'school_id' => $ecoleId],
            $validated
        );

        return response()->json([
            'message' => 'Réponse enregistrée',
            'data' => $reponse->load(['formulaire', 'ecole']),
        ], 201);
    }

    public function submit(Request $request, ReponseCollecte $reponse): JsonResponse
    {
        if ($reponse->statut !== ReponseCollecte::STATUT_BROUILLON) {
            return response()->json(['message' => 'Réponse déjà soumise'], 422);
        }

        $reponse->update([
            'statut' => ReponseCollecte::STATUT_SOUMIS,
            'soumis_par' => auth()->id(),
            'soumis_at' => now(),
        ]);

        return response()->json([
            'message' => 'Réponse soumise pour validation',
            'data' => $reponse->load(['formulaire', 'ecole']),
        ]);
    }

    public function validateResponse(Request $request, ReponseCollecte $reponse): JsonResponse
    {
        $user = auth()->user();
        $userLevel = $user->admin_level ?? null;

        $validated = $request->validate([
            'action' => 'required|in:valider,rejeter',
            'motif_rejet' => 'required_if:action,rejeter|nullable|string',
        ]);

        if ($validated['action'] === 'rejeter') {
            $reponse->update([
                'statut' => ReponseCollecte::STATUT_REJETE,
                'valide_par' => auth()->id(),
                'valide_at' => now(),
                'niveau_validation' => null,
                'motif_rejet' => $validated['motif_rejet'],
            ]);
            return response()->json(['message' => 'Réponse rejetée', 'data' => $reponse->load(['formulaire', 'ecole'])]);
        }

        $nextLevel = $reponse->getNextValidationLevel();
        if (!$nextLevel) {
            return response()->json(['message' => 'Réponse déjà entièrement validée'], 422);
        }

        // Admin levels: ECOLE < ZONE < COMMUNE < PROVINCE < MINISTERE < PAYS
        $levelOrder = ['ECOLE' => 0, 'ZONE' => 1, 'COMMUNE' => 2, 'PROVINCE' => 3, 'MINISTERE' => 4, 'PAYS' => 5];
        $userLevelOrder = $levelOrder[strtoupper($userLevel ?? '')] ?? -1;
        $requiredLevel = ['zone' => 'ZONE', 'commune' => 'COMMUNE', 'province' => 'PROVINCE'][$nextLevel] ?? null;
        $requiredLevelOrder = $requiredLevel ? $levelOrder[$requiredLevel] : 0;

        if ($userLevelOrder < $requiredLevelOrder) {
            return response()->json(['message' => 'Vous n\'avez pas le niveau pour valider à ce stade'], 403);
        }

        $newStatut = match ($nextLevel) {
            'zone' => ReponseCollecte::STATUT_VALIDE_ZONE,
            'commune' => ReponseCollecte::STATUT_VALIDE_COMMUNE,
            'province' => ReponseCollecte::STATUT_VALIDE_PROVINCE,
            default => $reponse->statut,
        };

        $reponse->update([
            'statut' => $newStatut,
            'valide_par' => auth()->id(),
            'valide_at' => now(),
            'niveau_validation' => $nextLevel,
            'motif_rejet' => null,
        ]);

        return response()->json([
            'message' => 'Réponse validée',
            'data' => $reponse->load(['formulaire', 'ecole']),
        ]);
    }

    public function bySchool(School $school, Request $request): JsonResponse
    {
        $reponses = ReponseCollecte::with(['formulaire.campagne'])
            ->where('school_id', $school->id)
            ->when($request->filled('campagne_id'), fn ($q) => $q->whereHas('formulaire', fn ($sq) => $sq->where('campagne_id', $request->campagne_id)))
            ->latest()
            ->paginate($request->get('per_page', 15));

        return response()->json($reponses);
    }

    public function export(Request $request, CampagneCollecte $campagne): JsonResponse
    {
        $reponses = ReponseCollecte::with(['formulaire', 'ecole', 'soumisPar'])
            ->whereHas('formulaire', fn ($q) => $q->where('campagne_id', $campagne->id))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->get();

        $data = $reponses->map(fn ($r) => [
            'id' => $r->id,
            'formulaire' => $r->formulaire->titre,
            'ecole' => $r->ecole->name ?? $r->ecole->code_ecole,
            'donnees' => $r->donnees,
            'statut' => $r->statut,
            'soumis_at' => $r->soumis_at?->toIso8601String(),
            'valide_at' => $r->valide_at?->toIso8601String(),
        ]);

        return response()->json([
            'data' => $data,
            'campagne' => $campagne->titre,
            'exported_at' => now()->toIso8601String(),
        ]);
    }
}
