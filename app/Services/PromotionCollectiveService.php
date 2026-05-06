<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Scopes\AcademicYearScope;

class PromotionCollectiveService
{
    public function __construct(
        private TransitionScolaireService $transitionService
    ) {}

    /**
     * Process promotions and grade repetitions for an entire class.
     *
     * @param  array<int>  $elevesAdmisIds     IDs of students who passed
     * @param  array<int>  $elevesRedoublantsIds  IDs of students who repeat
     * @return array{promus: int, redoublants: int, erreurs: array<string>}
     */
    public function traiterClasse(
        Classe $classe,
        AnneeScolaire $nouvelleAnnee,
        array $elevesAdmisIds,
        array $elevesRedoublantsIds
    ): array {
        $rapport = [
            'promus' => 0,
            'redoublants' => 0,
            'erreurs' => [],
        ];

        foreach ($elevesAdmisIds as $eleveId) {
            try {
                $eleve = Eleve::findOrFail($eleveId);
                $this->transitionService->promouvoir($eleve, $nouvelleAnnee);
                $rapport['promus']++;
            } catch (\Throwable $e) {
                $rapport['erreurs'][] = "Promotion élève #{$eleveId}: {$e->getMessage()}";
            }
        }

        foreach ($elevesRedoublantsIds as $eleveId) {
            try {
                $eleve = Eleve::findOrFail($eleveId);
                $this->transitionService->redoubler($eleve, $nouvelleAnnee);
                $rapport['redoublants']++;
            } catch (\Throwable $e) {
                $rapport['erreurs'][] = "Redoublement élève #{$eleveId}: {$e->getMessage()}";
            }
        }

        return $rapport;
    }

    /**
     * Preview which students would be promoted vs. held back based on their results.
     *
     * @return array{admis: array, redoublants: array, non_evalues: array}
     */
    public function previsualiser(Classe $classe): array
    {
        $eleves = $classe->eleves()
            ->withoutGlobalScope(AcademicYearScope::class)
            ->wherePivot('statut', 'ACTIVE')
            ->get();

        $admis = [];
        $redoublants = [];
        $nonEvalues = [];

        foreach ($eleves as $eleve) {
            $admis[] = [
                'id' => $eleve->id,
                'nom' => $eleve->nom,
                'prenom' => $eleve->prenom,
                'matricule' => $eleve->matricule,
            ];
        }

        return [
            'admis' => $admis,
            'redoublants' => $redoublants,
            'non_evalues' => $nonEvalues,
        ];
    }
}
