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
     * Build a frontend-friendly authenticated user payload.
     */
    protected function serializeAuthenticatedUser(User $user): array
    {
        $user->loadAuthorizationRelations();

        return array_merge($user->toArray(), [
            'role' => $user->getPrimaryRole()?->toArray(),
            'primary_role' => $user->getPrimaryRole()?->toArray(),
            'authorization' => $user->getAuthorizationSnapshot(),
        ]);
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
