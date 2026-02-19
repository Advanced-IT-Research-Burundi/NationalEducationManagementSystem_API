<?php

namespace App\Services;

use App\Models\School;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\InscriptionEleve;
use App\Models\AnneeScolaire;
use App\Models\Resultat;
use App\Models\InscriptionExamen;
use App\Models\Province;
use App\Models\Commune;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * Cache TTL in seconds (30 minutes)
     */
    const CACHE_TTL = 1800;

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
        if (!empty($filters['annee_scolaire_id'])) {
            return AnneeScolaire::find($filters['annee_scolaire_id']);
        }
        return AnneeScolaire::current();
    }

    /**
     * Apply geographic filters to a query on the 'ecoles' table
     */
    protected function applyGeoFilters($query, array $filters, string $tableAlias = '')
    {
        $prefix = $tableAlias ? "{$tableAlias}." : '';

        if (!empty($filters['province_id'])) {
            $query->where("{$prefix}province_id", $filters['province_id']);
        }
        if (!empty($filters['commune_id'])) {
            $query->where("{$prefix}commune_id", $filters['commune_id']);
        }
        if (!empty($filters['school_id'])) {
            $query->where("{$prefix}id", $filters['school_id']);
        }
        if (!empty($filters['niveau'])) {
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
            $ecolesQuery = School::query()->where('statut', 'ACTIVE');
            $this->applyGeoFilters($ecolesQuery, $filters);
            $totalEcoles = $ecolesQuery->count();

            // Schools by type
            $ecolesParTypeQuery = School::query()->where('statut', 'ACTIVE');
            $this->applyGeoFilters($ecolesParTypeQuery, $filters);
            $ecolesParType = $ecolesParTypeQuery
                ->select('type_ecole', DB::raw('COUNT(*) as total'))
                ->groupBy('type_ecole')
                ->pluck('total', 'type_ecole')
                ->toArray();

            // Schools by niveau
            $ecolesParNiveauQuery = School::query()->where('statut', 'ACTIVE');
            $this->applyGeoFilters($ecolesParNiveauQuery, $filters);
            $ecolesParNiveau = $ecolesParNiveauQuery
                ->select('niveau', DB::raw('COUNT(*) as total'))
                ->groupBy('niveau')
                ->pluck('total', 'niveau')
                ->toArray();

            // Total students (active)
            $elevesQuery = Eleve::query()->where('statut_global', 'actif');
            if (!empty($filters['province_id']) || !empty($filters['commune_id']) || !empty($filters['school_id']) || !empty($filters['niveau'])) {
                $elevesQuery->whereHas('school', function ($q) use ($filters) {
                    $q->where('statut', 'ACTIVE');
                    $this->applyGeoFilters($q, $filters);
                });
            }
            $totalEleves = $elevesQuery->count();

            // Total teachers (active)
            $enseignantsQuery = Enseignant::query()->where('statut', 'ACTIF');
            if (!empty($filters['province_id']) || !empty($filters['commune_id']) || !empty($filters['school_id'])) {
                $enseignantsQuery->whereHas('school', function ($q) use ($filters) {
                    $q->where('statut', 'ACTIVE');
                    $this->applyGeoFilters($q, $filters);
                });
            }
            $totalEnseignants = $enseignantsQuery->count();

            return [
                'total_ecoles' => $totalEcoles,
                'total_eleves' => $totalEleves,
                'total_enseignants' => $totalEnseignants,
                'ecoles_par_type' => $ecolesParType,
                'ecoles_par_niveau' => $ecolesParNiveau,
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
            if (!empty($filters['province_id']) || !empty($filters['commune_id']) || !empty($filters['school_id']) || !empty($filters['niveau'])) {
                $genderQuery->whereHas('school', function ($q) use ($filters) {
                    $q->where('statut', 'ACTIVE');
                    $this->applyGeoFilters($q, $filters);
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
                if (!empty($filters['province_id']) || !empty($filters['commune_id']) || !empty($filters['school_id'])) {
                    $inscQuery->whereHas('ecole', function ($q) use ($filters) {
                        $this->applyGeoFilters($q, $filters);
                    });
                }
                $inscriptionsCount = $inscQuery->where('statut', 'valide')->count();

                $inscByTypeQuery = InscriptionEleve::query()
                    ->where('annee_scolaire_id', $anneeId)
                    ->where('statut', 'valide');
                if (!empty($filters['province_id']) || !empty($filters['commune_id']) || !empty($filters['school_id'])) {
                    $inscByTypeQuery->whereHas('ecole', function ($q) use ($filters) {
                        $this->applyGeoFilters($q, $filters);
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
                        if ($row->sexe === 'M')
                            $result['garcons'] = $row->total;
                        if ($row->sexe === 'F')
                            $result['filles'] = $row->total;
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

            if (!empty($filters['annee_scolaire_id'])) {
                $resultatsQuery->where('examens.annee_scolaire_id', $filters['annee_scolaire_id']);
            }

            // Apply geo filters via eleves -> ecoles
            if (!empty($filters['province_id']) || !empty($filters['commune_id']) || !empty($filters['school_id'])) {
                $resultatsQuery
                    ->join('eleves', 'inscriptions_examen.eleve_id', '=', 'eleves.id')
                    ->join('schools', 'eleves.school_id', '=', 'schools.id');

                if (!empty($filters['province_id'])) {
                    $resultatsQuery->where('schools.province_id', $filters['province_id']);
                }
                if (!empty($filters['commune_id'])) {
                    $resultatsQuery->where('schools.commune_id', $filters['commune_id']);
                }
                if (!empty($filters['school_id'])) {
                    $resultatsQuery->where('schools.id', $filters['school_id']);
                }
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

            if (!empty($filters['annee_scolaire_id'])) {
                $studentsResultsQuery->where('examens.annee_scolaire_id', $filters['annee_scolaire_id']);
            }

            if (!empty($filters['province_id']) || !empty($filters['commune_id']) || !empty($filters['school_id'])) {
                $studentsResultsQuery
                    ->join('eleves', 'inscriptions_examen.eleve_id', '=', 'eleves.id')
                    ->join('schools', 'eleves.school_id', '=', 'schools.id');
                if (!empty($filters['province_id'])) {
                    $studentsResultsQuery->where('schools.province_id', $filters['province_id']);
                }
                if (!empty($filters['commune_id'])) {
                    $studentsResultsQuery->where('schools.commune_id', $filters['commune_id']);
                }
                if (!empty($filters['school_id'])) {
                    $studentsResultsQuery->where('schools.id', $filters['school_id']);
                }
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
                ->map(fn($r) => [
                    'matiere' => $r->matiere,
                    'moyenne' => round($r->moyenne, 2),
                    'total_candidats' => $r->total_candidats,
                ])
                ->toArray();

            return [
                'taux_reussite' => $tauxReussite,
                'moyenne_generale' => $moyenneGenerale,
                'total_candidats' => $totalStudentsExam,
                'candidats_reussis' => $passedStudents,
                'note_max' => $statsResultats->note_max ?? null,
                'note_min' => $statsResultats->note_min ?? null,
                'moyenne_par_matiere' => $moyenneParMatiere,
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
                'total_ecoles' => $globalStats['total_ecoles'],
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
            $evolution = [];

            foreach ($annees as $annee) {
                // Count validated inscriptions for this year
                $inscQuery = InscriptionEleve::query()
                    ->where('annee_scolaire_id', $annee->id)
                    ->where('statut', 'valide');

                if (!empty($filters['province_id']) || !empty($filters['commune_id']) || !empty($filters['school_id'])) {
                    $inscQuery->whereHas('ecole', function ($q) use ($filters) {
                        $this->applyGeoFilters($q, $filters);
                    });
                }

                $totalInscrits = $inscQuery->count();

                // Count active teachers for that period (approximate: teachers active at that time)
                $ensQuery = Enseignant::query()->where('statut', 'ACTIF');
                if (!empty($filters['province_id']) || !empty($filters['commune_id']) || !empty($filters['school_id'])) {
                    $ensQuery->whereHas('school', function ($q) use ($filters) {
                        $this->applyGeoFilters($q, $filters);
                    });
                }
                // Approximate: teachers hired before end of this school year
                if ($annee->date_fin) {
                    $ensQuery->where(function ($q) use ($annee) {
                        $q->whereNull('date_embauche')
                            ->orWhere('date_embauche', '<=', $annee->date_fin);
                    });
                }

                $totalEnseignants = $ensQuery->count();

                $evolution[] = [
                    'annee' => $annee->libelle ?? $annee->code,
                    'annee_id' => $annee->id,
                    'total_eleves' => $totalInscrits,
                    'total_enseignants' => $totalEnseignants,
                ];
            }

            return $evolution;
        });
    }

    /**
     * Geographic distribution of schools, students, teachers by province
     */
    public function getRepartitionGeographique(array $filters = []): array
    {
        return Cache::remember($this->cacheKey('geo', $filters), self::CACHE_TTL, function () use ($filters) {
            // By province
            $provinces = DB::table('provinces')
                ->select('provinces.id', 'provinces.name')
                ->get();

            $repartition = [];

            foreach ($provinces as $province) {
                $localFilters = array_merge($filters, ['province_id' => $province->id]);

                $ecoles = School::query()->where('statut', 'ACTIVE')->where('province_id', $province->id)->count();

                $eleves = DB::table('eleves')
                    ->join('schools', 'eleves.school_id', '=', 'schools.id')
                    ->where('eleves.statut_global', 'actif')
                    ->where('schools.statut', 'ACTIVE')
                    ->where('schools.province_id', $province->id)
                    ->count();

                $enseignants = DB::table('enseignants')
                    ->join('schools', 'enseignants.school_id', '=', 'schools.id')
                    ->where('enseignants.statut', 'ACTIF')
                    ->where('schools.statut', 'ACTIVE')
                    ->where('schools.province_id', $province->id)
                    ->count();

                // Enseignant duplicate check
                $duplicates = DB::table('enseignants as e1')
                    ->join('enseignants as e2', function ($join) {
                        $join->on('e1.matricule', '=', 'e2.matricule')
                            ->on('e1.id', '<', 'e2.id'); // Keep the one with higher ID? Logic to detect
                    })
                    ->where('e1.statut', 'ACTIF')
                    ->where('e2.statut', 'ACTIF')
                    ->where('e1.school_id', '<>', 'e2.school_id')
                    ->count();

                $ratio = $enseignants > 0 ? round($eleves / $enseignants, 1) : null;

                $repartition[] = [
                    'province_id' => $province->id,
                    'province' => $province->name,
                    'total_ecoles' => $ecoles,
                    'total_eleves' => $eleves,
                    'total_enseignants' => $enseignants,
                    'ratio_eleve_enseignant' => $ratio,
                ];
            }

            // Sort by total students descending
            usort($repartition, fn($a, $b) => $b['total_eleves'] - $a['total_eleves']);

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
        ];
    }

    /**
     * Clear all statistics cache
     */
    public function clearCache(): void
    {
        // Clear all stats keys by flushing tagged or prefixed cache
        // Since we use simple keys, we'll clear the specific patterns
        $methods = ['global', 'inscription', 'performance', 'kpis', 'evolution', 'geo'];
        foreach ($methods as $method) {
            // We can't easily clear all variations, so we use a broader approach
            Cache::flush();
            break;
        }
    }
}
