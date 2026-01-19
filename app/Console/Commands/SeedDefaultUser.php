<?php

namespace App\Console\Commands;

use App\Models\Commune;
use App\Models\Ministere;
use App\Models\Pays;
use App\Models\Province;
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

        // Pays::create([
        //     'name' => 'Burundi',
        //     'code' => 'BI',
        // ]);

        // $provinces = [
        //     'Bujumbura Mairie',
        //     'Bujumbura Rural',
        //     'Bubanza',
        //     'Bururi',
        //     'Cankuzo',
        //     'Cibitoke',
        //     'Gitega',
        //     'Karuzi',
        //     'Kayanza',
        //     'Kirundo',
        //     'Makamba',
        //     'Muramvya',
        //     'Muyinga',
        //     'Ngozi',
        //     'Rutana',
        //     'Ruyigi',
        // ];

        // foreach ($provinces as $province) {
        //     Province::create([
        //         'name' => $province,
        //         'ministere_id' => 3,
        //         'pays_id' => 1,
        //     ]);
        // }

        $communes = [
            'Bujumbura Mairie',
            'Bujumbura Rural',
            'Bubanza',
            'Bururi',
            'Cankuzo',
            'Cibitoke',
            'Gitega',
            'Karuzi',
            'Kayanza',
            'Kirundo',
            'Makamba',
            'Muramvya',
            'Muyinga',
            'Ngozi',
            'Rutana',
            'Ruyigi',
        ];

        foreach ($communes as $commune) {
            Commune::create([
                'name' => $commune,
                'province_id' => 3,
            ]);
        }
    }
}
