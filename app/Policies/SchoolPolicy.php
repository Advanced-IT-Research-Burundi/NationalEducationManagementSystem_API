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
        // AdminScope will filter, but we can add extra checks
        return $user->hasPermission('view_data');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Typically, commune/zone/province level admins can create schools
        return $user->hasPermission('create_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, School $school): bool
    {
        // Cannot edit active schools unless you're Admin National
        if ($school->statut === School::STATUS_ACTIVE && !$user->hasRole('Admin National')) {
            return false;
        }
        
        return $user->hasPermission('update_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, School $school): bool
    {
        // Only Admin National can delete schools, or those with explicit permission
        return $user->hasRole('Admin National') 
            || $user->hasPermission('delete_data') 
            || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can submit a school for validation.
     * DRAFT → PENDING_VALIDATION
     */
    public function submit(User $user, School $school): bool
    {
        // Can submit if they can update and school is in DRAFT
        if ($school->statut !== School::STATUS_BROUILLON) {
            return false;
        }

        return $user->hasPermission('update_data') || $user->hasPermission('manage_schools');
    }

    /**
     * Determine whether the user can validate (activate) a school.
     * PENDING_VALIDATION → ACTIVE
     */
    public function validate(User $user, School $school): bool
    {
        // Must have validate_data permission
        if (!$user->hasPermission('validate_data')) {
            return false;
        }

        // School must be in pending validation status
        if ($school->statut !== School::STATUS_EN_ATTENTE_VALIDATION) {
            return false;
        }

        // Hierarchical validation:
        // - Admin National can validate any school
        // - Provincial Director can validate schools in their province
        // - Communal Officer can validate schools in their commune
        
        if ($user->hasRole('Admin National') || $user->admin_level === 'PAYS') {
            return true;
        }

        // Provincial level validation
        if ($user->admin_level === 'PROVINCE' && $user->admin_entity_id === $school->province_id) {
            return true;
        }

        // Communal level validation
        if ($user->admin_level === 'COMMUNE' && $user->admin_entity_id === $school->commune_id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can deactivate a school.
     * ACTIVE → INACTIVE
     */
    public function deactivate(User $user, School $school): bool
    {
        // Must be active to deactivate
        if ($school->statut !== School::STATUS_ACTIVE) {
            return false;
        }

        // Admin National or Provincial Director can deactivate
        if ($user->hasRole('Admin National') || $user->admin_level === 'PAYS') {
            return true;
        }

        if ($user->admin_level === 'PROVINCE' && $user->admin_entity_id === $school->province_id) {
            return true;
        }

        return false;
    }
}
