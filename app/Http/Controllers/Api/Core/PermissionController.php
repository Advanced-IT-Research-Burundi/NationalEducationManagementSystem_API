<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::all();

        return response()->json($permissions);
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $validated['name'], 'guard_name' => 'web']);

        return sendResponse($permission, 'Permission created successfully');
    }

    /**
     * Display the specified permission.
     */
    public function show(string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);

        return response()->json($permission);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update($validated);

        return sendResponse($permission, 'Permission updated successfully');
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        return sendResponse(null, 'Permission deleted successfully');
    }
}
