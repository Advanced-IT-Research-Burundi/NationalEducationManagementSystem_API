<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use App\Models\Colline;

class BurundiSchoolsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // We will try to find a colline to attach schools to.
        // For realism, we should query a few real collines from the DB if seeded.
        // Fallback to ID 1 if not found.
        $collineIds = Colline::limit(5)->pluck('id')->toArray();
        if (empty($collineIds)) {
            // Not seeded? Assuming 1 for safety, though seeding should have happened.
            $collineIds = [1];
        }

        $schools = [
            [
                'name' => 'Lycée Municipal de Rohero',
                'code_ecole' => 'LMR_001',
                'type_ecole' => 'PUBLIQUE',
                'niveau' => 'SECONDAIRE',
                'latitude' => -3.3833,
                'longitude' => 29.3667,
            ],
            [
                'name' => 'Ecole Indépendante de Bujumbura',
                'code_ecole' => 'EIB_002',
                'type_ecole' => 'PRIVEE',
                'niveau' => 'FONDAMENTAL',
                'latitude' => -3.3750,
                'longitude' => 29.3600,
            ],
            [
                'name' => 'Lycée du Saint-Esprit',
                'code_ecole' => 'LSE_003',
                'type_ecole' => 'ECC', // Ecoles sous Convention Catholique often
                'niveau' => 'SECONDAIRE',
                'latitude' => -3.3600,
                'longitude' => 29.3800,
            ],
            [
                'name' => 'Ecole Technique Secondaire de Kamenge',
                'code_ecole' => 'ETS_004',
                'type_ecole' => 'PUBLIQUE',
                'niveau' => 'SECONDAIRE', // Technical usually secondary+
                'latitude' => -3.3400,
                'longitude' => 29.3900,
            ],
            [
                'name' => 'Lycée SOS  Hermann Gmeiner',
                'code_ecole' => 'SOS_005',
                'type_ecole' => 'PRIVEE',
                'niveau' => 'POST_FONDAMENTAL',
                'latitude' => -3.3800,
                'longitude' => 29.3700,
            ],
            [
                'name' => 'Ecole Primaire de Kanyosha',
                'code_ecole' => 'EPK_006',
                'type_ecole' => 'PUBLIQUE',
                'niveau' => 'FONDAMENTAL',
                'latitude' => -3.4200,
                'longitude' => 29.3500,
            ],
            [
                'name' => 'Lycée Clarté Notre Dame de Vugizo',
                'code_ecole' => 'VUG_007',
                'type_ecole' => 'ECC',
                'niveau' => 'SECONDAIRE',
                'latitude' => -3.3900,
                'longitude' => 29.3650,
            ],
            [
                'name' => 'Ecole Belge de Bujumbura',
                'code_ecole' => 'EBB_008',
                'type_ecole' => 'PRIVEE', // Or International, marked as Autre or Privee
                'niveau' => 'FONDAMENTAL',
                'latitude' => -3.3720,
                'longitude' => 29.3550,
            ],
            [
                'name' => 'Lycée Scheppers de Nyakabiga',
                'code_ecole' => 'LSN_009',
                'type_ecole' => 'ECC',
                'niveau' => 'SECONDAIRE',
                'latitude' => -3.3780,
                'longitude' => 29.3750,
            ],
            [
                'name' => 'Ecole Fondamentale de Kinama',
                'code_ecole' => 'EFK_010',
                'type_ecole' => 'PUBLIQUE',
                'niveau' => 'FONDAMENTAL',
                'latitude' => -3.3500,
                'longitude' => 29.3850,
            ],
            [
                'name' => 'Lycée Ngagara',
                'code_ecole' => 'LNG_011',
                'type_ecole' => 'PUBLIQUE',
                'niveau' => 'POST_FONDAMENTAL',
                'latitude' => -3.3650,
                'longitude' => 29.3680,
            ]
        ];

        foreach ($schools as $index => $schoolData) {
            $collineId = $collineIds[$index % count($collineIds)];
            
            // Auto-resolve parents using DB query logic similar to controller
            // Or simple assumption if we trust seed data integrity.
            // Using the controller logic is better but we are in seeder.
            $colline = Colline::with(['zone.commune.province.ministere.pays'])->find($collineId);

            if ($colline) {
                 School::create(array_merge($schoolData, [
                    'colline_id' => $collineId,
                    'zone_id' => $colline->zone_id,
                    'commune_id' => $colline->zone->commune_id ?? null,
                    'province_id' => $colline->zone->commune->province_id ?? null,
                    'ministere_id' => $colline->zone->commune->province->ministere_id ?? null,
                    'pays_id' => $colline->zone->commune->province->pays_id ?? 1,
                    'statut' => 'ACTIVE',
                    'created_by' => 1, // System or Admin
                 ]));
            }
        }
    }
}
