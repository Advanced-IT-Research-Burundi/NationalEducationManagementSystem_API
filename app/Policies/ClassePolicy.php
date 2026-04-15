<?php

namespace App\Policies;

use App\Models\Classe;
use App\Models\User;

class ClassePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermissionName([
            'view_data',
            'view_classe',
            'view_any_classe',
            'manage_classes',
            'manage_schools',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Classe $classe): bool
    {
        if (! $user->hasAnyPermissionName([
            'view_data',
            'view_classe',
            'view_any_classe',
            'manage_classes',
            'manage_schools',
        ])) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($classe->school);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // School directors and admins can create classes
        return $user->hasAnyPermissionName([
            'create_data',
            'create_classe',
            'manage_classes',
            'manage_schools',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Classe $classe): bool
    {
        if (! $user->hasAnyPermissionName([
            'update_data',
            'update_classe',
            'manage_classes',
            'manage_schools',
        ])) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($classe->school);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Classe $classe): bool
    {
        if (! $user->hasAnyPermissionName([
            'delete_data',
            'delete_classe',
            'manage_classes',
            'manage_schools',
        ])) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($classe->school);
    }
}
