<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Niveau;
use App\Models\School;
use Illuminate\Database\Seeder;

class ClasseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anneeScolaire = AnneeScolaire::where('est_active', true)->first();

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

        // Configuration des classes par niveau
        $classesConfig = [
            // Fondamental (1P à 9F)
            '1P' => ['sections' => ['A', 'B'], 'capacite' => 45],
            '2P' => ['sections' => ['A', 'B'], 'capacite' => 45],
            '3P' => ['sections' => ['A', 'B'], 'capacite' => 45],
            '4P' => ['sections' => ['A', 'B'], 'capacite' => 40],
            '5P' => ['sections' => ['A', 'B'], 'capacite' => 40],
            '6P' => ['sections' => ['A', 'B'], 'capacite' => 40],
            '7F' => ['sections' => ['A'], 'capacite' => 50],
            '8F' => ['sections' => ['A'], 'capacite' => 50],
            '9F' => ['sections' => ['A'], 'capacite' => 50],
            // Post-fondamental
            '1PF' => ['sections' => ['A'], 'capacite' => 40],
            '2PF' => ['sections' => ['A'], 'capacite' => 40],
            '3PF' => ['sections' => ['A'], 'capacite' => 40],
            '4PF' => ['sections' => ['A'], 'capacite' => 35],
        ];

        $createdCount = 0;

        foreach ($schools as $school) {
            // Déterminer quels niveaux cette école propose selon son type
            $niveauxEcole = $this->getNiveauxForSchool($school, $niveaux);

            foreach ($niveauxEcole as $niveauCode => $niveau) {
                $config = $classesConfig[$niveauCode] ?? ['sections' => ['A'], 'capacite' => 40];

                foreach ($config['sections'] as $section) {
                    $code = $niveauCode.$section;
                    $nom = $niveau->nom.' - Section '.$section;

                    Classe::updateOrCreate(
                        [
                            'ecole_id' => $school->id,
                            'niveau_id' => $niveau->id,
                            'annee_scolaire_id' => $anneeScolaire->id,
                            'code' => $code,
                        ],
                        [
                            'nom' => $nom,
                            'capacite' => $config['capacite'],
                            'statut' => 'ACTIVE',
                            'created_by' => 1,
                        ]
                    );

                    $createdCount++;
                }
            }
        }

        $this->command->info("$createdCount classes créées avec succès!");
    }

    /**
     * Retourne les niveaux appropriés selon le type d'école.
     */
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
                // Pour les autres types, inclure quelques niveaux de base
                foreach (array_slice($fondamental, 0, 6) as $code) {
                    if (isset($niveaux[$code])) {
                        $result[$code] = $niveaux[$code];
                    }
                }
        }

        return $result;
    }
}
