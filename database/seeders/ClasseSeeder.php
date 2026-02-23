<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Niveau;
use App\Models\School;
use App\Models\Section;
use Illuminate\Database\Seeder;

class ClasseSeeder extends Seeder
{
    public function run(): void
    {
        $anneeScolaire = AnneeScolaire::where('est_active', true)->first();

        $sections = Section::get();

        if (! $anneeScolaire) {
            $this->command->warn('Aucune année scolaire active trouvée. Seedage des classes ignoré.');
            return;
        }

        $schools = School::where('statut', 'ACTIVE')->get();

        if ($schools->isEmpty()) {
            $this->command->warn('Aucune école active trouvée. Seedage des classes ignoré.');
            return;
        }

        $niveaux = Niveau::where('actif', true)->get()->keyBy('code');

        if ($niveaux->isEmpty()) {
            $this->command->warn('Aucun niveau trouvé. Seedage des classes ignoré.');
            return;
        }

        // Configuration des capacités par niveau (SANS SECTION)
        $classesConfig = [
            '1P' => 45,
            '2P' => 45,
            '3P' => 45,
            '4P' => 40,
            '5P' => 40,
            '6P' => 40,
            '7F' => 50,
            '8F' => 50,
            '9F' => 50,
            '1PF' => 40,
            '2PF' => 40,
            '3PF' => 40,
            '4PF' => 35,
        ];

        $createdCount = 0;

        foreach ($schools as $school) {

            $niveauxEcole = $this->getNiveauxForSchool($school, $niveaux);

            foreach ($niveauxEcole as $niveauCode => $niveau) {

                $capacite = $classesConfig[$niveauCode] ?? 40;

                Classe::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'niveau_id' => $niveau->id,
                        'annee_scolaire_id' => $anneeScolaire->id,
                        'code' => $niveauCode, 
                    ],
                    [
                        'nom' => $niveau->nom, 
                        'capacite' => $capacite,
                        'statut' => 'ACTIVE',
                        'created_by' => 1,
                    ]
                );

                $createdCount++;
            }
        }

        $this->command->info("$createdCount classes créées avec succès!");
    }

    private function getNiveauxForSchool(School $school, $niveaux): array
    {
        $result = [];

        $fondamental = ['1P', '2P', '3P', '4P', '5P', '6P', '7F', '8F', '9F'];
        $postFondamental = ['1PF', '2PF', '3PF', '4PF'];

        switch ($school->niveau) {
            case 'FONDAMENTAL':
                foreach ($fondamental as $code) {
                    if (isset($niveaux[$code])) {
                        $result[$code] = $niveaux[$code];
                    }
                }
                break;

            case 'SECONDAIRE':
            case 'POST_FONDAMENTAL':
                foreach ($postFondamental as $code) {
                    if (isset($niveaux[$code])) {
                        $result[$code] = $niveaux[$code];
                    }
                }
                break;

            default:
                foreach (array_slice($fondamental, 0, 6) as $code) {
                    if (isset($niveaux[$code])) {
                        $result[$code] = $niveaux[$code];
                    }
                }
        }

        return $result;
    }
}