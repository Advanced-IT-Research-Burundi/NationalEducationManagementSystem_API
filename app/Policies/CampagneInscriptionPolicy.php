<?php

namespace App\Policies;

use App\Enums\CampagneStatut;
use App\Models\CampagneInscription;
use App\Models\User;

class CampagneInscriptionPolicy
{
    /**
     * Determine whether the user can view any campagnes.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('view_data') || $user->hasPermission('campagnes.view');
    }

    /**
     * Determine whether the user can view the campagne.
     */
    public function view(User $user, CampagneInscription $campagne): bool
    {
        if (! ($user->hasPermission('view_data') || $user->hasPermission('campagnes.view'))) {
            return false;
        }

        return $this->isInUserScope($user, $campagne);
    }

    /**
     * Determine whether the user can create campagnes.
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('manage_schools') || $user->hasPermission('campagnes.create');
    }

    /**
     * Determine whether the user can update the campagne.
     */
    public function update(User $user, CampagneInscription $campagne): bool
    {
        if (! ($user->hasPermission('manage_schools') || $user->hasPermission('campagnes.update'))) {
            return false;
        }

        if (! $this->isInUserScope($user, $campagne)) {
            return false;
        }

        // Cannot update closed campagnes
        return $campagne->statut !== CampagneStatut::Cloturee;
    }

    /**
     * Determine whether the user can delete the campagne.
     */
    public function delete(User $user, CampagneInscription $campagne): bool
    {
        if (! ($user->hasPermission('manage_schools') || $user->hasPermission('campagnes.delete'))) {
            return false;
        }

        if (! $this->isInUserScope($user, $campagne)) {
            return false;
        }

        // Can only delete planned campagnes with no inscriptions
        return $campagne->statut === CampagneStatut::Planifiee
            && ! $campagne->inscriptions()->exists();
    }

    /**
     * Determine whether the user can open the campagne.
     */
    public function ouvrir(User $user, CampagneInscription $campagne): bool
    {
        if (! ($user->hasPermission('manage_schools') || $user->hasPermission('campagnes.open'))) {
            return false;
        }

        if (! $this->isInUserScope($user, $campagne)) {
            return false;
        }

        return $campagne->canOpen();
    }

    /**
     * Determine whether the user can close the campagne.
     */
    public function cloturer(User $user, CampagneInscription $campagne): bool
    {
        if (! ($user->hasPermission('manage_schools') || $user->hasPermission('campagnes.close'))) {
            return false;
        }

        if (! $this->isInUserScope($user, $campagne)) {
            return false;
        }

        return $campagne->canClose();
    }

    /**
     * Check if campagne is within user's administrative scope.
     */
    private function isInUserScope(User $user, CampagneInscription $campagne): bool
    {
        // National Admin sees everything
        if ($user->role && $user->role->slug === 'admin_national') {
            return true;
        }

        // Load ecole relationship if not loaded
        $campagne->loadMissing('ecole');

        // School-level users
        if ($user->school_id) {
            return $campagne->school_id === $user->school_id;
        }

        // Zone-level users
        if ($user->zone_id && $campagne->ecole) {
            return $campagne->ecole->zone_id === $user->zone_id;
        }

        // Commune-level users
        if ($user->commune_id && $campagne->ecole) {
            return $campagne->ecole->commune_id === $user->commune_id;
        }

        // Province-level users
        if ($user->province_id && $campagne->ecole) {
            return $campagne->ecole->province_id === $user->province_id;
        }

        return false;
    }
}
