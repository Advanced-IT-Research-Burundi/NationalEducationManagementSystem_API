<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnneeScolaireRequest;
use App\Http\Requests\UpdateAnneeScolaireRequest;
use App\Models\AnneeScolaire;
use App\Models\Inscription;
use App\Services\TransitionScolaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnneeScolaireController extends Controller
{
    /**
     * Display a listing of années scolaires.
     */
    public function index(Request $request): JsonResponse
    {
        $query = AnneeScolaire::query();

        // Search filter
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('libelle', 'LIKE', "%{$request->search}%")
                    ->orWhere('code', 'LIKE', "%{$request->search}%");
            });
        }

        // Active filter
        if ($request->filled('est_active')) {
            $query->where('est_active', filter_var($request->est_active, FILTER_VALIDATE_BOOLEAN));
        }

        $anneesScolaires = $query->ordered()->paginate($request->get('per_page', 15));

        return sendResponse($anneesScolaires, 'Années scolaires récupérées avec succès');
    }

    /**
     * Store a newly created année scolaire.
     */
    public function store(StoreAnneeScolaireRequest $request): JsonResponse
    {
        $data = $request->validated();

        // If this is set as active, deactivate all others first
        if (! empty($data['est_active']) && $data['est_active']) {
            AnneeScolaire::where('est_active', true)->update(['est_active' => false]);
        }

        $anneeScolaire = AnneeScolaire::create($data);

        return response()->json([
            'message' => 'Année scolaire créée avec succès',
            'annee_scolaire' => $anneeScolaire,
        ], 201);
    }

    /**
     * Display the specified année scolaire.
     */
    public function show(AnneeScolaire $anneeScolaire): JsonResponse
    {
        return response()->json($anneeScolaire->loadCount(['classes', 'inscriptions']));
    }

    /**
     * Update the specified année scolaire.
     */
    public function update(UpdateAnneeScolaireRequest $request, AnneeScolaire $anneeScolaire): JsonResponse
    {
        $data = $request->validated();

        // If this is being set as active, deactivate all others first
        if (! empty($data['est_active']) && $data['est_active'] && ! $anneeScolaire->est_active) {
            AnneeScolaire::where('est_active', true)
                ->where('id', '!=', $anneeScolaire->id)
                ->update(['est_active' => false]);
        }

        $anneeScolaire->update($data);

        return response()->json([
            'message' => 'Année scolaire mise à jour avec succès',
            'annee_scolaire' => $anneeScolaire,
        ]);
    }

    /**
     * Remove the specified année scolaire.
     */
    public function destroy(AnneeScolaire $anneeScolaire): JsonResponse
    {
        // Check if année has related data
        if ($anneeScolaire->classes()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette année scolaire car elle contient des classes.',
            ], 422);
        }

        if ($anneeScolaire->inscriptions()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette année scolaire car elle contient des inscriptions.',
            ], 422);
        }

        $anneeScolaire->delete();

        return response()->json(['message' => 'Année scolaire supprimée avec succès']);
    }

    /**
     * Get all années scolaires (for dropdowns).
     */
    public function list(): JsonResponse
    {
        $anneesScolaires = AnneeScolaire::ordered()->get(['id', 'code', 'libelle', 'est_active']);

        return response()->json($anneesScolaires);
    }

    /**
     * Get the current active année scolaire.
     */
    public function current(): JsonResponse
    {
        $current = AnneeScolaire::current();

        if (! $current) {
            return response()->json([
                'message' => 'Aucune année scolaire active.',
            ], 404);
        }

        return response()->json([
            'annee_scolaire' => $current,
            'progress' => $current->progress,
            'days_elapsed' => $current->days_elapsed,
            'total_days' => $current->total_days,
        ]);
    }

    /**
     * Toggle active status (activate/deactivate).
     */
    public function toggleActive(AnneeScolaire $anneeScolaire): JsonResponse
    {
        if ($anneeScolaire->est_active) {
            $anneeScolaire->deactivate();
            $message = 'Année scolaire désactivée avec succès';
        } else {
            $anneeScolaire->activate();
            $message = 'Année scolaire activée avec succès';
        }

        return response()->json([
            'message' => $message,
            'annee_scolaire' => $anneeScolaire->fresh(),
        ]);
    }

    /**
     * Generate a new school year based on a previous one (duplicates classes without students).
     */
    public function generer(Request $request): JsonResponse
    {
        $request->validate([
            'annee_precedente_id' => ['required', 'exists:annee_scolaires,id'],
            'code' => ['required', 'string', 'max:20', 'unique:annee_scolaires,code'],
            'libelle' => ['required', 'string', 'max:100'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
        ]);

        $precedente = AnneeScolaire::findOrFail($request->annee_precedente_id);
        $service = app(TransitionScolaireService::class);

        $nouvelleAnnee = $service->genererNouvelleAnnee($precedente, $request->only([
            'code', 'libelle', 'date_debut', 'date_fin',
        ]));

        return response()->json([
            'message' => 'Nouvelle année scolaire générée avec succès.',
            'annee_scolaire' => $nouvelleAnnee->loadCount('classes'),
        ], 201);
    }

    /**
     * Get statistics for a specific school year.
     */
    public function statistiques(AnneeScolaire $anneeScolaire): JsonResponse
    {
        $inscriptions = Inscription::withoutGlobalScopes()
            ->where('annee_scolaire_id', $anneeScolaire->id);

        $stats = [
            'annee_scolaire' => $anneeScolaire,
            'total_inscriptions' => (clone $inscriptions)->count(),
            'par_statut_academique' => [
                'en_cours' => (clone $inscriptions)->where('statut_academique', 'en_cours')->count(),
                'admis' => (clone $inscriptions)->where('statut_academique', 'admis')->count(),
                'redouble' => (clone $inscriptions)->where('statut_academique', 'redouble')->count(),
                'transfere' => (clone $inscriptions)->where('statut_academique', 'transfere')->count(),
                'abandonne' => (clone $inscriptions)->where('statut_academique', 'abandonne')->count(),
                'exclu' => (clone $inscriptions)->where('statut_academique', 'exclu')->count(),
            ],
            'total_classes' => $anneeScolaire->classes()->count(),
            'total_mouvements' => $anneeScolaire->mouvements()->count(),
        ];

        return response()->json($stats);
    }
}
