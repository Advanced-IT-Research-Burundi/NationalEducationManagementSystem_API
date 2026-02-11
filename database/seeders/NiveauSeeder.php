<?php

namespace Database\Seeders;

use App\Models\Niveau;
use Illuminate\Database\Seeder;

class NiveauSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Système éducatif du Burundi:
     * - Préscolaire (Maternelle)
     * - Fondamental (9 ans): Cycle 1 (1-4), Cycle 2 (5-6), Cycle 3 (7-9)
     * - Post-Fondamental / Secondaire (4 ans)
     */
    public function run(): void
    {
        $niveaux = [
            // Préscolaire
            [
                'code' => 'PS1',
                'nom' => 'Petite Section',
                'ordre' => 1,
                'cycle' => 'PRIMAIRE',
                'description' => 'Préscolaire - Petite Section (3-4 ans)',
                'actif' => true,
            ],
            [
                'code' => 'PS2',
                'nom' => 'Moyenne Section',
                'ordre' => 2,
                'cycle' => 'PRIMAIRE',
                'description' => 'Préscolaire - Moyenne Section (4-5 ans)',
                'actif' => true,
            ],
            [
                'code' => 'PS3',
                'nom' => 'Grande Section',
                'ordre' => 3,
                'cycle' => 'PRIMAIRE',
                'description' => 'Préscolaire - Grande Section (5-6 ans)',
                'actif' => true,
            ],

            // Fondamental - Cycle 1 (1ère à 4ème année)
            [
                'code' => '1F',
                'nom' => '1ère Année Fondamentale',
                'ordre' => 4,
                'cycle' => 'FONDAMENTAL',
                'description' => 'Fondamental Cycle 1 - 1ère année',
                'actif' => true,
            ],
            [
                'code' => '2F',
                'nom' => '2ème Année Fondamentale',
                'ordre' => 5,
                'cycle' => 'FONDAMENTAL',
                'description' => 'Fondamental Cycle 1 - 2ème année',
                'actif' => true,
            ],
            [
                'code' => '3F',
                'nom' => '3ème Année Fondamentale',
                'ordre' => 6,
                'cycle' => 'FONDAMENTAL',
                'description' => 'Fondamental Cycle 1 - 3ème année',
                'actif' => true,
            ],
            [
                'code' => '4F',
                'nom' => '4ème Année Fondamentale',
                'ordre' => 7,
                'cycle' => 'FONDAMENTAL',
                'description' => 'Fondamental Cycle 1 - 4ème année',
                'actif' => true,
            ],

            // Fondamental - Cycle 2 (5ème à 6ème année)
            [
                'code' => '5F',
                'nom' => '5ème Année Fondamentale',
                'ordre' => 8,
                'cycle' => 'FONDAMENTAL',
                'description' => 'Fondamental Cycle 2 - 5ème année',
                'actif' => true,
            ],
            [
                'code' => '6F',
                'nom' => '6ème Année Fondamentale',
                'ordre' => 9,
                'cycle' => 'FONDAMENTAL',
                'description' => 'Fondamental Cycle 2 - 6ème année',
                'actif' => true,
            ],

            // Fondamental - Cycle 3 (7ème à 9ème année)
            [
                'code' => '7F',
                'nom' => '7ème Année Fondamentale',
                'ordre' => 10,
                'cycle' => 'FONDAMENTAL',
                'description' => 'Fondamental Cycle 3 - 7ème année',
                'actif' => true,
            ],
            [
                'code' => '8F',
                'nom' => '8ème Année Fondamentale',
                'ordre' => 11,
                'cycle' => 'FONDAMENTAL',
                'description' => 'Fondamental Cycle 3 - 8ème année',
                'actif' => true,
            ],
            [
                'code' => '9F',
                'nom' => '9ème Année Fondamentale',
                'ordre' => 12,
                'cycle' => 'FONDAMENTAL',
                'description' => 'Fondamental Cycle 3 - 9ème année (Concours National)',
                'actif' => true,
            ],

            // Post-Fondamental / Secondaire
            [
                'code' => '1PF',
                'nom' => '1ère Année Post-Fondamentale',
                'ordre' => 13,
                'cycle' => 'POST_FONDAMENTAL',
                'description' => 'Post-Fondamental - 1ère année (10ème)',
                'actif' => true,
            ],
            [
                'code' => '2PF',
                'nom' => '2ème Année Post-Fondamentale',
                'ordre' => 14,
                'cycle' => 'POST_FONDAMENTAL',
                'description' => 'Post-Fondamental - 2ème année (11ème)',
                'actif' => true,
            ],
            [
                'code' => '3PF',
                'nom' => '3ème Année Post-Fondamentale',
                'ordre' => 15,
                'cycle' => 'POST_FONDAMENTAL',
                'description' => 'Post-Fondamental - 3ème année (12ème)',
                'actif' => true,
            ],
            [
                'code' => '4PF',
                'nom' => '4ème Année Post-Fondamentale',
                'ordre' => 16,
                'cycle' => 'POST_FONDAMENTAL',
                'description' => 'Post-Fondamental - 4ème année (13ème - Examen d\'État)',
                'actif' => true,
            ],
        ];

        foreach ($niveaux as $niveau) {
            Niveau::updateOrCreate(
                ['code' => $niveau['code']],
                $niveau
            );
        }

        $this->command->info('Niveaux scolaires créés avec succès! ('.count($niveaux).' niveaux)');
    }
}
