<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClasseRequest;
use App\Http\Requests\UpdateClasseRequest;
use App\Models\Classe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClasseController extends Controller
{
    /**
     * Display a listing of classes.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Classe::class);

        $query = Classe::with(['niveau', 'school', 'creator']);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // School filter
        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        // Niveau filter
        if ($request->filled('niveau_id')) {
            $query->byNiveau($request->niveau_id);
        }

        // Année scolaire filter
        if ($request->filled('annee_scolaire')) {
            $query->byAnneeScolaire($request->annee_scolaire);
        }

        // Status filter
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $classes = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($classes);
    }

    /**
     * Store a newly created classe.
     */
    public function store(StoreClasseRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        $classe = Classe::create($data);

        return response()->json([
            'message' => 'Classe créée avec succès',
            'classe' => $classe->load(['niveau', 'school']),
        ], 201);
    }

    /**
     * Display the specified classe.
     */
    public function show(Classe $classe): JsonResponse
    {
        $this->authorize('view', $classe);

        return response()->json(
            $classe->load(['niveau', 'school', 'creator', 'enseignants', 'eleves'])
        );
    }

    /**
     * Update the specified classe.
     */
    public function update(UpdateClasseRequest $request, Classe $classe): JsonResponse
    {
        $classe->update($request->validated());

        return response()->json([
            'message' => 'Classe mise à jour avec succès',
            'classe' => $classe->load(['niveau', 'school']),
        ]);
    }

    /**
     * Remove the specified classe.
     */
    public function destroy(Classe $classe): JsonResponse
    {
        $this->authorize('delete', $classe);

        // Check if classe has inscriptions
        if ($classe->inscriptions()->where('statut', 'ACTIVE')->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cette classe car elle contient des élèves inscrits.',
            ], 422);
        }

        $classe->delete();

        return response()->json(['message' => 'Classe supprimée avec succès']);
    }

    /**
     * Get classes for a specific school.
     */
    public function bySchool(Request $request, int $schoolId): JsonResponse
    {
        $query = Classe::bySchool($schoolId)->with(['niveau']);

        if ($request->filled('annee_scolaire')) {
            $query->byAnneeScolaire($request->annee_scolaire);
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        } else {
            $query->active();
        }

        $classes = $query->get();

        return response()->json($classes);
    }

    /**
     * Get enseignants assigned to a classe.
     */
    public function enseignants(Classe $classe): JsonResponse
    {
        $this->authorize('view', $classe);

        $enseignants = $classe->enseignants()
            ->wherePivot('statut', 'ACTIVE')
            ->with('user')
            ->get();

        return response()->json($enseignants);
    }

    /**
     * Get élèves enrolled in a classe.
     */
    public function eleves(Classe $classe): JsonResponse
    {
        $this->authorize('view', $classe);

        $eleves = $classe->eleves()
            ->wherePivot('statut', 'ACTIVE')
            ->get();

        return response()->json($eleves);
    }

    /**
     * Get classe statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Classe::class);

        $query = Classe::query();

        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('annee_scolaire')) {
            $query->byAnneeScolaire($request->annee_scolaire);
        }

        $stats = [
            'total' => $query->count(),
            'by_status' => [
                'active' => (clone $query)->active()->count(),
                'inactive' => (clone $query)->where('statut', 'INACTIVE')->count(),
                'archivee' => (clone $query)->where('statut', 'ARCHIVEE')->count(),
            ],
            'by_niveau' => (clone $query)
                ->selectRaw('niveau_id, COUNT(*) as count')
                ->groupBy('niveau_id')
                ->with('niveau:id,nom')
                ->get()
                ->pluck('count', 'niveau.nom'),
        ];

        return response()->json($stats);
    }
}
