<?php

namespace App\Http\Controllers\Api\Schools;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSchoolRequest;
use App\Http\Requests\UpdateSchoolRequest;
use App\Models\Colline;
use App\Models\Enseignant;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SchoolController extends Controller
{
    /**
     * Display a listing of schools.
     */
   public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', School::class);

        $query = School::query()
            ->select([
                'id',
                'code_ecole',
                'name',
                'email',
                'type_ecole',
                'statut',
                'province_id',
                'commune_id',
            ])
            ->with([
                'province:id,name',
                'commune:id,name',
            ]);

        // Filters propres
        $query->when($request->filled('search'), fn ($q) =>
            $q->search($request->search)
        );

        $query->when($request->filled('statut'), fn ($q) =>
            $q->where('statut', $request->statut)
        );

        $query->when($request->filled('type_ecole'), fn ($q) =>
            $q->byType($request->type_ecole)
        );

        $query->when($request->filled('province_id'), fn ($q) =>
            $q->where('province_id', $request->province_id)
        );

        $query->when($request->filled('commune_id'), fn ($q) =>
            $q->where('commune_id', $request->commune_id)
        );

        $query->when($request->filled('zone_id'), fn ($q) =>
            $q->where('zone_id', $request->zone_id)
        );

        $query->when($request->filled('colline_id'), fn ($q) =>
            $q->where('colline_id', $request->colline_id)
        );

        $schools = $query
            ->latest('id')
            ->paginate($request->integer('per_page', 15));

        return sendResponse($schools, 'Schools retrieved successfully.');
    }

    /**
     * Store a newly created school.
     */
    public function store(StoreSchoolRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Auto-localization logic
        $colline = Colline::with(['zone.commune.province.ministere.pays'])->findOrFail($data['colline_id']);

        $data['zone_id'] = $colline->zone_id;
        $data['commune_id'] = $colline->zone->commune_id ?? null;
        $data['province_id'] = $colline->zone->commune->province_id ?? null;
        $data['ministere_id'] = $colline->zone->commune->province->ministere_id ?? null;
        $data['pays_id'] = $colline->zone->commune->province->pays_id ?? 1;

        $data['created_by'] = Auth::id();
        $data['statut'] = School::STATUS_BROUILLON;

        $school = School::create($data);

        if ($request->filled('niveau_scolaire_ids')) {
            $school->niveauxScolaires()->sync($data['niveau_scolaire_ids']);
        }

        return response()->json([
            'message' => 'School created successfully',
            'school' => $school->load('niveauxScolaires'),
        ], 201);
    }

    /**
     * Display the specified school.
     */
    public function show(School $school): JsonResponse
    {
        $this->authorize('view', $school);

        return response()->json($school->load([
            'colline', 'zone', 'commune', 'province',
            'creator', 'validator', 'niveauxScolaires',
            'enseignants.user', 'directeur',
        ]));
    }

    /**
     * Update the specified school.
     */
    public function update(UpdateSchoolRequest $request, School $school): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['colline_id']) && $data['colline_id'] != $school->colline_id) {
            // Re-localize if colline changed
            $colline = Colline::with(['zone.commune.province'])->findOrFail($data['colline_id']);
            $data['zone_id'] = $colline->zone_id;
            $data['commune_id'] = $colline->zone->commune_id;
            $data['province_id'] = $colline->zone->commune->province_id;
        }

        $school->update($data);

        if ($request->has('niveau_scolaire_ids')) {
            $school->niveauxScolaires()->sync($request->niveau_scolaire_ids);
        }

        return response()->json([
            'message' => 'School updated successfully',
            'school' => $school->load('niveauxScolaires'),
        ]);
    }

    /**
     * Remove the specified school.
     */
    public function destroy(School $school): JsonResponse
    {
        $this->authorize('delete', $school);

        $school->delete();

        return response()->json(['message' => 'School deleted successfully']);
    }

    /**
     * Get school statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', School::class);

        $stats = [
            'total' => School::count(),
            'by_status' => [
                'brouillon' => School::draft()->count(),
                'en_attente' => School::pending()->count(),
                'active' => School::active()->count(),
                'inactive' => School::inactive()->count(),
            ],
            'by_type' => School::selectRaw('type_ecole, COUNT(*) as count')
                ->groupBy('type_ecole')
                ->pluck('count', 'type_ecole'),
            'by_niveau' => School::selectRaw('niveau, COUNT(*) as count')
                ->groupBy('niveau')
                ->pluck('count', 'niveau'),
        ];

        return response()->json($stats);
    }

    /**
     * Get schools grouped by status.
     */
    public function byStatus(Request $request): JsonResponse
    {
        $this->authorize('viewAny', School::class);

        $status = $request->get('status', School::STATUS_ACTIVE);

        $schools = School::where('statut', $status)
            ->with(['colline', 'zone', 'commune', 'province'])
            ->paginate($request->get('per_page', 15));

        return response()->json($schools);
    }

    /**
     * Assign or change the director of a school.
     */
    public function assignDirector(Request $request, School $school): JsonResponse
    {
        $this->authorize('update', $school);

        $request->validate([
            'directeur_id' => 'required|exists:users,id',
        ]);

        $user = User::findOrFail($request->directeur_id);

        $school->update([
            'directeur_id' => $user->id,
            'directeur_name' => $user->name,
        ]);

        return response()->json([
            'message' => 'Directeur assigné avec succès',
            'school' => $school->load(['directeur', 'enseignants.user', 'niveauxScolaires']),
        ]);
    }

    /**
     * Assign a user as enseignant to a school.
     */
    public function assignEnseignant(Request $request, School $school): JsonResponse
    {
        $this->authorize('update', $school);

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'matricule' => 'nullable|string|max:50|unique:enseignants,matricule',
        ]);

        $user = User::findOrFail($request->user_id);

        // Check if user already has an enseignant profile globally
        $enseignant = Enseignant::where('user_id', $user->id)->first();

        if ($enseignant) {
            // Check if already linked to this school (using the updated bySchool logic)
            $isLinked = Enseignant::where('id', $enseignant->id)->bySchool($school->id)->exists();

            if ($isLinked) {
                return response()->json([
                    'message' => 'Cet utilisateur est déjà enseignant dans cet établissement.',
                ], 422);
            }

            // Just link them to the new school in the pivot table
            $enseignant->ecoles()->attach($school->id);
        } else {
            // If they are not an enseignant yet, we need a matricule to create the profile.
            // If none provided, we gracefully tell the user.
            if (!$request->filled('matricule')) {
                return response()->json([
                    'message' => "Cet utilisateur n'a pas encore de profil enseignant. Veuillez d'abord le créer dans la gestion des enseignants ou fournir un matricule.",
                ], 422);
            }

            $enseignant = Enseignant::create([
                'user_id' => $user->id,
                'school_id' => $school->id,
                'matricule' => $request->matricule,
                'statut' => Enseignant::STATUS_ACTIF,
                'created_by' => Auth::id(),
            ]);

            $enseignant->ecoles()->attach($school->id);
        }

        if (! $user->hasRole('Enseignant')) {
            $user->assignRole('Enseignant');
        }

        return response()->json([
            'message' => 'Enseignant assigné avec succès',
            'enseignant' => $enseignant->load('user'),
        ], 201);
    }

    /**
     * Remove an enseignant from a school.
     */
    public function removeEnseignant(School $school, Enseignant $enseignant): JsonResponse
    {
        $this->authorize('update', $school);

        $isDirectlyLinked = $enseignant->school_id === $school->id;
        $isPivotLinked = $enseignant->ecoles()->where('schools.id', $school->id)->exists();

        if (!$isDirectlyLinked && !$isPivotLinked) {
            return response()->json([
                'message' => "Cet enseignant n'appartient pas à cet établissement.",
            ], 422);
        }

        if ($enseignant->affectations()->where('statut', 'ACTIVE')->exists()) {
            return response()->json([
                'message' => 'Impossible de retirer cet enseignant car il a des affectations actives (classes/matières).',
            ], 422);
        }

        // 1. Detach from pivot table if linked
        if ($isPivotLinked) {
            $enseignant->ecoles()->detach($school->id);
        }

        // 2. Clear school_id if it was the main school
        if ($isDirectlyLinked) {
            $enseignant->update(['school_id' => null]);
        }

        return response()->json(['message' => 'Enseignant retiré de l’établissement avec succès']);
    }

    /**
     * Get all schools (for dropdowns).
     */
    public function list(): JsonResponse
    {
        $this->authorize('viewAny', School::class);

        $schools = School::orderBy('name')->get(['id', 'name', 'code_ecole']);

        return response()->json($schools);
    }

    /**
     * Get dashboard school data for the authenticated teacher.
     */
    public function getTeacherDashboardData(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing([
            'enseignant.user',
            'enseignant.school.province',
            'enseignant.school.commune',
            'enseignant.school.zone',
            'enseignant.school.colline',
            'enseignant.ecoles.province',
            'enseignant.ecoles.commune',
            'enseignant.ecoles.zone',
            'enseignant.ecoles.colline',
        ]);

        $enseignant = $user->enseignant;

        if (! $enseignant) {
            return response()->json([
                'message' => 'Aucun profil enseignant trouvé pour cet utilisateur.',
                'data' => [
                    'enseignant' => null,
                    'school' => null,
                    'schools' => [],
                ],
            ], 404);
        }

        $schools = collect();

        if ($enseignant->school) {
            $schools->push($enseignant->school);
        }

        foreach ($enseignant->ecoles as $school) {
            if (! $schools->contains(fn ($item) => $item->id === $school->id)) {
                $schools->push($school);
            }
        }

        return response()->json([
            'data' => [
                'enseignant' => $enseignant->loadMissing('user'),
                'school' => $enseignant->school,
                'schools' => $schools->values(),
            ],
        ]);
    }

    /**
     * Get levels associated with a school.
     */
    public function niveaux(School $school): JsonResponse
    {
        return response()->json($school->niveauxScolaires);
    }
}
