<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\CollineResource;
use App\Http\Resources\CommuneResource;
use App\Http\Resources\MinistereResource;
use App\Http\Resources\PaysResource;
use App\Http\Resources\ProvinceResource;
use App\Http\Resources\SchoolResource;
use App\Http\Resources\ZoneResource;
use App\Models\Enseignant;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::with(['roles', 'creator', 'enseignant']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->role($request->input('role'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('school_id')) {
            $query->where('school_id', $request->input('school_id'));
        }

        $users = $query->paginate($request->get('per_page', 15));
        $users->getCollection()->transform(function (User $user) {
            return tap($user)->setAttribute('is_super_admin', $user->isSuperAdmin());
        });

        return sendResponse($users, 'Users retrieved successfully');
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = new User();
        $user->fill($data);
        $user->password = Hash::make($data['password']);
        $user->created_by = Auth::id();
        $user->statut = 'actif';
        $user->save();

        $this->syncUserRoles($user, $data);
        $this->syncSchoolDirectorAssignment($user);
        $this->syncEnseignantProfile($user, $data);

        return response()->json([
            'message' => 'User created successfully',
            'user' => tap($user->load('roles'))->setAttribute('is_super_admin', $user->isSuperAdmin()),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        $user->load([
            'roles',
            'permissions',
            'creator',
            'pays',
            'ministere',
            'province',
            'commune',
            'zone',
            'colline',
            'school',
        ]);

        $payload = $user->toArray();

        $payload['pays'] = $user->relationLoaded('pays') && $user->pays
            ? (new PaysResource($user->pays))->resolve()
            : null;
        $payload['ministere'] = $user->relationLoaded('ministere') && $user->ministere
            ? (new MinistereResource($user->ministere))->resolve()
            : null;
        $payload['province'] = $user->relationLoaded('province') && $user->province
            ? (new ProvinceResource($user->province))->resolve()
            : null;
        $payload['commune'] = $user->relationLoaded('commune') && $user->commune
            ? (new CommuneResource($user->commune))->resolve()
            : null;
        $payload['zone'] = $user->relationLoaded('zone') && $user->zone
            ? (new ZoneResource($user->zone))->resolve()
            : null;
        $payload['colline'] = $user->relationLoaded('colline') && $user->colline
            ? (new CollineResource($user->colline))->resolve()
            : null;
        $payload['school'] = $user->relationLoaded('school') && $user->school
            ? (new SchoolResource($user->school))->resolve()
            : null;

        $payload['role'] = $user->getPrimaryRole()?->toArray();
        $payload['primary_role'] = $user->getPrimaryRole()?->toArray();
        $payload['is_super_admin'] = $user->isSuperAdmin();
        $payload['authorization'] = $user->getAuthorizationSnapshot();

        return response()->json($payload);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $this->ensureNotProtectedUser($user);

        $data = $request->validated();
        $previousSchoolId = $user->school_id;
        $wasSchoolDirector = $user->hasRole(Role::DIRECTEUR_ECOLE);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        $this->syncUserRoles($user, $data);
        $this->syncSchoolDirectorAssignment($user, $previousSchoolId, $wasSchoolDirector);
        $this->syncEnseignantProfile($user, $data);

        return response()->json([
            'message' => 'User updated successfully',
            'user' => tap($user->load('roles'))->setAttribute('is_super_admin', $user->isSuperAdmin()),
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $this->ensureNotProtectedUser($user);

        $this->clearSchoolDirectorAssignment($user->school_id, $user->id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Toggle user status (active/inactive).
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $this->ensureNotProtectedUser($user);

        $user->statut = $user->statut === 'actif' ? 'inactif' : 'actif';
        $user->save();

        return response()->json([
            'message' => 'User status updated',
            'statut' => $user->statut,
        ]);
    }

    /**
     * Admin reset password.
     */
    public function resetPassword(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $this->ensureNotProtectedUser($user);

        $request->validate(['password' => 'required|string|min:8|confirmed']);

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }

    /**
     * Get all users (for dropdowns).
     */
    public function list(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('roles')
            ->where('statut', 'actif')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    protected function syncUserRoles(User $user, array $data): void
    {
        $roles = [];

        if (! empty($data['roles']) && is_array($data['roles'])) {
            $roles = $data['roles'];
        } elseif (! empty($data['role'])) {
            $roles = [$data['role']];
        }

        if ($roles !== []) {
            $user->syncRoles($roles);
            $user->forceFill([
                'is_super_admin' => in_array(Role::SUPER_ADMIN, $roles, true),
            ])->save();
        }
    }

    /**
     * Ensure a user with the Enseignant role has a matching enseignants profile.
     */
    protected function syncEnseignantProfile(User $user, array $data): void
    {
        $user->loadMissing('roles');

        if (! $user->hasRole(Role::ENSEIGNANT)) {
            return;
        }

        if ($user->enseignant()->exists()) {
            return;
        }

        if (empty($data['matricule'])) {
            return;
        }

        $schoolId = ($user->admin_level === 'ECOLE' && $user->admin_entity_id)
            ? $user->admin_entity_id
            : null;

        $enseignant = Enseignant::create([
            'user_id'           => $user->id,
            'school_id'         => $schoolId,
            'matricule'         => $data['matricule'],
            'statut'            => Enseignant::STATUS_ACTIF,
            'annees_experience' => 0,
            'created_by'        => Auth::id(),
        ]);

        if ($schoolId) {
            $enseignant->ecoles()->attach($schoolId);
        }
    }

    protected function syncSchoolDirectorAssignment(
        User $user,
        ?int $previousSchoolId = null,
        bool $wasSchoolDirector = false
    ): void {
        $user->loadMissing('roles');

        if ($wasSchoolDirector && $previousSchoolId && $previousSchoolId !== $user->school_id) {
            $this->clearSchoolDirectorAssignment($previousSchoolId, $user->id);
        }

        if ($wasSchoolDirector && ! $user->hasRole(Role::DIRECTEUR_ECOLE) && $previousSchoolId) {
            $this->clearSchoolDirectorAssignment($previousSchoolId, $user->id);
        }

        if (! $user->hasRole(Role::DIRECTEUR_ECOLE) || ! $user->school_id) {
            return;
        }

        School::withoutGlobalScopes()
            ->whereKey($user->school_id)
            ->update([
                'directeur_id' => $user->id,
                'directeur_name' => $user->name,
            ]);
    }

    protected function clearSchoolDirectorAssignment(?int $schoolId, int $userId): void
    {
        if (! $schoolId) {
            return;
        }

        School::withoutGlobalScopes()
            ->whereKey($schoolId)
            ->where('directeur_id', $userId)
            ->update([
                'directeur_id' => null,
                'directeur_name' => null,
            ]);
    }

    /**
     * Sync direct permissions for a specific user (admin action).
     */
    public function syncPermissions(Request $request, User $user): JsonResponse
    {
        $this->authorize('syncPermissions', $user);
        $this->ensureNotProtectedUser($user);

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'api')],
        ]);

        $user->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permissions utilisateur mises à jour.',
            'user' => tap($user->load(['roles', 'permissions']))->setAttribute('is_super_admin', $user->isSuperAdmin()),
        ]);
    }

    /**
     * Sync roles for a specific user (admin action).
     */
    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $this->authorize('syncRoles', $user);
        $this->ensureNotProtectedUser($user);

        $validated = $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where('guard_name', 'api')],
        ]);

        $previousSchoolId = $user->school_id;
        $wasSchoolDirector = $user->hasRole(Role::DIRECTEUR_ECOLE);

        $user->syncRoles($validated['roles']);
        $user->forceFill([
            'is_super_admin' => in_array(Role::SUPER_ADMIN, $validated['roles'], true),
        ])->save();

        $this->syncSchoolDirectorAssignment($user, $previousSchoolId, $wasSchoolDirector);

        return response()->json([
            'message' => 'Rôles utilisateur mis à jour.',
            'user' => tap($user->load(['roles', 'permissions']))->setAttribute('is_super_admin', $user->isSuperAdmin()),
        ]);
    }

    protected function ensureNotProtectedUser(User $user): void
    {
        if ($user->isSuperAdmin() && ! request()->user()?->isSuperAdmin()) {
            abort(Response::HTTP_FORBIDDEN, 'Le compte supAdmin (sudo) est protégé et ne peut pas être modifié via cette interface.');
        }
    }
}
