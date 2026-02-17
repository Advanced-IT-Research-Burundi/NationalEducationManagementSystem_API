<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAffectationEnseignantRequest;
use App\Http\Requests\StoreEnseignantRequest;
use App\Http\Requests\UpdateEnseignantRequest;
use App\Models\AffectationEnseignant;
use App\Models\Enseignant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EnseignantController extends Controller
{
    /**
     * Display a listing of enseignants.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Enseignant::class);

        $query = Enseignant::with(['user', 'school', 'creator']);

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // School filter
        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        // Specialité filter
        if ($request->filled('specialite')) {
            $query->bySpecialite($request->specialite);
        }

        // Qualification filter
        if ($request->filled('qualification')) {
            $query->byQualification($request->qualification);
        }

        // Status filter
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        $enseignants = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($enseignants);
    }

    /**
     * Store a newly created enseignant (with user account).
     */
    public function store(StoreEnseignantRequest $request): JsonResponse
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Create user account for the enseignant
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'admin_level' => 'ECOLE',
                'admin_entity_id' => $data['ecole_id'],
                'ecole_id' => $data['ecole_id'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Assign teacher role
            $user->assignRole('Enseignant');

            // Create enseignant profile
            $enseignant = Enseignant::create([
                'user_id' => $user->id,
                'ecole_id' => $data['ecole_id'],
                'matricule' => $data['matricule'],
                'specialite' => $data['specialite'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'annees_experience' => $data['annees_experience'] ?? 0,
                'date_embauche' => $data['date_embauche'] ?? null,
                'telephone' => $data['telephone'] ?? null,
                'statut' => $data['statut'] ?? 'ACTIF',
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Enseignant créé avec succès',
                'enseignant' => $enseignant->load(['user', 'school']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified enseignant.
     */
    public function show(Enseignant $enseignant): JsonResponse
    {
        $this->authorize('view', $enseignant);

        return response()->json(
            $enseignant->load(['user', 'school', 'creator', 'classes', 'affectations.classe'])
        );
    }

    /**
     * Update the specified enseignant.
     */
    public function update(UpdateEnseignantRequest $request, Enseignant $enseignant): JsonResponse
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            // Update user data if provided
            if (isset($data['name']) || isset($data['email'])) {
                $enseignant->user->update([
                    'name' => $data['name'] ?? $enseignant->user->name,
                    'email' => $data['email'] ?? $enseignant->user->email,
                ]);
            }

            // Update enseignant profile
            $enseignant->update([
                'matricule' => $data['matricule'] ?? $enseignant->matricule,
                'specialite' => $data['specialite'] ?? $enseignant->specialite,
                'qualification' => $data['qualification'] ?? $enseignant->qualification,
                'annees_experience' => $data['annees_experience'] ?? $enseignant->annees_experience,
                'date_embauche' => $data['date_embauche'] ?? $enseignant->date_embauche,
                'telephone' => $data['telephone'] ?? $enseignant->telephone,
                'statut' => $data['statut'] ?? $enseignant->statut,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Enseignant mis à jour avec succès',
                'enseignant' => $enseignant->load(['user', 'school']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified enseignant.
     */
    public function destroy(Enseignant $enseignant): JsonResponse
    {
        $this->authorize('delete', $enseignant);

        // Check if enseignant has active affectations
        if ($enseignant->affectations()->where('statut', 'ACTIVE')->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cet enseignant car il a des affectations actives.',
            ], 422);
        }

        $enseignant->delete();

        return response()->json(['message' => 'Enseignant supprimé avec succès']);
    }

    /**
     * Get enseignants for a specific school.
     */
    public function bySchool(Request $request, int $schoolId): JsonResponse
    {
        $query = Enseignant::bySchool($schoolId)->with('user');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        } else {
            $query->actif();
        }

        $enseignants = $query->get();

        return response()->json($enseignants);
    }

    /**
     * Get classes assigned to an enseignant.
     */
    public function classes(Enseignant $enseignant): JsonResponse
    {
        $this->authorize('view', $enseignant);

        $classes = $enseignant->getActiveClasses();

        return response()->json($classes);
    }

    /**
     * Assign an enseignant to a classe.
     */
    public function assignToClasse(StoreAffectationEnseignantRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();

        // Check if enseignant can be assigned
        $enseignant = Enseignant::findOrFail($data['enseignant_id']);
        if (! $enseignant->canBeAssigned()) {
            return response()->json([
                'message' => 'Cet enseignant ne peut pas être affecté (statut: ' . $enseignant->statut . ').',
            ], 422);
        }

        // Check for existing active assignment
        $exists = AffectationEnseignant::where('enseignant_id', $data['enseignant_id'])
            ->where('classe_id', $data['classe_id'])
            ->where('matiere', $data['matiere'] ?? null)
            ->where('statut', 'ACTIVE')
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Cet enseignant est déjà affecté à cette classe pour cette matière.',
            ], 422);
        }

        $affectation = AffectationEnseignant::create($data);

        return response()->json([
            'message' => 'Enseignant affecté avec succès',
            'affectation' => $affectation->load(['enseignant.user', 'classe']),
        ], 201);
    }

    /**
     * Remove an enseignant from a classe.
     */
    public function removeFromClasse(AffectationEnseignant $affectation): JsonResponse
    {
        $this->authorize('delete', $affectation);

        if (! $affectation->canTerminate()) {
            return response()->json([
                'message' => 'Cette affectation ne peut pas être terminée.',
            ], 422);
        }

        $affectation->terminate();

        return response()->json(['message' => 'Affectation terminée avec succès']);
    }

    /**
     * Get enseignant statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Enseignant::class);

        $query = Enseignant::query();

        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        $stats = [
            'total' => $query->count(),
            'by_status' => [
                'actif' => (clone $query)->actif()->count(),
                'inactif' => (clone $query)->where('statut', 'INACTIF')->count(),
                'conge' => (clone $query)->where('statut', 'CONGE')->count(),
            ],
            'by_qualification' => (clone $query)
                ->selectRaw('qualification, COUNT(*) as count')
                ->groupBy('qualification')
                ->pluck('count', 'qualification'),
        ];

        return response()->json($stats);
    }
}
