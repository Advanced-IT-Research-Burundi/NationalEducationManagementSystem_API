<?php

namespace App\Policies;

use App\Models\InscriptionEleve;
use App\Models\User;

class InscriptionElevePolicy
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
    public function view(User $user, InscriptionEleve $inscription): bool
    {
        if (! $user->hasPermission('view_data')) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($inscription->classe->school);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // School staff and teachers can enroll students
        return $user->hasPermission('create_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, InscriptionEleve $inscription): bool
    {
        if (! ($user->hasPermission('update_data') || $user->hasPermission('manage_schools'))) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($inscription->classe->school);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, InscriptionEleve $inscription): bool
    {
        if (! ($user->hasPermission('delete_data') || $user->hasPermission('manage_schools'))) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($inscription->classe->school);
    }
}
