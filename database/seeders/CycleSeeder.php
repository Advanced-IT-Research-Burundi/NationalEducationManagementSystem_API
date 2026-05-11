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
            'nom' => 'Cycle 1',
            'description' => 'Enseignement fondamental (1-2ème année)',
            'type_id' => 2,
            'actif' => true,
        ]);

        CycleScolaire::create([
            'nom' => 'Cycle 2',
            'type_id' => 2,
            'description' => 'Enseignement fondamental (3-4ème année)',
            'actif' => true,
        ]);

        CycleScolaire::create([
            'nom' => 'Cycle 3',
            'type_id' => 2,
            'description' => 'Enseignement fondamental (5-6ème année)',
            'actif' => true,
        ]);

        CycleScolaire::create([
            'nom' => 'Cycle 4',
            'type_id' => 2,
            'description' => 'Enseignement fondamental (7-8ème année)',
            'actif' => true,
        ]);

         CycleScolaire::create([
            'nom' => 'Cycle 5',
            'type_id' => 3,
            'description' => 'Enseignement Post-fondamental (1ère-4ème année)',
            'actif' => true,
        ]);
    }
}
