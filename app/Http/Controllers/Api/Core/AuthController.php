<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Build a lean, frontend-friendly authenticated user payload.
     */
    protected function serializeAuthenticatedUser(User $user): array
    {
        $user->loadMissing(['roles.permissions', 'permissions', 'school', 'enseignant.school']);

        $primaryRole = $user->getPrimaryRole();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'statut' => $user->statut,
            'is_super_admin' => $user->is_super_admin,
            'admin_level' => $user->admin_level,
            'admin_entity_id' => $user->admin_entity_id,
            'school_id' => $user->school_id,
            'school' => $user->school ? [
                'id' => $user->school->id,
                'name' => $user->school->name,
            ] : null,
            'enseignant' => $user->enseignant ? [
                'id' => $user->enseignant->id,
                'nom_complet' => $user->enseignant->nom_complet ?? $user->enseignant->nom ?? null,
                'school' => $user->enseignant->school ? [
                    'id' => $user->enseignant->school->id,
                    'name' => $user->enseignant->school->name,
                ] : null,
            ] : null,
            'role' => $primaryRole ? [
                'id' => $primaryRole->id,
                'name' => $primaryRole->name,
            ] : null,
            'primary_role' => $primaryRole ? [
                'id' => $primaryRole->id,
                'name' => $primaryRole->name,
            ] : null,
            'authorization' => $user->getAuthorizationSnapshot(),
        ];
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
            'success' => true,
            'message' => 'Déconnexion réussie',
            'data' => null,
            'errors' => null,
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

        // Delete current token
        $user->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }
}
