<?php

namespace App\Policies;

use App\Models\Permission;
use App\Models\User;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('access_settings')
            && ($user->hasPermission('manage_permissions') || $user->hasPermission('manage_roles'));
    }

    public function view(User $user, Permission $permission): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('manage_permissions');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->hasPermission('manage_permissions') && ! $permission->isSystemPermission();
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasPermission('manage_permissions') && ! $permission->isSystemPermission();
    }
}
