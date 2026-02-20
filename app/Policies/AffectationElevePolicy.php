<?php

namespace App\Policies;

use App\Models\AffectationEleve;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AffectationElevePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AffectationEleve $affectationEleve): bool
    {
        return false;
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
    public function update(User $user, AffectationEleve $affectationEleve): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AffectationEleve $affectationEleve): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AffectationEleve $affectationEleve): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AffectationEleve $affectationEleve): bool
    {
        return false;
    }
}
