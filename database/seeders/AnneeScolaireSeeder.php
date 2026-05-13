<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use Illuminate\Database\Seeder;

class AnneeScolaireSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anneesScolaires = [
            [
                'code' => '23-24',
                'libelle' => '2023-2024',
                'date_debut' => '2023-09-04',
                'date_fin' => '2024-06-28',
                'est_active' => false,
            ],
            [
                'code' => '24-25',
                'libelle' => '2024-2025',
                'date_debut' => '2024-09-02',
                'date_fin' => '2025-06-27',
                'est_active' => false,
            ],
            [
                'code' => '25-26',
                'libelle' => '2025-2026',
                'date_debut' => '2025-09-01',
                'date_fin' => '2026-06-30',
                'est_active' => true,
            ],
        ];

        foreach ($anneesScolaires as $annee) {
            AnneeScolaire::updateOrCreate(
                ['code' => $annee['code']],
                $annee
            );
        }

        $this->call([
            TrimestreSeeder::class,
        ]);

        $this->command->info('Années scolaires créées avec succès!');
    }
}
