<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Services\PromotionCollectiveService;
use App\Services\TransitionScolaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function __construct(
        private TransitionScolaireService $transitionService,
        private PromotionCollectiveService $promotionService
    ) {}

    /**
     * Promote a single student to the next level.
     */
    public function promouvoir(Request $request): JsonResponse
    {
        $request->validate([
            'eleve_id' => ['required', 'exists:eleves,id'],
            'annee_scolaire_destination_id' => ['required', 'exists:annee_scolaires,id'],
        ]);

        $eleve = Eleve::findOrFail($request->eleve_id);
        $this->authorize('update', $eleve);

        $nouvelleAnnee = AnneeScolaire::findOrFail($request->annee_scolaire_destination_id);

        try {
            $inscription = $this->transitionService->promouvoir($eleve, $nouvelleAnnee);

            return response()->json([
                'message' => "Élève {$eleve->nom_complet} promu avec succès.",
                'inscription' => $inscription->load(['anneeScolaire', 'ecole', 'niveauDemande']),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Hold a student back (repeat the same level).
     */
    public function redoubler(Request $request): JsonResponse
    {
        $request->validate([
            'eleve_id' => ['required', 'exists:eleves,id'],
            'annee_scolaire_destination_id' => ['required', 'exists:annee_scolaires,id'],
        ]);

        $eleve = Eleve::findOrFail($request->eleve_id);
        $this->authorize('update', $eleve);

        $nouvelleAnnee = AnneeScolaire::findOrFail($request->annee_scolaire_destination_id);

        try {
            $inscription = $this->transitionService->redoubler($eleve, $nouvelleAnnee);

            return response()->json([
                'message' => "Élève {$eleve->nom_complet} marqué comme redoublant avec succès.",
                'inscription' => $inscription->load(['anneeScolaire', 'ecole', 'niveauDemande']),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Process promotions and repetitions for an entire class.
     */
    public function collective(Request $request): JsonResponse
    {
        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'annee_scolaire_destination_id' => ['required', 'exists:annee_scolaires,id'],
            'admis' => ['required', 'array'],
            'admis.*' => ['exists:eleves,id'],
            'redoublants' => ['required', 'array'],
            'redoublants.*' => ['exists:eleves,id'],
        ]);

        $classe = Classe::findOrFail($request->classe_id);
        $this->authorize('update', $classe);

        $nouvelleAnnee = AnneeScolaire::findOrFail($request->annee_scolaire_destination_id);

        $rapport = $this->promotionService->traiterClasse(
            $classe,
            $nouvelleAnnee,
            $request->input('admis', []),
            $request->input('redoublants', [])
        );

        return response()->json([
            'message' => "Promotion collective terminée : {$rapport['promus']} promus, {$rapport['redoublants']} redoublants.",
            'rapport' => $rapport,
        ]);
    }

    /**
     * Preview students in a class with their status for end-of-year decisions.
     */
    public function preview(Classe $classe): JsonResponse
    {
        $this->authorize('view', $classe);

        $result = $this->promotionService->previsualiser($classe);

        return response()->json([
            'classe' => [
                'id' => $classe->id,
                'nom' => $classe->nom,
                'niveau' => $classe->niveau?->nom,
            ],
            'eleves' => $result,
        ]);
    }
}
