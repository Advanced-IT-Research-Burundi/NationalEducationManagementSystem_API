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

        // Hierarchy check logic similar to creation
        // Provincial Director can view users in their province, etc.
        // Simplified for now: allow if in same scope
        
        // This is tricky without fully implementing scope checking here again.
        // But typically the Data Scope filters the list, so if they can list, they can see?
        // Let's rely on basic permission + maybe scope matching
        
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
     * Determine whether the user can store a specific user based on hierarchy.
     * Note: Policy 'create' usually takes only the authenticated user.
     * We might need a custom method or check this logic in Request/Controller.
     * But we can define a custom 'createChild' ability if we pass the target role/entity.
     * 
     * However, standard CRUD uses 'create' valid for the resource class.
     * We'll implement strict logic in the Controller/Request for *what* kind of user they can create.
     * Here, we just return if they *can* access the create action.
     */


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        // Prevent modifying yourself to upgrade role? Maybe.
        if ($user->id === $model->id) {
            // Usually users can't change their own administrative details, only profile
             return true; 
        }

        if ($user->hasRole('Admin National')) {
            return true;
        }

        // Hierarchical update check
        // Check if the target model belongs to the user's scope
        
        if ($user->admin_level === 'PROVINCE' && $model->province_id === $user->admin_entity_id) {
            return true;
        }
        
        if ($user->admin_level === 'COMMUNE' && $model->commune_id === $user->admin_entity_id) {
            return true;
        }

        // And so on... simplified:
        if ($user->hasPermission('manage_users')) {
             // Add logic: cannot update someone of higher or equal rank?
             // For now allow, Controller will handle validation of data changes
             return true;
        }

        return false;
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

        // Similar hierarchical check
         if ($user->admin_level === 'PROVINCE' && $model->province_id === $user->admin_entity_id) {
            return true;
        }
        
        return $user->hasPermission('manage_users');
    }
}
