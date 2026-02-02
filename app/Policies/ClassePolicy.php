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
        return $user->hasPermission('view_data');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Classe $classe): bool
    {
        if (! $user->hasPermission('view_data')) {
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
        return $user->hasPermission('create_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Classe $classe): bool
    {
        if (! ($user->hasPermission('update_data') || $user->hasPermission('manage_schools'))) {
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
        if (! ($user->hasPermission('delete_data') || $user->hasPermission('manage_schools'))) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($classe->school);
    }
}
