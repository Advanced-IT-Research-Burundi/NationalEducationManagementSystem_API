<?php

namespace Database\Seeders;

use App\Models\Matiere;
use Illuminate\Database\Seeder;

class MatiereSeeder extends Seeder
{
    public function run(): void
    {
        $matieres = [
            // Fondamental
            ['nom' => 'Kirundi', 'code' => 'KIR', 'coefficient' => 3, 'heures_par_semaine' => 5, 'description' => 'Langue nationale'],
            ['nom' => 'Français', 'code' => 'FRA', 'coefficient' => 3, 'heures_par_semaine' => 5, 'description' => 'Langue française'],
            ['nom' => 'Anglais', 'code' => 'ANG', 'coefficient' => 2, 'heures_par_semaine' => 3, 'description' => 'Langue anglaise'],
            ['nom' => 'Kiswahili', 'code' => 'KIS', 'coefficient' => 1, 'heures_par_semaine' => 2, 'description' => 'Langue kiswahili'],
            ['nom' => 'Mathématiques', 'code' => 'MATH', 'coefficient' => 4, 'heures_par_semaine' => 5, 'description' => 'Mathématiques'],
            ['nom' => 'Sciences', 'code' => 'SCI', 'coefficient' => 2, 'heures_par_semaine' => 3, 'description' => 'Sciences naturelles'],
            ['nom' => 'Physique', 'code' => 'PHY', 'coefficient' => 3, 'heures_par_semaine' => 3, 'description' => 'Physique'],
            ['nom' => 'Chimie', 'code' => 'CHI', 'coefficient' => 3, 'heures_par_semaine' => 3, 'description' => 'Chimie'],
            ['nom' => 'Biologie', 'code' => 'BIO', 'coefficient' => 3, 'heures_par_semaine' => 3, 'description' => 'Biologie'],
            ['nom' => 'Histoire', 'code' => 'HIS', 'coefficient' => 2, 'heures_par_semaine' => 2, 'description' => 'Histoire'],
            ['nom' => 'Géographie', 'code' => 'GEO', 'coefficient' => 2, 'heures_par_semaine' => 2, 'description' => 'Géographie'],
            ['nom' => 'Éducation Civique', 'code' => 'CIVIC', 'coefficient' => 1, 'heures_par_semaine' => 1, 'description' => 'Éducation à la citoyenneté'],
            ['nom' => 'Technologie', 'code' => 'TECH', 'coefficient' => 2, 'heures_par_semaine' => 2, 'description' => 'Technologie'],
            ['nom' => 'Informatique', 'code' => 'INFO', 'coefficient' => 2, 'heures_par_semaine' => 2, 'description' => 'Informatique'],
            ['nom' => 'Éducation Physique', 'code' => 'EPS', 'coefficient' => 1, 'heures_par_semaine' => 2, 'description' => 'Éducation physique et sportive'],
            ['nom' => 'Éducation Artistique', 'code' => 'ART', 'coefficient' => 1, 'heures_par_semaine' => 1, 'description' => 'Arts et culture'],
            ['nom' => 'Religion', 'code' => 'REL', 'coefficient' => 1, 'heures_par_semaine' => 1, 'description' => 'Éducation religieuse'],
            ['nom' => 'Économie', 'code' => 'ECO', 'coefficient' => 2, 'heures_par_semaine' => 2, 'description' => 'Économie'],
            ['nom' => 'Comptabilité', 'code' => 'CPT', 'coefficient' => 2, 'heures_par_semaine' => 3, 'description' => 'Comptabilité'],
            ['nom' => 'Philosophie', 'code' => 'PHILO', 'coefficient' => 2, 'heures_par_semaine' => 2, 'description' => 'Philosophie'],
            ['nom' => 'Électrotechnique', 'code' => 'ELEC', 'coefficient' => 3, 'heures_par_semaine' => 4, 'description' => 'Électrotechnique'],
            ['nom' => 'Mécanique', 'code' => 'MEC', 'coefficient' => 3, 'heures_par_semaine' => 4, 'description' => 'Mécanique'],
        ];

        foreach ($matieres as $matiere) {
            Matiere::create($matiere);
        }
    }
}
