<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of roles.
     */
    public function index(): JsonResponse
    {
        $roles = Role::with('permissions')->withCount('users')->get();

        return response()->json($roles);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    /**
     * Display the specified role.
     */
    public function show(string $id): JsonResponse
    {
        $role = Role::with('permissions')->findOrFail($id);

        return response()->json($role);
    }

    /**
     * Update the specified role.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
        ]);

        if (isset($validated['name'])) {
            $role->name = $validated['name'];
            $role->save();
        }

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions'));
    }

    /**
     * Remove the specified role.
     */
    public function destroy(string $id): JsonResponse
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return response()->json(null, 204);
    }

    /**
     * Sync permissions for a role.
     */
    public function syncPermissions(Request $request, string $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'permissions' => 'required|array',
        ]);

        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'message' => 'Permissions synced successfully',
            'role' => $role->load('permissions'),
        ]);
    }
}
