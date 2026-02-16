<?php

namespace App\Policies;

use App\Models\Maintenance;
use App\Models\User;

class MaintenancePolicy
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
    public function view(User $user, Maintenance $maintenance): bool
    {
        return $user->hasPermission('view_data');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('create_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Maintenance $maintenance): bool
    {
        return $user->hasPermission('update_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Maintenance $maintenance): bool
    {
        return $user->hasPermission('delete_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Maintenance $maintenance): bool
    {
        return $user->hasPermission('delete_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Maintenance $maintenance): bool
    {
        return $user->hasPermission('delete_data') || $user->hasPermission('manage_schools');
    }
}
