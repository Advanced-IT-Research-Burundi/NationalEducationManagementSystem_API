<?php

namespace App\Policies;

use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_data');
    }

    public function view(User $user, Section $section): bool
    {
        return $user->hasPermission('view_data');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_data') || $user->hasPermission('manage_schools');
    }

    public function update(User $user, Section $section): bool
    {
        return $user->hasPermission('update_data') || $user->hasPermission('manage_schools');
    }

    public function delete(User $user, Section $section): bool
    {
        return $user->hasPermission('delete_data') || $user->hasPermission('manage_schools');
    }
}
