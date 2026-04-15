<?php

namespace App\Policies;

use App\Models\Enseignant;
use App\Models\User;

class EnseignantPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermissionName([
            'view_data',
            'view_enseignant',
            'view_any_enseignant',
            'manage_teachers',
            'manage_users',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Enseignant $enseignant): bool
    {
        if (! $user->hasAnyPermissionName([
            'view_data',
            'view_enseignant',
            'view_any_enseignant',
            'manage_teachers',
            'manage_users',
        ])) {
            return false;
        }

        // Teachers can view themselves
        if ($user->id === $enseignant->user_id) {
            return true;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($enseignant->school);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // School directors and admins can create teachers
        return $user->hasAnyPermissionName([
            'create_data',
            'create_enseignant',
            'manage_teachers',
            'manage_users',
            'manage_schools',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Enseignant $enseignant): bool
    {
        if (! $user->hasAnyPermissionName([
            'update_data',
            'update_enseignant',
            'manage_teachers',
            'manage_users',
        ])) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($enseignant->school);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Enseignant $enseignant): bool
    {
        if (! $user->hasAnyPermissionName([
            'delete_data',
            'delete_enseignant',
            'manage_teachers',
            'manage_users',
        ])) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($enseignant->school);
    }
}
