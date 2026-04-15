<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('access_settings')
            && (
                $user->hasPermission('manage_roles')
                || $user->hasPermission('manage_permissions')
                || $user->hasPermission('manage_users')
                || $user->hasPermission('create_user')
                || $user->hasPermission('update_user')
            );
    }

    public function view(User $user, Role $role): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasPermission('manage_roles') && ! $role->isSystemRole();
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasPermission('manage_roles') && ! $role->isSystemRole();
    }
}
