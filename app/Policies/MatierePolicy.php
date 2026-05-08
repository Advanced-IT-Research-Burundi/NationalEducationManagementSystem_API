<?php

namespace App\Policies;

use App\Models\Matiere;
use App\Models\User;

class MatierePolicy
{
    /**
     * Teachers may view cours for grading, but must not create / update / delete them.
     * CUD is reserved to roles with explicit matière rights or class/school management.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermissionName([
            'view_data',
            'view_matiere',
            'view_any_matiere',
            'manage_grades',
            'manage_classes',
            'manage_schools',
        ]);
    }

    public function view(User $user, Matiere $matiere): bool
    {
        return $user->hasAnyPermissionName([
            'view_data',
            'view_matiere',
            'view_any_matiere',
            'manage_grades',
            'manage_classes',
            'manage_schools',
        ]);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyPermissionName([
            'create_matiere',
            'create_data',
            'manage_classes',
            'manage_schools',
        ]);
    }

    public function update(User $user, Matiere $matiere): bool
    {
        return $user->hasAnyPermissionName([
            'update_matiere',
            'update_data',
            'manage_classes',
            'manage_schools',
        ]);
    }

    public function delete(User $user, Matiere $matiere): bool
    {
        return $user->hasAnyPermissionName([
            'delete_matiere',
            'delete_data',
            'manage_classes',
            'manage_schools',
        ]);
    }
}
