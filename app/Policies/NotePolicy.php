<?php

namespace App\Policies;

use App\Models\Note;
use App\Models\Role;
use App\Models\User;

class NotePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        if ($user->hasRole(Role::PARENT)) {
            return true;
        }

        return $user->hasAnyPermissionName([
            'view_data',
            'view_note',
            'view_any_note',
            'manage_grades',
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Note $note): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->hasRole(Role::PARENT)) {
            return $user->isLinkedParentOfEleve((int) $note->eleve_id);
        }

        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Note $note): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Note $note): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Note $note): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Note $note): bool
    {
        return false;
    }
}
