<?php

namespace App\Policies;

use App\Models\StandardQualite;
use App\Models\User;

class StandardQualitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_data');
    }

    public function view(User $user, StandardQualite $standardQualite): bool
    {
        return $user->hasPermission('view_data');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Admin National') || $user->hasPermission('manage_system_config');
    }

    public function update(User $user, StandardQualite $standardQualite): bool
    {
        return $user->hasRole('Admin National') || $user->hasPermission('manage_system_config');
    }

    public function delete(User $user, StandardQualite $standardQualite): bool
    {
        return $user->hasRole('Admin National') || $user->hasPermission('manage_system_config');
    }
}
