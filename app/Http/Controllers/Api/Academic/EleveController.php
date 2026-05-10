<?php

namespace App\Http\Controllers\Api\Academic;

use App\Exports\EleveTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\InscriptionStoreRequest;
use App\Http\Requests\StoreEleveRequest;
use App\Http\Requests\UpdateEleveRequest;
use App\Http\Resources\EleveResource;
use App\Imports\EleveImport;
use App\Models\AffectationClasse;
use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use App\Models\Niveau;
use App\Models\School;
use App\Services\AcademicYearService;
use App\Services\TransitionScolaireService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EleveController extends Controller
{
    /**
     * Display a listing of eleves.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Eleve::class);

        $query = Eleve::with(['ecole', 'creator', 'provinceOrigine', 'communeOrigine', 'zoneOrigine', 'collineOrigine', 'niveau']);

        // Resolve which school year to display
        $anneeScolaireId = $request->filled('annee_scolaire_id')
            ? $request->integer('annee_scolaire_id')
            : AcademicYearService::currentId();

        if ($anneeScolaireId) {
            $query->where(function ($q) use ($anneeScolaireId, $request) {
                $q->whereHas('inscriptions', function ($q2) use ($anneeScolaireId, $request) {
                    $q2->withoutGlobalScopes()
                        ->where('annee_scolaire_id', $anneeScolaireId)
                        ->where('statut_academique', '!=', 'transfere');

                    if ($request->filled('school_id')) {
                        $q2->where('school_id', $request->school_id);
                    }
                });

                $q->orWhere(function ($q3) use ($request) {
                    $q3->doesntHave('inscriptions');
                    if ($request->filled('school_id')) {
                        $q3->where('school_id', $request->school_id);
                    }
                });
            });

            // Eager-load the inscription for the consulted year (with niveau, classe, school)
            $query->with(['inscriptions' => function ($q) use ($anneeScolaireId, $request) {
                $q->withoutGlobalScopes()
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->with(['niveauDemande', 'ecole', 'affectation.classe'])
                    ->latest();

                if ($request->filled('school_id')) {
                    $q->where('school_id', $request->school_id);
                }
            }]);
        }

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('school_id') && ! $anneeScolaireId) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('niveau_id')) {
            if ($anneeScolaireId) {
                $query->where(function ($q) use ($anneeScolaireId, $request) {
                    $q->whereHas('inscriptions', function ($q2) use ($anneeScolaireId, $request) {
                        $q2->withoutGlobalScopes()
                            ->where('annee_scolaire_id', $anneeScolaireId)
                            ->where('niveau_demande_id', $request->niveau_id);
                    })
                        ->orWhere(function ($q3) use ($request) {
                            $q3->doesntHave('inscriptions')
                                ->where('niveau_id', $request->niveau_id);
                        });
                });
            } else {
                $query->where('niveau_id', $request->niveau_id);
            }
        }

        if ($request->filled('sexe')) {
            $query->bySexe($request->sexe);
        }

        if ($request->filled('statut_global')) {
            $query->where('statut_global', $request->statut_global);
        }

        if ($request->filled('classe_id')) {
            $query->whereHas('inscriptions', function ($q) use ($request, $anneeScolaireId) {
                $q->withoutGlobalScopes()
                    ->whereHas('affectation', function ($q2) use ($request) {
                        $q2->where('classe_id', $request->classe_id);
                    });

                if ($anneeScolaireId) {
                    $q->where('annee_scolaire_id', $anneeScolaireId);
                }
            });
        }

        $eleves = $query->latest()->paginate($request->get('per_page', 15));

        // Attach the consulted year ID so the resource can pick the right inscription
        $eleves->getCollection()->each(function ($eleve) use ($anneeScolaireId) {
            $eleve->setAttribute('_annee_scolaire_consultee_id', $anneeScolaireId);
        });

        return EleveResource::collection($eleves)->response();
    }

    /**
     * Store a newly created eleve.
     */
    public function store(StoreEleveRequest $request): JsonResponse
    {
        $data = $request->validated();

        DB::beginTransaction();

        try {
            $data['created_by'] = Auth::id();

            $classeId = $data['classe_id'] ?? null;
            unset($data['classe_id'], $data['annee_scolaire']);

            $eleve = Eleve::create($data);

            if ($classeId) {
                $classe = Classe::findOrFail($classeId);

                if (! $classe->hasCapacity()) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'La classe est pleine. Capacité maximale atteinte.',
                    ], 422);
                }

                $anneeScolaire = AnneeScolaire::current();

                if (! $anneeScolaire) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'Aucune année scolaire active.',
                    ], 422);
                }

                $prefix = 'INS';
                $year = $anneeScolaire->date_debut?->format('Y') ?? date('Y');
                $sequence = Inscription::withoutGlobalScopes()->count() + 1;

                $inscription = Inscription::withoutGlobalScopes()->create([
                    'numero_inscription' => sprintf('%s%s%06d', $prefix, $year, $sequence),
                    'eleve_id' => $eleve->id,
                    'annee_scolaire_id' => $anneeScolaire->id,
                    'school_id' => $classe->school_id,
                    'niveau_demande_id' => $classe->niveau_id,
                    'type_inscription' => 'nouvelle',
                    'statut' => 'valide',
                    'statut_academique' => 'en_cours',
                    'date_inscription' => now()->toDateString(),
                    'date_soumission' => now(),
                    'date_validation' => now(),
                    'est_redoublant' => false,
                    'created_by' => Auth::id(),
                    'valide_par' => Auth::id(),
                ]);

                AffectationClasse::create([
                    'inscription_id' => $inscription->id,
                    'classe_id' => $classeId,
                    'date_affectation' => now(),
                    'est_active' => true,
                    'affecte_par' => Auth::id(),
                ]);

                $classe->eleves()->syncWithoutDetaching([
                    $eleve->id => [
                        'annee_scolaire' => $anneeScolaire->code,
                        'date_inscription' => now(),
                        'statut' => 'ACTIVE',
                    ],
                ]);

                $newEffectif = DB::table('eleve_class')
                    ->where('classe_id', $classeId)
                    ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                    ->count();
                $classe->update(['effectif' => $newEffectif]);

                $eleve->update(['statut_global' => 'actif']);
            }

            DB::commit();

            $eleve->load(['ecole', 'provinceOrigine', 'communeOrigine', 'zoneOrigine', 'collineOrigine', 'niveau', 'inscriptions.affectation.classe']);

            return (new EleveResource($eleve))
                ->additional(['message' => 'Élève créé avec succès'])
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified eleve.
     */
    public function show($id): JsonResponse
    {
        $eleve = Eleve::findOrFail($id);

        $eleve->load([
            'ecole',
            'ecoleOrigine',
            'provinceOrigine',
            'communeOrigine',
            'zoneOrigine',
            'collineOrigine',
            'niveau',
            'creator',
            'classes',
            'parents',
            'inscriptions' => function ($q) {
                $q->withoutGlobalScopes()
                    ->with(['anneeScolaire', 'ecole', 'niveauDemande', 'affectation.classe.niveau'])
                    ->orderByDesc('annee_scolaire_id');
            },
        ]);

        return (new EleveResource($eleve))->response();
    }

    /**
     * Update the specified eleve.
     */
    public function update(UpdateEleveRequest $request, Eleve $eleve): JsonResponse
    {
        $this->authorize('update', $eleve);

        $eleve->update($request->validated());

        $eleve->load(['ecole', 'provinceOrigine', 'communeOrigine', 'zoneOrigine', 'collineOrigine', 'niveau']);

        return (new EleveResource($eleve))
            ->additional(['message' => 'Élève mis à jour avec succès'])
            ->response();
    }

    /**
     * Remove the specified eleve.
     */
    public function destroy($id): JsonResponse
    {
        $eleve = Eleve::findOrFail($id);

        $this->authorize('delete', $eleve);

        if ($eleve->inscriptions()->where('statut', 'ACTIVE')->exists()) {
            return response()->json([
                'message' => 'Impossible de supprimer cet élève car il a des inscriptions actives.',
            ], 422);
        }

        try {
            DB::table('eleves')
                ->where('id', $eleve->id)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Delete eleve failed', ['id' => $id, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Erreur: '.$e->getMessage(),
            ], 500);
        }

        return response()->json(['message' => 'Élève supprimé avec succès']);
    }

    /**
     * Get eleves for a specific school.
     */
    public function bySchool(Request $request, int $schoolId): JsonResponse
    {
        $query = Eleve::with(['provinceOrigine', 'communeOrigine', 'zoneOrigine', 'collineOrigine', 'niveau'])->bySchool($schoolId);

        if ($request->filled('statut')) {
            $query->whereRaw('LOWER(statut_global) = ?', [strtolower($request->statut)]);
        } else {
            $query->actif();
        }

        $eleves = $query->get();

        return EleveResource::collection($eleves)->response();
    }

    /**
     * Get eleves for a specific classe (via inscriptions/affectations).
     */
    public function byClasse(Request $request, int $classeId): JsonResponse
    {
        $classe = Classe::findOrFail($classeId);
        $this->authorize('view', $classe);

        $anneeScolaireId = $classe->annee_scolaire_id;

        $eleves = Eleve::whereHas('inscriptions', function ($q) use ($classeId, $anneeScolaireId) {
            $q->withoutGlobalScopes()
                ->where('annee_scolaire_id', $anneeScolaireId)
                ->whereHas('affectation', function ($q2) use ($classeId) {
                    $q2->where('classe_id', $classeId);
                });
        })
            ->with(['inscriptions' => function ($q) use ($classeId, $anneeScolaireId) {
                $q->withoutGlobalScopes()
                    ->where('annee_scolaire_id', $anneeScolaireId)
                    ->whereHas('affectation', fn ($q2) => $q2->where('classe_id', $classeId))
                    ->with(['niveauDemande', 'ecole', 'affectation.classe']);
            }])
            ->get();

        $eleves->each(function ($eleve) use ($anneeScolaireId) {
            $eleve->setAttribute('_annee_scolaire_consultee_id', $anneeScolaireId);
        });

        return EleveResource::collection($eleves)->response();
    }

    /**
     * Enroll an eleve in a classe.
     */
    public function enroll(InscriptionStoreRequest $request): JsonResponse
    {
        $data = $request->validated();

        $eleve = Eleve::findOrFail($data['eleve_id']);
        $classe = Classe::findOrFail($data['classe_id']);

        // Check if eleve can enroll
        if (! $eleve->canEnroll()) {
            return response()->json([
                'message' => 'Cet élève ne peut pas être inscrit (statut: '.$eleve->statut.').',
            ], 422);
        }

        // Check if already enrolled in this classe
        if ($eleve->isEnrolledInClass($data['classe_id'])) {
            return response()->json([
                'message' => 'Cet élève est déjà inscrit dans cette classe.',
            ], 422);
        }

        // Check capacity
        if (! $classe->hasCapacity()) {
            return response()->json([
                'message' => 'La classe est pleine. Capacité maximale atteinte.',
            ], 422);
        }

        // Check if schools match
        if ($eleve->school_id !== $classe->school_id) {
            return response()->json([
                'message' => 'L\'élève et la classe doivent appartenir à la même école.',
            ], 422);
        }

        $data['created_by'] = Auth::id();
        $inscription = Inscription::create([
            'eleve_id' => $data['eleve_id'],
            'annee_scolaire' => $data['annee_scolaire'],
            'date_inscription' => $data['date_inscription'] ?? now(),
            'statut' => 'ACTIVE',
            'created_by' => Auth::id(),
        ]);

        AffectationClasse::create([
            'inscription_id' => $inscription->id,
            'classe_id' => $data['classe_id'],
            'date_affectation' => now(),
            'est_active' => true,
            'affecte_par' => Auth::id(),
        ]);

        // Also update eleve_class pivot for effectif count consistency
        $classe->eleves()->syncWithoutDetaching([
            $eleve->id => [
                'annee_scolaire' => $data['annee_scolaire'],
                'date_inscription' => now(),
                'statut' => 'ACTIVE',
            ],
        ]);

        // Update eleve status if needed
        if ($eleve->statut === 'INSCRIT') {
            $eleve->update(['statut' => 'ACTIF']);
        }

        return response()->json([
            'message' => 'Élève inscrit avec succès',
            'inscription' => $inscription->load(['eleve', 'classe']),
        ], 201);
    }

    /**
     * Unenroll an eleve from a classe.
     */
    public function unenroll(Inscription $inscription): JsonResponse
    {
        $this->authorize('delete', $inscription);

        if ($inscription->statut !== 'ACTIVE') {
            return response()->json([
                'message' => 'Cette inscription n\'est pas active.',
            ], 422);
        }

        $inscription->update(['statut' => 'ANNULEE']);

        return response()->json(['message' => 'Inscription annulée avec succès']);
    }

    /**
     * Transfer an eleve to another classe.
     */
    public function transfer(Request $request, Inscription $inscription): JsonResponse
    {
        $this->authorize('update', $inscription);

        $request->validate([
            'classe_id' => ['required', 'exists:classes,id'],
            'annee_scolaire' => ['required', 'string', 'regex:/^\d{4}-\d{4}$/'],
        ]);

        $newClasse = Classe::findOrFail($request->classe_id);

        // Check capacity
        if (! $newClasse->hasCapacity()) {
            return response()->json([
                'message' => 'La nouvelle classe est pleine. Capacité maximale atteinte.',
            ], 422);
        }

        $newInscription = $inscription->transfer($request->classe_id, $request->annee_scolaire);

        if (! $newInscription) {
            return response()->json([
                'message' => 'Impossible d\'effectuer le transfert.',
            ], 422);
        }

        return response()->json([
            'message' => 'Élève transféré avec succès',
            'inscription' => $newInscription->load(['eleve', 'classe']),
        ]);
    }

    /**
     * Get eleve statistics scoped to the active (or requested) school year.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Eleve::class);

        $anneeScolaireId = $request->filled('annee_scolaire_id')
            ? $request->integer('annee_scolaire_id')
            : AcademicYearService::currentId();

        $query = Eleve::query();

        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        // Scope to students who have an inscription in the target school year
        if ($anneeScolaireId) {
            $query->whereHas('inscriptions', function ($q) use ($anneeScolaireId, $request) {
                $q->withoutGlobalScopes()
                    ->where('annee_scolaire_id', $anneeScolaireId);

                if ($request->filled('school_id')) {
                    $q->where('school_id', $request->school_id);
                }
            });
        }

        $stats = [
            'total' => $query->count(),
            'by_status' => [
                'actif' => (clone $query)->actif()->count(),
                'inactif' => (clone $query)->whereRaw('LOWER(statut_global) = ?', ['inactif'])->count(),
                'transfere' => (clone $query)->whereRaw('LOWER(statut_global) = ?', ['transfere'])->count(),
                'abandonne' => (clone $query)->whereRaw('LOWER(statut_global) = ?', ['abandonne'])->count(),
                'decede' => (clone $query)->whereRaw('LOWER(statut_global) = ?', ['decede'])->count(),
            ],
            'by_sexe' => [
                'garcons' => (clone $query)->bySexe('M')->count(),
                'filles' => (clone $query)->bySexe('F')->count(),
            ],
            'annee_scolaire_id' => $anneeScolaireId,
        ];

        return response()->json($stats);
    }

    /**
     * Update the niveau (grade level) of an eleve: promotion or redoublement.
     * Now delegates to TransitionScolaireService for proper inter-year tracking.
     *
     * Accepts either:
     *   - annee_scolaire_destination_id (int) — the ID directly
     *   - annee_scolaire (string, e.g. "2025-2026") — resolved by code/libelle
     */
    public function updateNiveau(Request $request, Eleve $eleve): JsonResponse
    {
        $this->authorize('update', $eleve);

        $request->validate([
            'annee_scolaire_destination_id' => ['required_without:annee_scolaire', 'nullable', 'exists:annee_scolaires,id'],
            'annee_scolaire' => ['required_without:annee_scolaire_destination_id', 'nullable', 'string'],
            'redoublant' => ['sometimes', 'boolean'],
        ]);

        $isRedoublant = $request->boolean('redoublant', false);

        $nouvelleAnnee = null;
        if ($request->filled('annee_scolaire_destination_id')) {
            $nouvelleAnnee = AnneeScolaire::findOrFail($request->annee_scolaire_destination_id);
        } elseif ($request->filled('annee_scolaire')) {
            $nouvelleAnnee = AnneeScolaire::where('code', $request->annee_scolaire)
                ->orWhere('libelle', $request->annee_scolaire)
                ->first();

            if (! $nouvelleAnnee) {
                return response()->json([
                    'message' => "Année scolaire '{$request->annee_scolaire}' introuvable.",
                ], 422);
            }
        }

        if (! $nouvelleAnnee) {
            return response()->json([
                'message' => 'Veuillez spécifier une année scolaire de destination.',
            ], 422);
        }

        $service = app(TransitionScolaireService::class);

        try {
            // Deactivate eleve_class pivot entries for effectif recalculation
            $classesActives = DB::table('eleve_class')
                ->where('eleve_id', $eleve->id)
                ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                ->pluck('classe_id');

            if ($isRedoublant) {
                $service->redoubler($eleve, $nouvelleAnnee);
            } else {
                $service->promouvoir($eleve, $nouvelleAnnee);
            }

            DB::table('eleve_class')
                ->where('eleve_id', $eleve->id)
                ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                ->update(['statut' => 'INACTIVE', 'updated_at' => now()]);

            foreach ($classesActives as $classeId) {
                $newEffectif = DB::table('eleve_class')
                    ->where('classe_id', $classeId)
                    ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                    ->count();
                DB::table('classes')
                    ->where('id', $classeId)
                    ->update(['effectif' => $newEffectif, 'updated_at' => now()]);
            }

            $message = $isRedoublant
                ? 'Élève marqué comme redoublant avec succès'
                : 'Élève promu au niveau supérieur avec succès';

            $eleve->refresh();
            $eleve->load(['niveau', 'ecole', 'inscriptions' => fn ($q) => $q->withoutGlobalScopes()->with('affectation.classe.niveau')]);

            return (new EleveResource($eleve))
                ->additional(['message' => $message])
                ->response();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    /**
     * Transfer the student to another school.
     * Now delegates to TransitionScolaireService for proper inscription tracking.
     */
    public function transfertEtablissement(Request $request, Eleve $eleve): JsonResponse
    {
        $this->authorize('update', $eleve);

        $validated = $request->validate([
            'ecole_destination_id' => ['required', 'exists:schools,id'],
            'niveau_cible_id' => ['nullable', 'exists:niveaux_scolaires,id'],
            'niveau_cible' => ['nullable', 'string'],
            'motif' => ['nullable', 'string'],
            'validation_avancement' => ['nullable', 'boolean'],
        ]);

        $service = app(TransitionScolaireService::class);

        try {
            $classesActives = DB::table('eleve_class')
                ->where('eleve_id', $eleve->id)
                ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                ->pluck('classe_id');

            $nouvelEtablissement = School::findOrFail($validated['ecole_destination_id']);
            $niveauCible = null;
            if (! empty($validated['niveau_cible_id'])) {
                $niveauCible = Niveau::find($validated['niveau_cible_id']);
            }

            $anneeActive = AnneeScolaire::current();

            if (! $anneeActive) {
                return response()->json(['message' => 'Aucune année scolaire active.'], 422);
            }

            $service->transferer($eleve, $nouvelEtablissement, $niveauCible, $anneeActive);

            DB::table('eleve_class')
                ->where('eleve_id', $eleve->id)
                ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                ->update(['statut' => 'INACTIVE', 'updated_at' => now()]);

            foreach ($classesActives as $classeId) {
                $newEffectif = DB::table('eleve_class')
                    ->where('classe_id', $classeId)
                    ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                    ->count();
                DB::table('classes')
                    ->where('id', $classeId)
                    ->update(['effectif' => $newEffectif, 'updated_at' => now()]);
            }

            $eleve->refresh();
            $eleve->load('ecole');

            return (new EleveResource($eleve))
                ->additional(['message' => 'Élève transféré avec succès'])
                ->response();
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erreur lors du transfert: '.$e->getMessage()], 500);
        }
    }

    /**
     * Import eleves via Excel.
     */
    public function importExcel(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            Excel::import(new EleveImport, $request->file('file'));

            return response()->json([
                'message' => 'Importation réussie',
            ]);
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            foreach ($failures as $failure) {
                $errors[] = 'Ligne '.$failure->row().': '.implode(', ', $failure->errors());
            }

            return response()->json([
                'message' => 'Erreur de validation lors de l\'importation',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'importation: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download Excel template for import.
     */
    public function downloadTemplate()
    {
        if (ob_get_level()) {
            ob_end_clean();
        }

        return Excel::download(new EleveTemplateExport, 'template_import_eleves.xlsx');
    }

    public function export(Request $request): BinaryFileResponse
    {
        $this->authorize('viewAny', Eleve::class);

        return Excel::download(
            new EleveExport(
                schoolId: $request->school_id,
                statut: $request->statut,
                niveauId: $request->niveau_id,
            ),
            'eleves_'.now()->format('Y-m-d').'.xlsx'
        );
    }
}
