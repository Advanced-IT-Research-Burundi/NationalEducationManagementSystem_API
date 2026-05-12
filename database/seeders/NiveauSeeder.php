<?php

namespace Database\Seeders;

use App\Models\CycleScolaire;
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
        // Get cycle IDs
        $cycle1 = CycleScolaire::where('nom', 'Cycle 1')->first()?->id;
        $cycle2 = CycleScolaire::where('nom', 'Cycle 2')->first()?->id;
        $cycle3 = CycleScolaire::where('nom', 'Cycle 3')->first()?->id;
        $cycle4 = CycleScolaire::where('nom', 'Cycle 4')->first()?->id;
        $cycle5 = CycleScolaire::where('nom', 'Cycle 5')->first()?->id;

        if (!$cycle1 || !$cycle2 || !$cycle3 || !$cycle4 || !$cycle5) {
            $this->command->error('Les cycles scolaires ne sont pas créés. Veuillez exécuter CycleSeeder d\'abord.');
            return;
        }

        $niveaux = [
            // Préscolaire
            [
                'code' => 'PS1',
                'nom' => '1ère Année Maternelle',
                'ordre' => 1,
                'type_id' => 1,
                'cycle_id' => null,
                'description' => 'Maternelle - 1ère année',
                'actif' => true,
            ],
            [
                'code' => 'PS2',
                'nom' => '2ème Année Maternelle',
                'ordre' => 2,
                'type_id' => 1,
                'cycle_id' => null,
                'description' => 'Maternelle - 2ème année',
                'actif' => true,
            ],
            [
                'code' => 'PS3',
                'nom' => '3ème Année Maternelle',
                'ordre' => 3,
                'type_id' => 1,
                'cycle_id' => null,
                'description' => 'Maternelle - 3ème année',
                'actif' => true,
            ],

            // Fondamental - Cycle 1 (1ère à 4ème année)
            [
                'code' => '1F',
                'nom' => '1ère Année Fondamentale',
                'ordre' => 4,
                'type_id' => 2,
                'cycle_id' => $cycle1,
                'description' => 'Fondamental Cycle 1 - 1ère année',
                'actif' => true,
            ],
            [
                'code' => '2F',
                'nom' => '2ème Année Fondamentale',
                'ordre' => 5,
                'type_id' => 2,
                'cycle_id' => $cycle1,
                'description' => 'Fondamental Cycle 1 - 2ème année',
                'actif' => true,
            ],
            [
                'code' => '3F',
                'nom' => '3ème Année Fondamentale',
                'ordre' => 6,
                'type_id' => 2,
                'cycle_id' => $cycle2,
                'description' => 'Fondamental Cycle 1 - 3ème année',
                'actif' => true,
            ],
            [
                'code' => '4F',
                'nom' => '4ème Année Fondamentale',
                'ordre' => 7,
                'type_id' => 2,
                'cycle_id' => $cycle2,
                'description' => 'Fondamental Cycle 1 - 4ème année',
                'actif' => true,
            ],

            // Fondamental - Cycle 2 (5ème à 6ème année)
            [
                'code' => '5F',
                'nom' => '5ème Année Fondamentale',
                'ordre' => 8,
                'type_id' => 2,
                'cycle_id' => $cycle3,
                'description' => 'Fondamental Cycle 2 - 5ème année',
                'actif' => true,
            ],
            [
                'code' => '6F',
                'nom' => '6ème Année Fondamentale',
                'ordre' => 9,
                'type_id' => 2,
                'cycle_id' => $cycle3,
                'description' => 'Fondamental Cycle 2 - 6ème année',
                'actif' => true,
            ],

            // Fondamental - Cycle 3 (7ème à 9ème année)
            [
                'code' => '7F',
                'nom' => '7ème Année Fondamentale',
                'ordre' => 10,
                'type_id' => 2,
                'cycle_id' => $cycle4,
                'description' => 'Fondamental Cycle 3 - 7ème année',
                'actif' => true,
            ],
            [
                'code' => '8F',
                'nom' => '8ème Année Fondamentale',
                'ordre' => 11,
                'type_id' => 2,
                'cycle_id' => $cycle4,
                'description' => 'Fondamental Cycle 3 - 8ème année',
                'actif' => true,
            ],
            [
                'code' => '9F',
                'nom' => '9ème Année Fondamentale',
                'ordre' => 12,
                'type_id' => 2,
                'cycle_id' => $cycle4,
                'description' => 'Fondamental Cycle 3 - 9ème année (Concours National)',
                'actif' => true,
            ],

            // Post-Fondamental / Secondaire
            [
                'code' => '1PF',
                'nom' => '1ère Année Post-Fondamentale',
                'ordre' => 13,
                'type_id' => 3,
                'cycle_id' => $cycle5,
                'description' => 'Post-Fondamental - 1ère année',
                'actif' => true,
            ],
            [
                'code' => '2PF',
                'nom' => '2ème Année Post-Fondamentale',
                'ordre' => 14,
                'type_id' => 3,
                'cycle_id' => $cycle5,
                'description' => 'Post-Fondamental - 2ème année',
                'actif' => true,
            ],
            [
                'code' => '3PF',
                'nom' => '3ème Année Post-Fondamentale',
                'ordre' => 15,
                'type_id' => 3,
                'cycle_id' => $cycle5,
                'description' => 'Post-Fondamental - 3ème année',
                'actif' => true,
            ],
            [
                'code' => '4PF',
                'nom' => '4ème Année Post-Fondamentale',
                'ordre' => 16,
                'type_id' => 3,
                'cycle_id' => $cycle5,
                'description' => 'Post-Fondamental - 4ème année',
                'actif' => true,
            ],
        ];

        foreach ($niveaux as $niveau) {
            Niveau::updateOrCreate(
                ['code' => $niveau['code']],
                $niveau
            );
        }

        $this->command->info('Niveaux scolaires créés avec succès! (' . count($niveaux) . ' niveaux)');
    }
}
