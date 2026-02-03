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

        User::factory()->create([
            'name' => 'Jean Lionel',
            'email' => 'nijeanlionel@gmail.com',
            'password' => Hash::make('Advanced2026'),
        ]);

        $this->call([
            PaysSeeder::class,
            BurundiAdministrativeDivisionsSeeder::class,
            RolesAndPermissionsSeeder::class,
            BurundiSchoolsSeeder::class,
        ]);
    }
}
