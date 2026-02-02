<?php

namespace App\Http\Controllers\Api\Inscription;

use App\Http\Controllers\Controller;
use App\Http\Requests\InscriptionStoreRequest;
use App\Http\Requests\InscriptionUpdateRequest;
use App\Http\Resources\Api\InscriptionEleveResource;
use App\Models\InscriptionEleve;
use App\Models\AffectationEleve;
use App\Models\MouvementEleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InscriptionController extends Controller
{
    /**
     * GET /api/inscriptions-eleves
     */
    public function index(Request $request)
    {
        $query = InscriptionEleve::with([
            'eleve',
            'classe',
            'ecole',
            'anneeScolaire',
            'niveauDemande',
        ])->latest();

        if ($request->filled('annee_scolaire_id')) {
            $query->where('annee_scolaire_id', $request->annee_scolaire_id);
        }

        if ($request->filled('ecole_id')) {
            $query->where('ecole_id', $request->ecole_id);
        }

        return InscriptionEleveResource::collection(
            $query->paginate(15)
        );
    }

    /**
     * POST /api/inscriptions-eleves
     */
    public function store(InscriptionStoreRequest $request)
    {
        $data = $request->validated();

        $inscription = DB::transaction(function () use ($data) {

            $inscription = InscriptionEleve::create($data['inscription']);

            AffectationEleve::create([
                'inscription_id'   => $inscription->id,
                ...$data['affectation'],
            ]);

            if (isset($data['mouvement'])) {
                MouvementEleve::create([
                    'eleve_id'          => $inscription->eleve_id,
                    'annee_scolaire_id' => $inscription->annee_scolaire_id,
                    ...$data['mouvement'],
                ]);
            }

            return $inscription;
        });

        return response()->json([
            'success' => true,
            'message' => 'Inscription créée avec succès',
            'data' => new InscriptionEleveResource(
                $inscription->load([
                    'eleve',
                    'classe',
                    'ecole',
                    'anneeScolaire',
                    'niveauDemande',
                ])
            )
        ], 201);
    }

    /**
     * GET /api/inscriptions-eleves/{inscriptionEleve}
     */
    public function show(InscriptionEleve $inscriptionEleve)
    {
        return new InscriptionEleveResource(
            $inscriptionEleve->load([
                'eleve',
                'classe',
                'ecole',
                'anneeScolaire',
                'niveauDemande',
                'affectations',
                'mouvements',
            ])
        );
    }

    /**
     * PUT /api/inscriptions-eleves/{inscriptionEleve}
     */
    public function update(
        InscriptionUpdateRequest $request,
        InscriptionEleve $inscriptionEleve
    ) {
        $data = $request->validated();

        DB::transaction(function () use ($data, $inscriptionEleve) {

            if (isset($data['inscription'])) {
                $inscriptionEleve->update($data['inscription']);
            }

            if (isset($data['affectation'])) {
                $inscriptionEleve->affectations()
                    ->where('est_active', true)
                    ->update([
                        'est_active' => false,
                        'date_fin' => now(),
                    ]);

                AffectationEleve::create([
                    'inscription_id' => $inscriptionEleve->id,
                    ...$data['affectation'],
                ]);
            }

            if (isset($data['mouvement'])) {
                MouvementEleve::create([
                    'eleve_id'          => $inscriptionEleve->eleve_id,
                    'annee_scolaire_id' => $inscriptionEleve->annee_scolaire_id,
                    ...$data['mouvement'],
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Inscription mise à jour',
            'data' => new InscriptionEleveResource(
                $inscriptionEleve->refresh()
            )
        ]);
    }

    /**
     * DELETE /api/inscriptions-eleves/{inscriptionEleve}
     */
    public function destroy(InscriptionEleve $inscriptionEleve)
    {
        $inscriptionEleve->update(['statut' => 'ANNULEE']);
        $inscriptionEleve->delete();

        return response()->json([
            'success' => true,
            'message' => 'Inscription annulée'
        ], 204);

    }
}
