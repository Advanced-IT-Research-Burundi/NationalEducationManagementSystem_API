<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Niveau;
use App\Models\School;


class NiveauSchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Migrating NiveauSchool Seeder ...');

        // Get all niveaux
        $niveaux = Niveau::all();

        // Get all schools
        $schools = School::all();

        // Associate each school with all niveaux (many-to-many)
        foreach ($schools as $school) {
            $school->niveauxScolaires()->sync($niveaux->pluck('id')->toArray());
        }

        $this->command->info('NiveauSchool associations created successfully!');
    }
}
