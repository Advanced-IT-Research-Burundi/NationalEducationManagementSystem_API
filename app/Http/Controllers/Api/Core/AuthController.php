<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Build a frontend-friendly authenticated user payload.
     */
    protected function serializeAuthenticatedUser(User $user): array
    {
        $user->synchronizeDirectorSchoolScope();
        $user->loadAuthorizationRelations();

        return array_merge($user->toArray(), 
        [
            'role' => $user->getPrimaryRole()?->toArray(),
            'primary_role' => $user->getPrimaryRole()?->toArray(),
            'authorization' => $user->getAuthorizationSnapshot(),
        ]
    );
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        if ($user->statut !== 'actif') {
            throw ValidationException::withMessages([
                'email' => ['Votre compte est inactif. Contactez l\'administrateur.'],
            ]);
        }

        // Create Token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->serializeAuthenticatedUser($user),
        ]);
    }

    /**
     * Logout user (revoke token).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            "success" => true,
            'message' => 'Déconnexion réussie',
            "data" => null,
            "errors" => null,
        ]);
    }

    /**
     * Get authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($this->serializeAuthenticatedUser($request->user()));
    }

    /**
     * Refresh token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->currentAccessToken()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * List the authenticated user's permissions.
     */
    public function myPermissions(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['roles.permissions', 'permissions']);

        $directPermissions = $user->permissions->keyBy('id');
        $allPermissions = $user->getAllPermissions()
            ->sortBy(['group_name', 'sort_order', 'name'])
            ->values();

        return response()->json([
            'permissions' => $allPermissions->map(fn (Permission $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'label' => $p->label,
                'description' => $p->description,
                'group_name' => $p->group_name,
                'group_label' => $p->group_label,
                'is_system' => $p->is_system,
                'source' => $directPermissions->has($p->id) ? 'direct' : 'role',
            ])->all(),
            'total' => $allPermissions->count(),
        ]);
    }

    /**
     * List the authenticated user's roles.
     */
    public function myRoles(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing('roles.permissions');

        return response()->json([
            'roles' => $user->roles->sortByDesc('sort_order')->values()->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'description' => $r->description,
                'is_system' => $r->is_system,
                'permissions_count' => $r->permissions->count(),
            ])->all(),
            'primary_role' => $user->getPrimaryRole()?->only(['id', 'name', 'slug']),
        ]);
    }
}
