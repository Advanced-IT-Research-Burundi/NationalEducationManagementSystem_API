<?php

namespace Database\Seeders;

use App\Models\CycleScolaire;
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
            'nom' => 'cycle 1',
            'type_id' => 1,
            'description' => 'Enseignement fondamental (1-2)',
            'actif' => true,
        ]);

        CycleScolaire::create([
            'nom' => 'cycle 2',
            'type_id' => 1,
            'description' => 'Enseignement fondamental (3-4)',
            'actif' => true,
        ]);

        CycleScolaire::create([
            'nom' => 'cycle 3',
            'type_id' => 1,
            'description' => 'Enseignement fondamental (5-6)',
            'actif' => true,
        ]);
        CycleScolaire::create([
            'nom' => 'cycle 4',
            'type_id' => 1,
            'description' => 'Enseignement fondamental (7-8)',
            'actif' => true,
        ]);

        CycleScolaire::create([
            'nom' => 'cycle 5',
            'type_id' => 2,
            'description' => 'Enseignement secondaire (1er-4eme)',
            'actif' => true,
        ]);
    }
}
