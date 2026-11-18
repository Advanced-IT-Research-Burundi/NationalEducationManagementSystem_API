<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\HR\EmployeSyncService;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

class SyncEmployeOnRoleChange
{
    public function __construct(public EmployeSyncService $employeSync) {}

    public function handle(RoleAttached|RoleDetached $event): void
    {
        if ($event->model instanceof User) {
            $this->employeSync->syncUser($event->model);
        }
    }
}
