<?php

namespace Database\Seeders;

use App\Models\CycleScolaire;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Migrating Cycle Seeder ...');

        CycleScolaire::create([
            'nom' => 'Premier Cycle',
            'description' => 'Enseignement Préscolaire',
            'actif' => true,
        ]);

        CycleScolaire::create([
            'nom' => 'Deuxieme Cycle',
            'description' => 'Enseignement Fondamental (9 ans)',
            'actif' => true,
        ]);

        CycleScolaire::create([
            'nom' => 'POST_FONDAMENTAL',
            'description' => 'Enseignement Post-Fondamental (4 ans)',
            'actif' => true,
        ]);
    }
}
