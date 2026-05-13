<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Trimestre;
use App\Services\CurrentAcademicContextService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrimestreController extends Controller
{
    public function __construct(
        private readonly CurrentAcademicContextService $contextService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Trimestre::with('anneeScolaire:id,libelle,code')
            ->ordered();

        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->integer('annee_scolaire_id'));
        }

        return response()->json($query->paginate($request->integer('per_page', 50)));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'annee_scolaire_id' => ['required', 'exists:annee_scolaires,id'],
            'nom' => ['required', 'string', 'max:50'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after_or_equal:date_debut'],
            'verrouille' => ['nullable', 'boolean'],
        ]);

        $trimestre = Trimestre::create([
            ...$validated,
            'actif' => false,
            'verrouille' => (bool) ($validated['verrouille'] ?? false),
        ]);

        return response()->json([
            'message' => 'Trimestre créé avec succès.',
            'data' => $trimestre->load('anneeScolaire:id,libelle,code'),
        ], 201);
    }

    public function show(Trimestre $trimestre): JsonResponse
    {
        return response()->json([
            'data' => $trimestre->load('anneeScolaire:id,libelle,code'),
        ]);
    }

    public function update(Request $request, Trimestre $trimestre): JsonResponse
    {
        $validated = $request->validate([
            'nom' => ['sometimes', 'string', 'max:50'],
            'date_debut' => ['sometimes', 'date'],
            'date_fin' => ['sometimes', 'date', 'after_or_equal:date_debut'],
            'verrouille' => ['nullable', 'boolean'],
        ]);

        $trimestre->update($validated);

        return response()->json([
            'message' => 'Trimestre mis à jour avec succès.',
            'data' => $trimestre->load('anneeScolaire:id,libelle,code'),
        ]);
    }

    public function destroy(Trimestre $trimestre): JsonResponse
    {
        if ($trimestre->evaluations()->exists() || $trimestre->noteConduites()->exists() || $trimestre->sanctions()->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer ce trimestre car il est déjà utilisé.',
            ], 422);
        }

        $trimestre->delete();

        return response()->json(['message' => 'Trimestre supprimé avec succès.']);
    }

    public function toggleLock(Trimestre $trimestre): JsonResponse
    {
        $trimestre->update(['verrouille' => ! $trimestre->verrouille]);

        return response()->json([
            'message' => $trimestre->verrouille
                ? 'Trimestre verrouillé avec succès.'
                : 'Trimestre déverrouillé avec succès.',
            'data' => $trimestre->fresh('anneeScolaire:id,libelle,code'),
        ]);
    }
}
