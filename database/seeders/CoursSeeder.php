<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CoursSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Migrating Cours Seeder ...');
        CategorieCours::create([
            'nom' => 'Mathématiques',
            'code' => 'MATH',
            'categorie_cours_id' => 1,
            'est_principale' => true,
            'ponderation_tj' => 80,
            'ponderation_examen' => 80,
            'credit_heures' => 2,
            'section_id' => 1,
            'niveau_id' => 1,
            'description' => 'Mathématiques',
            'coefficient' => 1,
            'heures_par_semaine' => 5,
            'actif' => true,
        ]);

    }
}
