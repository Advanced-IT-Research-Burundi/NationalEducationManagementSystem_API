<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\HR\EmployeSyncService;
use Illuminate\Console\Command;

class SyncEmployesFromUsers extends Command
{
    /**
     * @var string
     */
    protected $signature = 'hr:sync-employes';

    /**
     * @var string
     */
    protected $description = 'Crée ou met à jour la fiche Employé (poste, service, département) de chaque utilisateur selon son rôle';

    public function handle(EmployeSyncService $employeSync): int
    {
        $roles = array_keys(EmployeSyncService::ROLE_MAPPING);
        $synced = 0;

        User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', $roles))
            ->orderBy('id')
            ->chunkById(100, function ($users) use ($employeSync, &$synced) {
                foreach ($users as $user) {
                    if ($employeSync->syncUser($user)) {
                        $synced++;
                    }
                }
            });

        $this->info("{$synced} fiche(s) employé synchronisée(s).");

        return self::SUCCESS;
    }
}
