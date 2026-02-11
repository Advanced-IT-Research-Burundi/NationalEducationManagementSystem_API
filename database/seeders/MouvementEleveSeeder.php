<?php

namespace Database\Seeders;

use App\Enums\StatutMouvement;
use App\Enums\TypeMouvement;
use App\Models\AnneeScolaire;
use App\Models\InscriptionEleve;
use App\Models\MouvementEleve;
use App\Models\School;
use Illuminate\Database\Seeder;

class MouvementEleveSeeder extends Seeder
{
    /**
     * Motifs réalistes par type de mouvement.
     */
    private array $motifs = [
        'transfert_sortant' => [
            'Déménagement de la famille vers une autre province',
            'Rapprochement du domicile familial',
            'Mutation professionnelle des parents',
            'Intégration dans un internat',
            'Admission dans une école spécialisée',
        ],
        'abandon' => [
            'Difficultés financières de la famille',
            'Mariage précoce',
            'Travail pour soutenir la famille',
            'Maladie prolongée',
            'Éloignement de l\'école',
        ],
        'exclusion' => [
            'Absences répétées non justifiées',
            'Comportement violent envers les camarades',
            'Non-respect du règlement intérieur',
            'Fraude lors des examens',
        ],
        'passage' => [
            'Passage au niveau supérieur - résultats satisfaisants',
            'Promotion avec mention',
            'Admission au cycle suivant',
        ],
        'redoublement' => [
            'Résultats insuffisants pour le passage',
            'Absences prolongées ayant affecté les apprentissages',
            'Difficultés d\'apprentissage nécessitant une consolidation',
        ],
        'reintegration' => [
            'Retour après abandon - situation familiale stabilisée',
            'Réintégration après maladie',
            'Retour après exclusion temporaire',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anneeScolaire = AnneeScolaire::where('est_active', true)->first();

        if (! $anneeScolaire) {
            $this->command->warn('Aucune année scolaire active trouvée.');

            return;
        }

        // Récupérer des élèves avec leurs inscriptions
        $inscriptions = InscriptionEleve::with(['eleve', 'classe.ecole'])
            ->where('annee_scolaire_id', $anneeScolaire->id)
            ->where('statut', 'valide')
            ->inRandomOrder()
            ->limit(50)
            ->get();

        if ($inscriptions->isEmpty()) {
            $this->command->warn('Aucune inscription trouvée. Exécutez EleveSeeder d\'abord.');

            return;
        }

        $schools = School::where('statut', 'ACTIVE')->pluck('id')->toArray();
        $mouvementsCount = 0;

        // Créer des mouvements variés
        foreach ($inscriptions as $index => $inscription) {
            $type = $this->getRandomType($index);

            $mouvement = $this->createMouvement(
                $inscription,
                $type,
                $anneeScolaire->id,
                $schools
            );

            if ($mouvement) {
                $mouvementsCount++;
            }
        }

        $this->command->info("$mouvementsCount mouvements créés avec succès!");
    }

    /**
     * Détermine un type de mouvement basé sur l'index pour avoir une variété.
     */
    private function getRandomType(int $index): TypeMouvement
    {
        $types = [
            TypeMouvement::TransfertSortant,
            TypeMouvement::TransfertSortant,
            TypeMouvement::Abandon,
            TypeMouvement::Passage,
            TypeMouvement::Passage,
            TypeMouvement::Passage,
            TypeMouvement::Redoublement,
            TypeMouvement::Reintegration,
        ];

        // Ajouter quelques exclusions et autres cas rares
        if ($index % 15 === 0) {
            return TypeMouvement::Exclusion;
        }

        return $types[$index % count($types)];
    }

    /**
     * Crée un mouvement pour une inscription.
     */
    private function createMouvement(
        InscriptionEleve $inscription,
        TypeMouvement $type,
        int $anneeScolaireId,
        array $schoolIds
    ): ?MouvementEleve {
        $eleve = $inscription->eleve;
        $classe = $inscription->classe;

        if (! $eleve || ! $classe) {
            return null;
        }

        $motifs = $this->motifs[$type->value] ?? ['Mouvement enregistré'];
        $motif = $motifs[array_rand($motifs)];

        // Déterminer le statut (70% validé, 20% en attente, 10% rejeté)
        $rand = rand(1, 10);
        $statut = match (true) {
            $rand <= 7 => StatutMouvement::Valide,
            $rand <= 9 => StatutMouvement::EnAttente,
            default => StatutMouvement::Rejete,
        };

        $data = [
            'eleve_id' => $eleve->id,
            'annee_scolaire_id' => $anneeScolaireId,
            'type_mouvement' => $type->value,
            'date_mouvement' => now()->subDays(rand(1, 90)),
            'ecole_origine_id' => $classe->ecole_id,
            'classe_origine_id' => $classe->id,
            'motif' => $motif,
            'statut' => $statut->value,
            'created_by' => 1,
        ];

        // Pour les transferts, ajouter une école de destination
        if ($type === TypeMouvement::TransfertSortant) {
            $otherSchools = array_filter($schoolIds, fn ($id) => $id !== $classe->ecole_id);
            if (! empty($otherSchools)) {
                $data['ecole_destination_id'] = $otherSchools[array_rand($otherSchools)];
            }
        }

        // Si validé, ajouter les infos de validation
        if ($statut === StatutMouvement::Valide) {
            $data['date_validation'] = now()->subDays(rand(0, 30));
            $data['valide_par'] = 1;

            // Mettre à jour le statut de l'élève si nécessaire
            if ($type->affectsEleveStatus()) {
                $newStatus = $type->resultingEleveStatus();
                if ($newStatus) {
                    $eleve->update(['statut_global' => $newStatus]);
                }
            }
        }

        // Si rejeté, ajouter une observation
        if ($statut === StatutMouvement::Rejete) {
            $data['observations'] = 'Dossier incomplet - pièces justificatives manquantes';
            $data['date_validation'] = now()->subDays(rand(0, 15));
            $data['valide_par'] = 1;
        }

        return MouvementEleve::create($data);
    }
}
