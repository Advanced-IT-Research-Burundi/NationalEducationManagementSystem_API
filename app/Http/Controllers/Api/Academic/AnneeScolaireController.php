<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnneeScolaireRequest;
use App\Http\Requests\UpdateAnneeScolaireRequest;
use App\Models\AnneeScolaire;
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

        return response()->json($anneesScolaires);
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
}
