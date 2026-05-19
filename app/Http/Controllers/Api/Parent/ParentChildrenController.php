<?php

namespace App\Http\Controllers\Api\Parent;

use App\Http\Controllers\Controller;
use App\Models\AnneeScolaire;
use App\Models\Role;
use App\Services\AcademicYearService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentChildrenController extends Controller
{
    /**
     * Linked élèves with school and class context for the active (or requested) school year.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasRole(Role::PARENT)) {
            abort(403);
        }

        $anneeScolaireId = AnneeScolaire::withoutGlobalScopes()->active()->value('id')
            ?? AcademicYearService::currentId();

        if ($anneeScolaireId) {
            AcademicYearService::setCurrent($anneeScolaireId);
        }

        $user->load([
            'eleveParents.eleve' => function ($q) {
                $q->withoutGlobalScopes()->with(['ecole']);
            },
        ]);

        $payload = [];

        foreach ($user->eleveParents as $link) {
            $eleve = $link->eleve;

            if (! $eleve) {
                continue;
            }

            $inscription = null;

            if ($anneeScolaireId) {
                $inscription = $eleve->inscriptions()
                    ->withoutGlobalScopes()
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->with(['affectation.classe', 'ecole', 'anneeScolaire'])
                    ->latest()
                    ->first();
            }

            $classe = $inscription?->affectation?->classe;
            $school = $eleve->ecole ?? $inscription?->ecole;

            $payload[] = [
                'link_id' => $link->id,
                'relation' => $link->relation,
                'eleve' => [
                    'id' => $eleve->id,
                    'matricule' => $eleve->matricule,
                    'nom' => $eleve->nom,
                    'prenom' => $eleve->prenom,
                    'nom_complet' => $eleve->nom_complet,
                ],
                'school' => $school ? [
                    'id' => $school->id,
                    'name' => $school->name,
                ] : null,
                'annee_scolaire_id' => $anneeScolaireId,
                'annee_scolaire' => $inscription?->anneeScolaire ? [
                    'id' => $inscription->anneeScolaire->id,
                    'libelle' => $inscription->anneeScolaire->libelle,
                    'code' => $inscription->anneeScolaire->code,
                    'est_active' => (bool) $inscription->anneeScolaire->est_active,
                ] : null,
                'classe_id' => $classe?->id,
                'classe' => $classe ? [
                    'id' => $classe->id,
                    'nom' => $classe->nom,
                    'code' => $classe->code ?? null,
                ] : null,
            ];
        }

        return response()->json(['data' => $payload]);
    }
}
