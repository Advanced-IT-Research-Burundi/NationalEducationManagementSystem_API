<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_data');
    }

    public function view(User $user, Inspection $inspection): bool
    {
        if ($user->hasRole('Admin National')) {
            return true;
        }

        return $user->id === $inspection->inspecteur_id || $user->canAccessSchool($inspection->ecole);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_data');
    }

    public function update(User $user, Inspection $inspection): bool
    {
        return $user->hasRole('Admin National') || $user->id === $inspection->inspecteur_id;
    }

    public function delete(User $user, Inspection $inspection): bool
    {
        return $user->hasRole('Admin National') || $user->hasPermission('delete_data');
    }

    public function validate(User $user, Inspection $inspection): bool
    {
        return $user->hasPermission('validate_data');
    }
}
