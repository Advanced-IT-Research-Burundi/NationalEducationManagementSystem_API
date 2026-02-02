<?php

namespace App\Policies;

use App\Models\AffectationEnseignant;
use App\Models\User;

class AffectationEnseignantPolicy
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
    public function view(User $user, AffectationEnseignant $affectation): bool
    {
        if (! $user->hasPermission('view_data')) {
            return false;
        }

        // Teachers can view their own affectations
        if ($user->id === $affectation->enseignant->user_id) {
            return true;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($affectation->classe->school);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // School directors and admins can assign teachers
        return $user->hasPermission('create_data')
            || $user->hasPermission('manage_users')
            || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AffectationEnseignant $affectation): bool
    {
        if (! ($user->hasPermission('update_data') || $user->hasPermission('manage_schools'))) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($affectation->classe->school);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AffectationEnseignant $affectation): bool
    {
        if (! ($user->hasPermission('delete_data') || $user->hasPermission('manage_schools'))) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($affectation->classe->school);
    }
}
