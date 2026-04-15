<?php

namespace App\Policies;

use App\Models\Eleve;
use App\Models\User;

class ElevePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermissionName([
            'view_data',
            'view_eleve',
            'view_any_eleve',
            'manage_students',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Eleve $eleve): bool
    {
        if (! $user->hasAnyPermissionName([
            'view_data',
            'view_eleve',
            'view_any_eleve',
            'manage_students',
        ])) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($eleve->school);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermissionName([
            'create_data',
            'create_eleve',
            'manage_students',
            'manage_schools',
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Eleve $eleve): bool
    {
        if (! $user->hasAnyPermissionName([
            'update_data',
            'update_eleve',
            'manage_students',
            'manage_schools',
        ])) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($eleve->school);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Eleve $eleve): bool
    {
        if (! $user->hasAnyPermissionName([
            'delete_data',
            'delete_eleve',
            'manage_students',
            'manage_schools',
        ])) {
            return false;
        }

        // Check if user can access this school's data
        return $user->canAccessSchool($eleve->school);
    }
}
