<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEleveRequest;
use App\Http\Requests\InscriptionStoreRequest;
use App\Http\Requests\UpdateEleveRequest;
use App\Http\Resources\EleveResource;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Inscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\AffectationClasse;
use App\Imports\EleveImport;
use App\Exports\EleveTemplateExport;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class EleveController extends Controller
{
    /**
     * Display a listing of eleves.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Eleve::class);

        $query = Eleve::with(['ecole', 'creator', 'provinceOrigine', 'communeOrigine', 'zoneOrigine', 'collineOrigine', 'niveau']);



        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('nom', 'like', '%' . $request->search . '%')
                    ->orWhere('prenom', 'like', '%' . $request->search . '%');
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
        }

        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        if ($request->filled('sexe')) {
            $query->bySexe($request->sexe);
        }

        if ($request->filled('statut_global')) {
            $query->where('statut_global', $request->statut_global);
        }

        if ($request->filled('classe_id')) {
            $query->whereHas('inscriptions', function ($q) use ($request) {
                $q->whereHas('classe', function ($q2) use ($request) {
                    $q2->where('id', $request->classe_id);
                })->where('statut', 'ACTIVE');
            });
        }

        // Niveau scolaire filter
        if ($request->filled('niveau_id')) {
            $query->where('niveau_id', $request->niveau_id);
        }

        $eleves = $query->latest()->paginate($request->get('per_page', 15));

        //cache them for 24 hours
        Cache::put('eleves', $eleves, 24 * 60 * 60);

        return response()->json($eleves);
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

            // Extract inscription data if provided
            $classeId = $data['classe_id'] ?? null;
            $anneeScolaire = $data['annee_scolaire'] ?? null;
            unset($data['classe_id'], $data['annee_scolaire']);

            $eleve = Eleve::create($data);

            // Auto-enroll if classe_id provided
            if ($classeId && $anneeScolaire) {
                $classe = Classe::findOrFail($classeId);

                // Check capacity
                if (! $classe->hasCapacity()) {
                    DB::rollBack();

                    return response()->json([
                        'message' => 'La classe est pleine. Capacité maximale atteinte.',
                    ], 422);
                }

                $inscription = Inscription::create([
                    'eleve_id' => $eleve->id,
                    'annee_scolaire' => $anneeScolaire,
                    'date_inscription' => now(),
                    'statut' => 'ACTIVE',
                    'created_by' => Auth::id(),
                ]);

                AffectationClasse::create([
                    'inscription_id' => $inscription->id,
                    'classe_id' => $classeId,
                    'date_affectation' => now(),
                    'est_active' => true,
                    'affecte_par' => Auth::id(),
                ]);

                // Update eleve status to ACTIF
                $eleve->update(['statut' => 'ACTIF']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Élève créé avec succès',
                'eleve' => $eleve->load(['ecole', 'provinceOrigine', 'communeOrigine', 'zoneOrigine', 'collineOrigine', 'niveau', 'inscriptions.classe']),
            ], 201);
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
   // $this->authorize('view', $eleve);

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
        'inscriptions.classe.niveau'
    ]);

    return response()->json([
        'success' => true,
        'data' => new EleveResource($eleve)
    ]);
}

    /**
     * Update the specified eleve.
     */
    public function update(UpdateEleveRequest $request, Eleve $eleve): JsonResponse
    {
        $this->authorize('update', $eleve);

        $eleve->update($request->validated());

        return response()->json([
            'message' => 'Élève mis à jour avec succès',
            'eleve' => $eleve->load(['ecole', 'provinceOrigine', 'communeOrigine', 'zoneOrigine', 'collineOrigine', 'niveau']),
        ]);
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
                'message' => 'Erreur: ' . $e->getMessage(),
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

        return response()->json($eleves);
    }

    /**
     * Get eleves for a specific classe.
     */
    public function byClasse(Request $request, int $classeId): JsonResponse
    {
        $classe = Classe::findOrFail($classeId);
        $this->authorize('view', $classe);

        $eleves = $classe->eleves()
            ->wherePivot('statut', 'ACTIVE')
            ->get();

        return response()->json($eleves);
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
             // Add other fields from data as needed, filtering out classe_id
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
                'statut' => 'ACTIVE'
            ]
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
     * Get eleve statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Eleve::class);

        $query = Eleve::query();

        if ($request->filled('school_id')) {
            $query->bySchool($request->school_id);
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
        ];

        return response()->json($stats);
    }

    /**
     * Update the niveau (grade level) of an eleve: promotion or redoublement.
     */
    public function updateNiveau(Request $request, Eleve $eleve): JsonResponse
{
    $this->authorize('update', $eleve);

    $request->validate([
        'niveau_id' => ['required_without:redoublant', 'nullable', 'exists:niveaux_scolaires,id'],
        'annee_scolaire' => ['required', 'string'],
        'redoublant' => ['sometimes', 'boolean'],
    ]);

    DB::beginTransaction();

    try {
        $isRedoublant = $request->boolean('redoublant', false);

        // Récupérer les classes actives AVANT de les désactiver
        $classesActives = DB::table('eleve_class')
            ->where('eleve_id', $eleve->id)
            ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
            ->pluck('classe_id');

        if (isset($request->niveau_id) && ! $isRedoublant) {
            // Validation de la progression (Interdire le retour en arrière ou le maintien du même niveau)
            $newNiveau = \App\Models\Niveau::find($request->niveau_id);
            $currentNiveau = $eleve->niveau; // Relation BelongsTo Niveau

            if ($currentNiveau && $newNiveau) {
                $currentOrdre = (int)($currentNiveau->ordre ?? 0);
                $targetOrdre = (int)($newNiveau->ordre ?? 0);

                if ($currentOrdre > 0 && $targetOrdre <= $currentOrdre) {
                     return response()->json([
                        'message' => 'L\'élève ne peut pas être promu vers un niveau inférieur ou identique au niveau actuel.',
                        'current_ordre' => $currentOrdre,
                        'target_ordre' => $targetOrdre
                    ], 422);
                }
            }

            $eleve->niveau_id = $request->niveau_id;
        }
        if ($isRedoublant) {
            $eleve->est_redoublant = true;
        }
        $eleve->save();

        $currentInscription = $eleve->inscriptions()->latest()->first();

        if ($currentInscription) {
            AffectationClasse::where('inscription_id', $currentInscription->id)
                ->where('est_active', true)
                ->update([
                    'est_active' => false,
                    'date_fin' => now(),
                    'motif_changement' => $isRedoublant ? 'Redoublement' : 'Promotion de niveau',
                ]);

            if ($isRedoublant) {
                $currentInscription->update(['est_redoublant' => true]);
            } else {
                $currentInscription->update(['niveau_demande_id' => $request->niveau_id]);
            }
        }

        // Désactiver dans le pivot
        DB::table('eleve_class')
            ->where('eleve_id', $eleve->id)
            ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
            ->update([
                'statut' => 'INACTIVE',
                'updated_at' => now(),
            ]);

        // ✅ Recalculer l'effectif de chaque classe quittée
        foreach ($classesActives as $classeId) {
            $newEffectif = DB::table('eleve_class')
                ->where('classe_id', $classeId)
                ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                ->count();

            DB::table('classes')
                ->where('id', $classeId)
                ->update(['effectif' => $newEffectif, 'updated_at' => now()]);
        }

        DB::commit();

        $message = $isRedoublant
            ? 'Élève marqué comme redoublant avec succès'
            : 'Élève promu au niveau supérieur avec succès';

        return response()->json([
            'message' => $message,
            'eleve' => $eleve->load(['niveau', 'ecole', 'inscriptions.classe.niveau']),
        ]);

    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}

    /**
     * Transfer the student to another school.
     */
    public function transfertEtablissement(Request $request, Eleve $eleve): JsonResponse
    {
        $this->authorize('update', $eleve);

        $validated = $request->validate([
            'ecole_destination_id' => ['required', 'exists:schools,id'],
            'motif' => ['nullable', 'string'],
            'niveau_cible' => ['nullable', 'string'],
            'validation_avancement' => ['nullable', 'boolean'],
        ]);

        DB::beginTransaction();
        try {
            // ✅ Récupérer les classes actives AVANT de les désactiver
            $classesActives = DB::table('eleve_class')
                ->where('eleve_id', $eleve->id)
                ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                ->pluck('classe_id');

            $eleve->school_id = $validated['ecole_destination_id'];
            $eleve->statut_global = 'TRANSFERE';
            $eleve->save();

            // Deactivate all current class assignments in this school
            $activeInscription = $eleve->activeInscription;
            if ($activeInscription) {
                // 1. Deactivate in AffectationClasse
                AffectationClasse::where('inscription_id', $activeInscription->id)
                    ->where('est_active', true)
                    ->update([
                        'est_active' => false,
                        'date_fin' => now(),
                        'motif_changement' => 'Transfert établissement'
                    ]);
            }

            // 2. Deactivate in eleve_class pivot UNCONDITIONALLY
            DB::table('eleve_class')
                ->where('eleve_id', $eleve->id)
                ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                ->update([
                    'statut' => 'INACTIVE',
                    'updated_at' => now()
                ]);

            // ✅ Recalculer l'effectif de chaque classe quittée
            foreach ($classesActives as $classeId) {
                $newEffectif = DB::table('eleve_class')
                    ->where('classe_id', $classeId)
                    ->whereIn('statut', ['ACTIVE', 'active', 'ACTIF', 'actif'])
                    ->count();

                DB::table('classes')
                    ->where('id', $classeId)
                    ->update(['effectif' => $newEffectif, 'updated_at' => now()]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Élève transféré avec succès',
                'eleve' => $eleve->load(['ecole']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

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
        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
             $failures = $e->failures();
             $errors = [];
             foreach ($failures as $failure) {
                 $errors[] = "Ligne " . $failure->row() . ": " . implode(', ', $failure->errors());
             }
             return response()->json([
                 'message' => 'Erreur de validation lors de l\'importation',
                 'errors' => $errors
             ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'importation: ' . $e->getMessage(),
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

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $this->authorize('viewAny', Eleve::class);

        return Excel::download(
            new EleveExport(
                schoolId: $request->school_id,
                statut:   $request->statut,
                niveauId: $request->niveau_id,
            ),
            'eleves_' . now()->format('Y-m-d') . '.xlsx'
        );
    }
}
