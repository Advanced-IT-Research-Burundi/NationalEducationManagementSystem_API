<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class PermissionController extends Controller
{
    /**
     * Display a listing of permissions.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = Permission::query()
            ->orderBy('group_name')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json($permissions);
    }

    /**
     * Store a newly created permission.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', Permission::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('permissions', 'name')->where('guard_name', 'api')],
            'description' => ['nullable', 'string'],
            'group_name' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = Permission::create([
            'name' => $validated['name'],
            'guard_name' => 'api',
            'description' => $validated['description'] ?? null,
            'group_name' => $validated['group_name'] ?? null,
            'is_system' => false,
        ]);

        return sendResponse($permission, 'Permission created successfully');
    }

    /**
     * Display the specified permission.
     */
    public function show(string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $this->authorize('view', $permission);

        return response()->json($permission);
    }

    /**
     * Update the specified permission.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $this->authorize('update', $permission);
        $this->ensurePermissionIsMutable($permission);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255', Rule::unique('permissions', 'name')->ignore($permission->id)->where('guard_name', 'api')],
            'description' => ['nullable', 'string'],
            'group_name' => ['nullable', 'string', 'max:255'],
        ]);

        if (array_key_exists('description', $validated)) {
            $permission->description = $validated['description'];
        }

        if (array_key_exists('group_name', $validated)) {
            $permission->group_name = $validated['group_name'];
        }

        $permission->update($validated);

        return sendResponse($permission, 'Permission updated successfully');
    }

    /**
     * Remove the specified permission.
     */
    public function destroy(string $id): JsonResponse
    {
        $permission = Permission::findOrFail($id);
        $this->authorize('delete', $permission);
        $this->ensurePermissionIsMutable($permission);
        $permission->delete();

        return sendResponse(null, 'Permission deleted successfully');
    }

    protected function ensurePermissionIsMutable(Permission $permission): void
    {
        if ($permission->isSystemPermission()) {
            abort(Response::HTTP_FORBIDDEN, 'Cette permission système est verrouillée.');
        }
    }
}
