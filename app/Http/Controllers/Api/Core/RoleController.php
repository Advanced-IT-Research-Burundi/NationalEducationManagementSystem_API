<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::with('permissions')
            ->withCount('users')
            ->orderByDesc('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'api')],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'api')],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'api',
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions')->loadCount('users'), 201);
    }

    /**
     * Display the specified role.
     */
    public function show(string $id): JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);
        $this->authorize('view', $role);

        return response()->json($role->loadCount('users'));
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $this->authorize('update', $role);
        $this->ensureRoleIsMutable($role);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)->where('guard_name', 'api')],
            'description' => ['nullable', 'string'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'api')],
        ]);

        if (isset($validated['name'])) {
            $role->name = $validated['name'];
        }

        if (array_key_exists('description', $validated)) {
            $role->description = $validated['description'];
        }

        $role->guard_name = 'api';
        $role->save();

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions')->loadCount('users'));
    }

    /**
     * Remove the specified role.
     */
    public function destroy(string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $this->authorize('delete', $role);
        $this->ensureRoleIsMutable($role);

        if ($role->users()->exists()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Ce rôle est encore attribué à des utilisateurs.');
        }

        $role->delete();

        return response()->json(null, 204);
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $this->authorize('update', $role);
        $this->ensureRoleIsMutable($role);

        $validated = $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'api')],
        ]);

        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permissions synced successfully',
            'role' => $role->load('permissions')->loadCount('users'),
        ]);
    }

    protected function ensureRoleIsMutable(Role $role): void
    {
        if ($role->isSystemRole()) {
            abort(Response::HTTP_FORBIDDEN, 'Ce rôle système est verrouillé.');
        }
    }
}
