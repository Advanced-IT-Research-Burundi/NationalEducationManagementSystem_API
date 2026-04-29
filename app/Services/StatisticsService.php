<?php

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Batiment;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Classe;
use App\Models\Financement;
use App\Models\InscriptionEleve;
use App\Models\Province;
use App\Models\Salle;
use App\Models\School;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Cache TTL in seconds (30 minutes)
     */
    const CACHE_TTL = 1800;

    /**
     * Check if the filters array contains any geographic constraint.
     */
    protected function hasGeoFilters(array $filters): bool
    {
        return ! empty($filters['ministere_id'])
            || ! empty($filters['province_id'])
            || ! empty($filters['commune_id'])
            || ! empty($filters['zone_id'])
            || ! empty($filters['school_id'])
            || ! empty($filters['niveau']);
    }

    /**
     * Build a cache key from method name and filters
     */
    protected function cacheKey(string $method, array $filters = []): string
    {
        $filterHash = md5(json_encode($filters));

        return "stats:{$method}:{$filterHash}";
    }

    /**
     * Get the active school year or a specific one from filters
     */
    protected function resolveAnneeScolaire(array $filters): ?AnneeScolaire
    {
        if (! empty($filters['annee_scolaire_id'])) {
            return AnneeScolaire::find($filters['annee_scolaire_id']);
        }

        return AnneeScolaire::current();
    }

    /**
     * Apply geographic filters to an Enseignant query, checking both the
     * primary school (school_id) and the N:N pivot (enseignant_school).
     */
    protected function applyEnseignantGeoFilters($query, array $filters): void
    {
        $query->where(function ($q) use ($filters) {
            $q->whereHas('school', function ($sq) use ($filters) {
                $sq->where('schools.statut', 'ACTIVE');
                $this->applyGeoFilters($sq, $filters, 'schools');
            })->orWhereHas('ecoles', function ($sq) use ($filters) {
                $sq->where('schools.statut', 'ACTIVE');
                $this->applyGeoFilters($sq, $filters, 'schools');
            });
        });
    }

    /**
     * Apply geographic filters to a query on the 'schools' table
     */
    protected function applyGeoFilters($query, array $filters, string $tableAlias = '')
    {
        $prefix = $tableAlias ? "{$tableAlias}." : '';

        if (! empty($filters['ministere_id'])) {
            $query->where("{$prefix}ministere_id", $filters['ministere_id']);
        }
        if (! empty($filters['province_id'])) {
            $query->where("{$prefix}province_id", $filters['province_id']);
        }
        if (! empty($filters['commune_id'])) {
            $query->where("{$prefix}commune_id", $filters['commune_id']);
        }
        if (! empty($filters['zone_id'])) {
            $query->where("{$prefix}zone_id", $filters['zone_id']);
        }
        if (! empty($filters['school_id'])) {
            $query->where("{$prefix}id", $filters['school_id']);
        }
        if (! empty($filters['niveau'])) {
            $query->where("{$prefix}niveau", $filters['niveau']);
        }

        return $query;
    }

    /**
     * Global statistics: total schools, students, teachers
     */
    public function getGlobalStats(array $filters = []): array
    {
        return Cache::remember($this->cacheKey('global', $filters), self::CACHE_TTL, function () use ($filters) {
            // Total schools
            $schoolsQuery = School::query()->where('statut', 'ACTIVE');
            $this->applyGeoFilters($schoolsQuery, $filters);
            $totalEcoles = $schoolsQuery->count();

            // Schools by type
            $schoolsParTypeQuery = School::query()->where('statut', 'ACTIVE');
            $this->applyGeoFilters($schoolsParTypeQuery, $filters);
            $schoolsParType = $schoolsParTypeQuery
                ->select('type_ecole', DB::raw('COUNT(*) as total'))
                ->groupBy('type_ecole')
                ->pluck('total', 'type_ecole')
                ->toArray();

            // Schools by niveau
            $schoolsParNiveauQuery = School::query()->where('statut', 'ACTIVE');
            $this->applyGeoFilters($schoolsParNiveauQuery, $filters);
            $schoolsParNiveau = $schoolsParNiveauQuery
                ->select('niveau', DB::raw('COUNT(*) as total'))
                ->groupBy('niveau')
                ->pluck('total', 'niveau')
                ->toArray();

            // Total students (active)
            $elevesQuery = Eleve::query()->where('statut_global', 'actif');
            if ($this->hasGeoFilters($filters)) {
                $elevesQuery->whereHas('school', function ($q) use ($filters) {
                    $q->where('schools.statut', 'ACTIVE');
                    $this->applyGeoFilters($q, $filters, 'schools');
                });
            }
            $totalEleves = $elevesQuery->count();

            // Total teachers (active) — includes teachers assigned via pivot
            $enseignantsQuery = Enseignant::query()->where('statut', 'ACTIF');
            if ($this->hasGeoFilters($filters)) {
                $this->applyEnseignantGeoFilters($enseignantsQuery, $filters);
            }
            $totalEnseignants = $enseignantsQuery->count();

            // Total classes (active)
            $classesQuery = Classe::query()->where('classes.statut', 'ACTIVE');
            if ($this->hasGeoFilters($filters)) {
                $classesQuery->whereHas('school', function ($q) use ($filters) {
                    $q->where('schools.statut', 'ACTIVE');
                    $this->applyGeoFilters($q, $filters, 'schools');
                });
            }
            $totalClasses = $classesQuery->count();

            $typeLabels = [
                'PUBLIQUE' => 'Public',
                'PRIVEE' => 'Privé',
                'ECC' => 'Confessionnel',
                'AUTRE' => 'Autre',
            ];

            $niveauLabels = [
                'FONDAMENTAL' => 'Fondamental',
                'POST_FONDAMENTAL' => 'Post-fondamental',
                'SECONDAIRE' => 'Secondaire',
                'SUPERIEUR' => 'Supérieur',
            ];

            $repartitionEtablissements = collect($schoolsParType)
                ->map(fn (int $total, string $type) => [
                    'label' => $typeLabels[$type] ?? $type,
                    'value' => $total,
                ])
                ->values()
                ->toArray();

            $repartitionParNiveau = collect($schoolsParNiveau)
                ->map(fn (int $total, string $niveau) => [
                    'label' => $niveauLabels[$niveau] ?? $niveau,
                    'value' => $total,
                ])
                ->values()
                ->toArray();

            return [
                'total_schools' => $totalEcoles,
                'total_eleves' => $totalEleves,
                'total_enseignants' => $totalEnseignants,
                'total_classes' => $totalClasses,
                'schools_par_type' => $schoolsParType,
                'schools_par_niveau' => $schoolsParNiveau,
                'repartition_etablissements' => $repartitionEtablissements,
                'repartition_par_niveau' => $repartitionParNiveau,
            ];
        });
    }

    /**
     * Inscription statistics: gender breakdown, enrollment by level
     */
    public function getInscriptionStats(array $filters = []): array
    {
        return Cache::remember($this->cacheKey('inscription', $filters), self::CACHE_TTL, function () use ($filters) {
            $annee = $this->resolveAnneeScolaire($filters);
            $anneeId = $annee?->id;

            // Gender breakdown
            $genderQuery = Eleve::query()->where('statut_global', 'actif');
            if ($this->hasGeoFilters($filters)) {
                $genderQuery->whereHas('school', function ($q) use ($filters) {
                    $q->where('schools.statut', 'ACTIVE');
                    $this->applyGeoFilters($q, $filters, 'schools');
                });
            }
            $genderBreakdown = $genderQuery
                ->select('sexe', DB::raw('COUNT(*) as total'))
                ->groupBy('sexe')
                ->pluck('total', 'sexe')
                ->toArray();

            $garcons = $genderBreakdown['M'] ?? 0;
            $filles = $genderBreakdown['F'] ?? 0;
            $totalGenre = $garcons + $filles;
            $pourcentageFilles = $totalGenre > 0 ? round(($filles / $totalGenre) * 100, 1) : 0;
            $pourcentageGarcons = $totalGenre > 0 ? round(($garcons / $totalGenre) * 100, 1) : 0;

            // Inscriptions count per year (if annee specified)
            $inscriptionsCount = 0;
            $inscriptionsByType = [];
            if ($anneeId) {
                $inscQuery = InscriptionEleve::query()->where('annee_scolaire_id', $anneeId);
                if ($this->hasGeoFilters($filters)) {
                    $inscQuery->whereHas('ecole', function ($q) use ($filters) {
                        $this->applyGeoFilters($q, $filters, 'schools');
                    });
                }
                $inscriptionsCount = $inscQuery->where('statut', 'valide')->count();

                $inscByTypeQuery = InscriptionEleve::query()
                    ->where('annee_scolaire_id', $anneeId)
                    ->where('statut', 'valide');
                if ($this->hasGeoFilters($filters)) {
                    $inscByTypeQuery->whereHas('ecole', function ($q) use ($filters) {
                        $this->applyGeoFilters($q, $filters, 'schools');
                    });
                }
                $inscriptionsByType = $inscByTypeQuery
                    ->select('type_inscription', DB::raw('COUNT(*) as total'))
                    ->groupBy('type_inscription')
                    ->pluck('total', 'type_inscription')
                    ->toArray();
            }

            // Gender distribution by province
            $genderByProvince = DB::table('eleves')
                ->join('schools', 'eleves.school_id', '=', 'schools.id')
                ->join('provinces', 'schools.province_id', '=', 'provinces.id')
                ->where('eleves.statut_global', 'actif')
                ->where('schools.statut', 'ACTIVE')
                ->select(
                    'provinces.name as province',
                    'eleves.sexe',
                    DB::raw('COUNT(*) as total')
                )
                ->groupBy('provinces.name', 'eleves.sexe')
                ->get()
                ->groupBy('province')
                ->map(function ($group) {
                    $result = ['province' => $group->first()->province, 'garcons' => 0, 'filles' => 0];
                    foreach ($group as $row) {
                        if ($row->sexe === 'M') {
                            $result['garcons'] = $row->total;
                        }
                        if ($row->sexe === 'F') {
                            $result['filles'] = $row->total;
                        }
                    }

                    return $result;
                })
                ->values()
                ->toArray();

            return [
                'repartition_genre' => [
                    'garcons' => $garcons,
                    'filles' => $filles,
                    'pourcentage_garcons' => $pourcentageGarcons,
                    'pourcentage_filles' => $pourcentageFilles,
                ],
                'inscriptions_validees' => $inscriptionsCount,
                'inscriptions_par_type' => $inscriptionsByType,
                'repartition_genre_par_province' => $genderByProvince,
            ];
        });
    }

    /**
     * Performance statistics: exam success rates, averages
     */
    public function getPerformanceStats(array $filters = []): array
    {
        return Cache::remember($this->cacheKey('performance', $filters), self::CACHE_TTL, function () use ($filters) {
            // Overall success rate (average note >= 50/100 or 10/20 depending on scale)
            // Using a threshold of 50% of max note
            $resultatsQuery = DB::table('resultats')
                ->join('inscriptions_examen', 'resultats.inscription_examen_id', '=', 'inscriptions_examen.id')
                ->join('sessions_examen', 'inscriptions_examen.session_id', '=', 'sessions_examen.id')
                ->join('examens', 'sessions_examen.examen_id', '=', 'examens.id');

            if (! empty($filters['annee_scolaire_id'])) {
                $resultatsQuery->where('examens.annee_scolaire_id', $filters['annee_scolaire_id']);
            }

            if ($this->hasGeoFilters($filters)) {
                $resultatsQuery
                    ->join('eleves', 'inscriptions_examen.eleve_id', '=', 'eleves.id')
                    ->join('schools', 'eleves.school_id', '=', 'schools.id');
                $this->applyGeoFilters($resultatsQuery, $filters, 'schools');
            }

            $statsResultats = $resultatsQuery->select(
                DB::raw('COUNT(*) as total_notes'),
                DB::raw('AVG(resultats.note) as moyenne_generale'),
                DB::raw('MAX(resultats.note) as note_max'),
                DB::raw('MIN(resultats.note) as note_min')
            )->first();

            $totalNotes = $statsResultats->total_notes ?? 0;
            $moyenneGenerale = $totalNotes > 0 ? round($statsResultats->moyenne_generale, 2) : null;

            // Success rate: count students with average >= 50%
            // We compute per-student average across their subjects
            $studentsResultsQuery = DB::table('resultats')
                ->join('inscriptions_examen', 'resultats.inscription_examen_id', '=', 'inscriptions_examen.id')
                ->join('sessions_examen', 'inscriptions_examen.session_id', '=', 'sessions_examen.id')
                ->join('examens', 'sessions_examen.examen_id', '=', 'examens.id');

            if (! empty($filters['annee_scolaire_id'])) {
                $studentsResultsQuery->where('examens.annee_scolaire_id', $filters['annee_scolaire_id']);
            }

            if ($this->hasGeoFilters($filters)) {
                $studentsResultsQuery
                    ->join('eleves', 'inscriptions_examen.eleve_id', '=', 'eleves.id')
                    ->join('schools', 'eleves.school_id', '=', 'schools.id');
                $this->applyGeoFilters($studentsResultsQuery, $filters, 'schools');
            }

            $studentAverages = $studentsResultsQuery
                ->select(
                    'inscriptions_examen.eleve_id',
                    DB::raw('AVG(resultats.note) as moyenne')
                )
                ->groupBy('inscriptions_examen.eleve_id')
                ->get();

            $totalStudentsExam = $studentAverages->count();
            // Assuming notes are on 100 scale, pass threshold = 50
            $passedStudents = $studentAverages->where('moyenne', '>=', 50)->count();
            $tauxReussite = $totalStudentsExam > 0 ? round(($passedStudents / $totalStudentsExam) * 100, 1) : null;

            // Average by subject
            $moyenneParMatiere = DB::table('resultats')
                ->select('matiere', DB::raw('AVG(note) as moyenne'), DB::raw('COUNT(*) as total_candidats'))
                ->groupBy('matiere')
                ->orderByDesc('moyenne')
                ->get()
                ->map(fn ($r) => [
                    'matiere' => $r->matiere,
                    'moyenne' => round($r->moyenne, 2),
                    'total_candidats' => $r->total_candidats,
                ])
                ->toArray();

            $tauxParNiveauQuery = DB::table('resultats')
                ->join('inscriptions_examen', 'resultats.inscription_examen_id', '=', 'inscriptions_examen.id')
                ->join('sessions_examen', 'inscriptions_examen.session_id', '=', 'sessions_examen.id')
                ->join('examens', 'sessions_examen.examen_id', '=', 'examens.id')
                ->join('niveaux_scolaires', 'examens.niveau_id', '=', 'niveaux_scolaires.id');

            if (! empty($filters['annee_scolaire_id'])) {
                $tauxParNiveauQuery->where('examens.annee_scolaire_id', $filters['annee_scolaire_id']);
            }

            if ($this->hasGeoFilters($filters)) {
                $tauxParNiveauQuery
                    ->join('eleves', 'inscriptions_examen.eleve_id', '=', 'eleves.id')
                    ->join('schools', 'eleves.school_id', '=', 'schools.id');
                $this->applyGeoFilters($tauxParNiveauQuery, $filters, 'schools');
            }

            $resultatsParNiveau = $tauxParNiveauQuery
                ->select(
                    'niveaux_scolaires.nom as niveau',
                    'niveaux_scolaires.ordre',
                    'inscriptions_examen.eleve_id',
                    DB::raw('AVG(resultats.note) as moyenne')
                )
                ->groupBy('niveaux_scolaires.nom', 'niveaux_scolaires.ordre', 'inscriptions_examen.eleve_id')
                ->get();

            $tauxReussiteParNiveau = $resultatsParNiveau
                ->groupBy('niveau')
                ->map(function ($group, $niveau) {
                    $total = $group->count();
                    $reussis = $group->where('moyenne', '>=', 50)->count();

                    return [
                        'niveau' => $niveau,
                        'ordre' => $group->first()->ordre ?? 0,
                        'taux' => $total > 0 ? round(($reussis / $total) * 100, 1) : 0,
                        'total_candidats' => $total,
                        'candidats_reussis' => $reussis,
                    ];
                })
                ->sortBy('ordre')
                ->values()
                ->toArray();

            return [
                'taux_reussite' => $tauxReussite,
                'moyenne_generale' => $moyenneGenerale,
                'total_candidats' => $totalStudentsExam,
                'candidats_reussis' => $passedStudents,
                'note_max' => $statsResultats->note_max ?? null,
                'note_min' => $statsResultats->note_min ?? null,
                'moyenne_par_matiere' => $moyenneParMatiere,
                'taux_reussite_par_niveau' => $tauxReussiteParNiveau,
            ];
        });
    }

    /**
     * National KPIs: student/teacher ratio, enrollment rate
     */
    public function getNationalKpis(array $filters = []): array
    {
        return Cache::remember($this->cacheKey('kpis', $filters), self::CACHE_TTL, function () use ($filters) {
            $globalStats = $this->getGlobalStats($filters);

            // Ratio élève/enseignant
            $ratioEleveEnseignant = $globalStats['total_enseignants'] > 0
                ? round($globalStats['total_eleves'] / $globalStats['total_enseignants'], 1)
                : null;

            // Performance
            $performanceStats = $this->getPerformanceStats($filters);

            // Inscription stats
            $inscriptionStats = $this->getInscriptionStats($filters);

            return [
                'ratio_eleve_enseignant' => $ratioEleveEnseignant,
                'taux_reussite' => $performanceStats['taux_reussite'],
                'pourcentage_filles' => $inscriptionStats['repartition_genre']['pourcentage_filles'],
                'total_schools' => $globalStats['total_schools'],
                'total_eleves' => $globalStats['total_eleves'],
                'total_enseignants' => $globalStats['total_enseignants'],
                // taux_scolarisation requires population data (not available)
                'taux_scolarisation' => null,
            ];
        });
    }

    /**
     * Evolution of student/teacher counts across school years
     */
    public function getEvolutionEffectifs(array $filters = []): array
    {
        return Cache::remember($this->cacheKey('evolution', $filters), self::CACHE_TTL, function () use ($filters) {
            $annees = AnneeScolaire::orderBy('date_debut', 'asc')->get();

            $inscQuery = DB::table('inscriptions')
                ->where('inscriptions.statut', 'valide')
                ->select('annee_scolaire_id', DB::raw('COUNT(*) as total'))
                ->groupBy('annee_scolaire_id');

            if ($this->hasGeoFilters($filters)) {
                $inscQuery->join('schools', 'inscriptions.school_id', '=', 'schools.id');
                $this->applyGeoFilters($inscQuery, $filters, 'schools');
            }

            $inscriptionsByYear = $inscQuery->pluck('total', 'annee_scolaire_id');

            $enseignantTotal = Enseignant::query()->where('statut', 'ACTIF');
            if ($this->hasGeoFilters($filters)) {
                $this->applyEnseignantGeoFilters($enseignantTotal, $filters);
            }
            $totalEnseignants = $enseignantTotal->count();

            return $annees->map(fn (AnneeScolaire $annee) => [
                'annee' => $annee->libelle ?? $annee->code,
                'annee_id' => $annee->id,
                'total_eleves' => $inscriptionsByYear->get($annee->id, 0),
                'total_enseignants' => $totalEnseignants,
            ])->toArray();
        });
    }

    /**
     * Geographic distribution of schools, students, teachers by province
     */
    public function getRepartitionGeographique(array $filters = []): array
    {
        return Cache::remember($this->cacheKey('geo', $filters), self::CACHE_TTL, function () {
            $schoolCounts = DB::table('schools')
                ->where('statut', 'ACTIVE')
                ->select('province_id', DB::raw('COUNT(*) as total'))
                ->groupBy('province_id')
                ->pluck('total', 'province_id');

            $eleveCounts = DB::table('eleves')
                ->join('schools', 'eleves.school_id', '=', 'schools.id')
                ->where('eleves.statut_global', 'actif')
                ->where('schools.statut', 'ACTIVE')
                ->select('schools.province_id', DB::raw('COUNT(*) as total'))
                ->groupBy('schools.province_id')
                ->pluck('total', 'province_id');

            $enseignantCounts = DB::table(DB::raw('(
                    SELECT DISTINCT e.id, s.province_id
                    FROM enseignants e
                    INNER JOIN schools s ON s.id = e.school_id AND s.statut = \'ACTIVE\' AND s.deleted_at IS NULL
                    WHERE e.statut = \'ACTIF\' AND e.deleted_at IS NULL
                    UNION
                    SELECT DISTINCT e.id, s.province_id
                    FROM enseignants e
                    INNER JOIN enseignant_school es ON es.enseignant_id = e.id
                    INNER JOIN schools s ON s.id = es.school_id AND s.statut = \'ACTIVE\' AND s.deleted_at IS NULL
                    WHERE e.statut = \'ACTIF\' AND e.deleted_at IS NULL
                ) AS ens_provinces'))
                ->select('province_id', DB::raw('COUNT(DISTINCT id) as total'))
                ->groupBy('province_id')
                ->pluck('total', 'province_id');

            $provinces = DB::table('provinces')
                ->select('id', 'name')
                ->get();

            $repartition = $provinces->map(function ($province) use ($schoolCounts, $eleveCounts, $enseignantCounts) {
                $schools = $schoolCounts->get($province->id, 0);
                $eleves = $eleveCounts->get($province->id, 0);
                $enseignants = $enseignantCounts->get($province->id, 0);

                return [
                    'province_id' => $province->id,
                    'province' => $province->name,
                    'total_schools' => $schools,
                    'total_eleves' => $eleves,
                    'total_enseignants' => $enseignants,
                    'ratio_eleve_enseignant' => $enseignants > 0 ? round($eleves / $enseignants, 1) : null,
                ];
            })->sortByDesc('total_eleves')->values()->toArray();

            return $repartition;
        });
    }

    /**
     * Full dashboard data for a given scope
     */
    public function getDashboardData(array $filters = []): array
    {
        return [
            'global' => $this->getGlobalStats($filters),
            'kpis' => $this->getNationalKpis($filters),
            'inscription' => $this->getInscriptionStats($filters),
            'performance' => $this->getPerformanceStats($filters),
            'evolution_effectifs' => $this->getEvolutionEffectifs($filters),
            'repartition_geographique' => $this->getRepartitionGeographique($filters),
            'infrastructure' => $this->getInfrastructureStats($filters),
            'budget' => $this->getBudgetStats($filters),
        ];
    }

    /**
     * Infrastructure statistics: buildings, rooms, equipment
     */
    public function getInfrastructureStats(array $filters = []): array
    {
        return Cache::remember($this->cacheKey('infrastructure', $filters), self::CACHE_TTL, function () use ($filters) {
            $batimentsQuery = Batiment::query();
            if ($this->hasGeoFilters($filters)) {
                $batimentsQuery->whereHas('ecole', function ($q) use ($filters) {
                    $this->applyGeoFilters($q, $filters, 'schools');
                });
            }

            $totalBatiments = $batimentsQuery->count();
            $batimentsParType = $batimentsQuery
                ->select('type', DB::raw('COUNT(*) as total'))
                ->groupBy('type')
                ->pluck('total', 'type')
                ->toArray();

            $sallesQuery = Salle::query();
            if ($this->hasGeoFilters($filters)) {
                $sallesQuery->whereHas('batiment.ecole', function ($q) use ($filters) {
                    $this->applyGeoFilters($q, $filters, 'schools');
                });
            }

            $totalSalles = $sallesQuery->count();
            $sallesParType = $sallesQuery
                ->select('type', DB::raw('COUNT(*) as total'))
                ->groupBy('type')
                ->pluck('total', 'type')
                ->toArray();

            // Salles avec accès handicap
            $sallesAccessibles = (clone $sallesQuery)->where('accessible_handicap', true)->count();

            return [
                'total_batiments' => $totalBatiments,
                'batiments_par_type' => $batimentsParType,
                'total_salles' => $totalSalles,
                'salles_par_type' => $sallesParType,
                'salles_accessibles' => $sallesAccessibles,
            ];
        });
    }

    /**
     * Budget statistics: financing by project and source
     */
    public function getBudgetStats(array $filters = []): array
    {
        return Cache::remember($this->cacheKey('budget', $filters), self::CACHE_TTL, function () {
            $financementsQuery = Financement::query()
                ->join('projets_partenariat', 'financements.projet_partenariat_id', '=', 'projets_partenariat.id');

            $totalFinancement = $financementsQuery->sum('montant');

            $repartitionParProjet = $financementsQuery
                ->select('projets_partenariat.nom as projet', DB::raw('SUM(montant) as total'))
                ->groupBy('projets_partenariat.id', 'projets_partenariat.nom')
                ->get()
                ->map(fn ($f) => [
                    'label' => $f->projet,
                    'value' => (float) $f->total,
                ])
                ->toArray();

            $repartitionParSource = DB::table('financements')
                ->join('projets_partenariat', 'financements.projet_partenariat_id', '=', 'projets_partenariat.id')
                ->join('partenaires', 'projets_partenariat.partenaire_id', '=', 'partenaires.id')
                ->select('partenaires.nom as partenaire', DB::raw('SUM(financements.montant) as total'))
                ->groupBy('partenaires.id', 'partenaires.nom')
                ->get()
                ->map(fn ($f) => [
                    'label' => $f->partenaire,
                    'value' => (float) $f->total,
                ])
                ->toArray();

            return [
                'total_financement' => (float) $totalFinancement,
                'repartition_projet' => $repartitionParProjet,
                'repartition_source' => $repartitionParSource,
            ];
        });
    }

    /**
     * Clear all statistics cache using targeted key deletion instead of flush.
     */
    public function clearCache(array $filters = []): void
    {
        $methods = ['global', 'inscription', 'performance', 'kpis', 'evolution', 'geo', 'infrastructure', 'budget'];

        if (! empty($filters)) {
            foreach ($methods as $method) {
                Cache::forget($this->cacheKey($method, $filters));
            }

            return;
        }

        foreach ($methods as $method) {
            Cache::forget($this->cacheKey($method, []));
        }
    }
}
