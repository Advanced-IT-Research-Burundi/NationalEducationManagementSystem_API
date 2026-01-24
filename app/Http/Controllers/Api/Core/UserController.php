<?php

namespace App\Http\Controllers\Api\Core;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $users = User::with(['roles', 'creator'])->paginate(15);

        return response()->json($users);
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

        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('roles'),
        ], 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json($user->load([
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
        ]));
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user->load('roles'),
        ]);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Toggle user status (active/inactive).
     */
    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

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

        $request->validate(['password' => 'required|string|min:8|confirmed']);

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }
}
