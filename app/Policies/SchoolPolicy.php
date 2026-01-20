<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
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
    public function view(User $user, School $school): bool
    {
        // Add scope check if needed, but usually scope filters the query.
        return $user->hasPermission('view_data');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Can create if they have permission. 
        // e.g., Un Directeur d'école usually doesn't create *schools*, but maybe administrative agents do.
        // We'll allow based on generic permission for now.
        return $user->hasPermission('create_data'); // or manage_schools
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User বিষয়টি $user, School $school): bool
    {
        if ($school->statut === 'ACTIVE' && !$user->hasRole('Admin National')) {
             // Maybe restrict editing active schools?
        }
        
        return $user->hasPermission('update_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, School $school): bool
    {
        return $user->hasPermission('delete_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can validate the school.
     */
    public function validate(User $user, School $school): bool
    {
        return $user->hasPermission('validate_data');
    }
}
