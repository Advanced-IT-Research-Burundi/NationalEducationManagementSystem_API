<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('manage_users') || $user->hasPermission('view_data');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->hasRole('Admin National')) {
            return true;
        }

        // Own profile
        if ($user->id === $model->id) {
            return true;
        }

        // School-level user can only view users in their school
        if ($user->admin_level === 'ECOLE') {
            return $model->school_id === $user->admin_entity_id;
        }

        // Zone-level user can only view users in their zone
        if ($user->admin_level === 'ZONE') {
            return $model->zone_id === $user->admin_entity_id;
        }

        // Commune-level user can only view users in their commune
        if ($user->admin_level === 'COMMUNE') {
            return $model->commune_id === $user->admin_entity_id;
        }

        // Province-level user can only view users in their province
        if ($user->admin_level === 'PROVINCE') {
            return $model->province_id === $user->admin_entity_id;
        }

        return $user->hasPermission('manage_users');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_users');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Own profile - users can update their own basic info
        if ($user->id === $model->id) {
            return true;
        }

        if ($user->hasRole('Admin National')) {
            return true;
        }

        // School-level user can only update users in their school
        if ($user->admin_level === 'ECOLE') {
            return $model->school_id === $user->admin_entity_id
                && $user->hasPermission('manage_users');
        }

        // Zone-level user can only update users in their zone
        if ($user->admin_level === 'ZONE') {
            return $model->zone_id === $user->admin_entity_id
                && $user->hasPermission('manage_users');
        }

        // Commune-level user can only update users in their commune
        if ($user->admin_level === 'COMMUNE') {
            return $model->commune_id === $user->admin_entity_id
                && $user->hasPermission('manage_users');
        }

        // Province-level user can only update users in their province
        if ($user->admin_level === 'PROVINCE') {
            return $model->province_id === $user->admin_entity_id
                && $user->hasPermission('manage_users');
        }

        return $user->hasPermission('manage_users');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->id === $model->id) {
            return false; // Cannot delete self
        }

        if ($user->hasRole('Admin National')) {
            return true;
        }

        // School-level user can only delete users in their school
        if ($user->admin_level === 'ECOLE') {
            return $model->school_id === $user->admin_entity_id
                && $user->hasPermission('manage_users');
        }

        // Zone-level user can only delete users in their zone
        if ($user->admin_level === 'ZONE') {
            return $model->zone_id === $user->admin_entity_id
                && $user->hasPermission('manage_users');
        }

        // Commune-level user can only delete users in their commune
        if ($user->admin_level === 'COMMUNE') {
            return $model->commune_id === $user->admin_entity_id
                && $user->hasPermission('manage_users');
        }

        // Province-level user can only delete users in their province
        if ($user->admin_level === 'PROVINCE') {
            return $model->province_id === $user->admin_entity_id
                && $user->hasPermission('manage_users');
        }

        return $user->hasPermission('manage_users');
    }
}
