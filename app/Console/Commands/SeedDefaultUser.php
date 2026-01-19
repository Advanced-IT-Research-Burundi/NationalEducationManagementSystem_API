<?php

namespace App\Console\Commands;

use App\Models\Ministere;
use App\Models\Pays;
use App\Models\User;
use Illuminate\Console\Command;

class SeedDefaultUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:seed-default-user';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        // User::create([
        //     'name' => 'Jean Lionel',
        //     'email' => 'nijeanlionel@gmail.com',
        //     'password' => bcrypt('Advanced2026'),
        //     'role' => 'admin_national',
        // ]);

        // Ministere::create([
        //     'name' => 'Ministere de l\'Education Nationale',
        //     'code' => 'MEN',
        //     'pays_id' => null,
        // ]);

        Pays::create([
            'name' => 'Burundi',
            'code' => 'BI',
        ]);
    }
}
