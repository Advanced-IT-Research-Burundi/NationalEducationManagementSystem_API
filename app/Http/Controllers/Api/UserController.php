<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('viewAny', User::class);

        // Apply Data Scope automatically via AdminScope or manually if needed.
        // Since we implemented AdminScope globally, User::all() or User::paginate() 
        // will already be filtered by the logged-in user's administrative level.
        
        $users = User::with(['role', 'creator'])->paginate(15);
        
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        // policy 'create' check is done in request authorize()
        
        $data = $request->validated();
        
        $user = new User();
        $user->fill($data);
        $user->password = Hash::make($data['password']);
        $user->created_by = Auth::id();
        $user->statut = 'actif'; // default
        $user->save();

        if (isset($data['role'])) {
            $user->assignRole($data['role']);
        }

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user->load('role')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $this->authorize('view', $user);
        return response()->json($user->load(['role', 'creator', 'pays', 'ministere', 'province', 'commune', 'zone', 'colline', 'school']));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        // policy 'update' check done in request
        
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
            'user' => $user->load('role')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $this->authorize('delete', $user);
        
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    /**
     * Toggle user status (active/inactive).
     */
    public function toggleStatus(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $user->statut = $user->statut === 'actif' ? 'inactif' : 'actif';
        $user->save();

        return response()->json([
            'message' => 'User status updated',
            'statut' => $user->statut
        ]);
    }

    /**
     * Admin reset password.
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $request->validate(['password' => 'required|string|min:8|confirmed']);

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully']);
    }
}
