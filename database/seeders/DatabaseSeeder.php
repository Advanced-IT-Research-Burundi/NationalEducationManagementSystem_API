<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {


        User::updateOrCreate(
            ['email' => 'nijeanlionel@gmail.com'],
            [
                'name' => 'Jean Lionel',
                'password' => Hash::make('Advanced2026'),
                'email_verified_at' => now(),
            ]
        );


        $this->call([
            PaysSeeder::class,
            BurundiAdministrativeDivisionsSeeder::class,
            RolesAndPermissionsSeeder::class,
            // BurundiSchoolsSeeder::class,
            // AnneeScolaireSeeder::class,
            // NiveauSeeder::class,
            // ClasseSeeder::class,
            // EleveSeeder::class,
            // MouvementEleveSeeder::class,
            // SectionSeeder::class,
            // BatimentSeeder::class,
            // MatiereSeeder::class
        ]);
    }
}
