<?php

namespace App\Policies;

use App\Models\Formation;
use App\Models\User;

class FormationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_data');
    }

    public function view(User $user, Formation $formation): bool
    {
        return $user->hasPermission('view_data');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('create_data') || $user->hasRole('Admin National');
    }

    public function update(User $user, Formation $formation): bool
    {
        return $user->hasPermission('update_data') || $user->hasRole('Admin National') || $user->id === $formation->formateur_id;
    }

    public function delete(User $user, Formation $formation): bool
    {
        return $user->hasRole('Admin National') || $user->hasPermission('delete_data');
    }

    public function register(User $user, Formation $formation): bool
    {
        // Simple permission: if user can view, they can probably register if there's capacity
        return $user->hasPermission('view_data');
    }
}
