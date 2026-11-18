<?php

namespace App\Observers;

use App\Models\Enseignant;
use App\Models\User;
use App\Services\HR\EmployeSyncService;

/**
 * Répercute sur la fiche Employé les changements d'identité d'un utilisateur ou d'un enseignant.
 */
class EmployeSyncObserver
{
    public function __construct(public EmployeSyncService $employeSync) {}

    public function saved(User|Enseignant $model): void
    {
        if ($model instanceof User) {
            if ($model->wasChanged(['name', 'nom', 'prenom', 'email'])) {
                $this->employeSync->syncUser($model);
            }

            return;
        }

        if ($model->user) {
            $this->employeSync->syncUser($model->user);
        }
    }
}
