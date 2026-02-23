<?php

namespace Database\Seeders;

use App\Models\Batiment;
use App\Models\School;
use Illuminate\Database\Seeder;

class BatimentSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where('statut', 'ACTIVE')->get();

        if ($schools->isEmpty()) {
            $this->command->warn('Aucune école active trouvée.');
            return;
        }

        $createdCount = 0;

        foreach ($schools as $school) {

            $batiments = $this->getBatimentsForSchool($school);

            foreach ($batiments as $data) {

                Batiment::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'nom' => $data['nom'],
                    ],
                    [
                        'type' => $data['type'],
                        'annee_construction' => $data['annee_construction'],
                        'superficie' => $data['superficie'],
                        'nombre_etages' => $data['nombre_etages'],
                        'etat' => $data['etat'],
                        'description' => $data['description'],
                    ]
                );

                $createdCount++;
            }
        }

        $this->command->info("$createdCount bâtiments créés avec succès !");
    }

    /**
     * Génère les bâtiments selon le type d'école
     */
    private function getBatimentsForSchool($school): array
    {
        $currentYear = now()->year;

        $baseBatiments = [
            [
                'nom' => 'Bloc Administratif',
                'type' => 'ADMINISTRATIF',
                'annee_construction' => $currentYear - rand(5, 25),
                'superficie' => rand(150, 400),
                'nombre_etages' => rand(1, 2),
                'etat' => 'BON',
                'description' => 'Bureaux de la direction et administration.'
            ],
            [
                'nom' => 'Bloc Académique A',
                'type' => 'ACADEMIQUE',
                'annee_construction' => $currentYear - rand(3, 20),
                'superficie' => rand(300, 800),
                'nombre_etages' => rand(1, 3),
                'etat' => 'BON',
                'description' => 'Salles de classe principales.'
            ],
            [
                'nom' => 'Terrain de Sport',
                'type' => 'SPORTIF',
                'annee_construction' => $currentYear - rand(1, 15),
                'superficie' => rand(500, 1500),
                'nombre_etages' => 1,
                'etat' => 'MOYEN',
                'description' => 'Infrastructure sportive polyvalente.'
            ],
        ];

        // Si école secondaire → ajouter laboratoire
        if (in_array($school->niveau, ['SECONDAIRE', 'POST_FONDAMENTAL'])) {
            $baseBatiments[] = [
                'nom' => 'Laboratoire Scientifique',
                'type' => 'ACADEMIQUE',
                'annee_construction' => $currentYear - rand(2, 15),
                'superficie' => rand(120, 300),
                'nombre_etages' => 1,
                'etat' => 'BON',
                'description' => 'Laboratoire de sciences (Physique, Chimie, Biologie).'
            ];
        }

        // Si école technique → ajouter atelier
        if ($school->type === 'TECHNIQUE') {
            $baseBatiments[] = [
                'nom' => 'Atelier Technique',
                'type' => 'ACADEMIQUE',
                'annee_construction' => $currentYear - rand(1, 10),
                'superficie' => rand(200, 600),
                'nombre_etages' => 1,
                'etat' => 'BON',
                'description' => 'Atelier pour travaux pratiques techniques.'
            ];
        }

        return $baseBatiments;
    }
}