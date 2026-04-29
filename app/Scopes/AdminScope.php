<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class AdminScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (!Auth::hasUser()) {
            return;
        }

        $user = Auth::user();
        $qualify = fn (string $column) => $model->qualifyColumn($column);

        // 1. Super admin and national operators see everything
        if ($user->isSuperAdmin() || $user->hasRole('Admin National') || $user->admin_level === 'PAYS') {
            return;
        }

        // 2. Filter based on Admin Level
        // We assume the model has columns matching the hierarchy: 
        // pays_id, ministere_id, province_id, commune_id, zone_id, school_id (or id for School)
        
        // Enseignant multi-école: include all assigned schools
        if ($user->hasRole(\App\Models\Role::ENSEIGNANT)) {
            $schoolIds = $this->resolveTeacherSchoolIds($user);

            if ($schoolIds->isNotEmpty()) {
                if ($model instanceof \App\Models\School) {
                    $builder->whereIn($qualify('id'), $schoolIds);
                } elseif ($this->hasColumn($model, 'school_id')) {
                    $this->filterBySchools($builder, $model, $qualify, $schoolIds);
                } else {
                    $this->applySchoolIdsFilter($builder, $model, $qualify, $schoolIds);
                }

                return;
            }
        }

        $schoolScopeId = $this->resolveSchoolScopedSchoolId($user);

        if ($schoolScopeId !== null) {
            if ($model instanceof \App\Models\School) {
                $builder->where($qualify('id'), $schoolScopeId);
            } elseif ($this->hasColumn($model, 'school_id')) {
                $this->filterBySchools($builder, $model, $qualify, collect([$schoolScopeId]));
            } else {
                $this->applySchoolIdsFilter($builder, $model, $qualify, collect([$schoolScopeId]));
            }

            return;
        }

        $level = $user->admin_level;
        $entityId = $user->admin_entity_id;

        if (! $level || ! $entityId) {
            $builder->whereRaw('1 = 0');

            return;
        }

        switch ($level) {
            case 'MINISTERE':
                $this->applyHierarchyFilter($builder, $model, $qualify, 'ministere_id', $entityId);
                break;

            case 'PROVINCE':
                $this->applyHierarchyFilter($builder, $model, $qualify, 'province_id', $entityId);
                break;

            case 'COMMUNE':
                $this->applyHierarchyFilter($builder, $model, $qualify, 'commune_id', $entityId);
                break;

            case 'ZONE':
                $this->applyHierarchyFilter($builder, $model, $qualify, 'zone_id', $entityId);
                break;

            case 'ECOLE':
                if ($model instanceof \App\Models\School) {
                    $builder->where($qualify('id'), $entityId);
                } elseif ($this->hasColumn($model, 'school_id')) {
                    $this->filterBySchools($builder, $model, $qualify, collect([$entityId]));
                } else {
                    $this->applySchoolIdsFilter($builder, $model, $qualify, collect([$entityId]));
                }
                break;
        }
    }

    /**
     * Apply a hierarchy-level filter, falling back to a schools subquery
     * when the model lacks the direct geographic column but has school_id.
     * Also handles models with ecole_origine_id / ecole_destination_id.
     */
    protected function applyHierarchyFilter(Builder $builder, Model $model, callable $qualify, string $column, $entityId): void
    {
        if ($model instanceof \App\Models\School) {
            $builder->where($qualify($column), $entityId);

            return;
        }

        if ($this->hasColumn($model, $column)) {
            $builder->where($qualify($column), $entityId);

            return;
        }

        $schoolSubquery = function ($q) use ($column, $entityId) {
            $q->select('id')
                ->from('schools')
                ->where($column, $entityId)
                ->whereNull('deleted_at');
        };

        if ($this->hasColumn($model, 'school_id')) {
            $this->filterBySchools($builder, $model, $qualify, $schoolSubquery);

            return;
        }

        $hasOrigine = $this->hasColumn($model, 'ecole_origine_id');
        $hasDestination = $this->hasColumn($model, 'ecole_destination_id');

        if ($hasOrigine || $hasDestination) {
            $builder->where(function ($q) use ($qualify, $schoolSubquery, $hasOrigine, $hasDestination) {
                if ($hasOrigine) {
                    $q->whereIn($qualify('ecole_origine_id'), $schoolSubquery);
                }
                if ($hasDestination) {
                    $q->orWhereIn($qualify('ecole_destination_id'), $schoolSubquery);
                }
            });

            return;
        }

        $builder->whereRaw('1 = 0');
    }

    /**
     * Filter by school(s) on a model that has school_id.
     * For Enseignant, also includes matches from the enseignant_school pivot table.
     *
     * @param  \Closure|\Illuminate\Support\Collection  $schoolConstraint  Closure (subquery) or Collection of IDs
     */
    protected function filterBySchools(Builder $builder, Model $model, callable $qualify, $schoolConstraint): void
    {
        if ($model instanceof \App\Models\Enseignant) {
            $builder->where(function ($q) use ($qualify, $schoolConstraint) {
                $q->whereIn($qualify('school_id'), $schoolConstraint)
                    ->orWhereIn($qualify('id'), function ($sub) use ($schoolConstraint) {
                        $sub->select('enseignant_id')
                            ->from('enseignant_school')
                            ->whereIn('school_id', $schoolConstraint);
                    });
            });
        } else {
            $builder->whereIn($qualify('school_id'), $schoolConstraint);
        }
    }

    /**
     * Filter models that use ecole_origine_id / ecole_destination_id by a set of school IDs.
     */
    protected function applySchoolIdsFilter(Builder $builder, Model $model, callable $qualify, \Illuminate\Support\Collection $schoolIds): void
    {
        $hasOrigine = $this->hasColumn($model, 'ecole_origine_id');
        $hasDestination = $this->hasColumn($model, 'ecole_destination_id');

        if ($hasOrigine || $hasDestination) {
            $builder->where(function ($q) use ($qualify, $schoolIds, $hasOrigine, $hasDestination) {
                if ($hasOrigine) {
                    $q->whereIn($qualify('ecole_origine_id'), $schoolIds);
                }
                if ($hasDestination) {
                    $q->orWhereIn($qualify('ecole_destination_id'), $schoolIds);
                }
            });

            return;
        }

        $builder->whereRaw('1 = 0');
    }

    /**
     * Identifiant d'établissement pour utilisateurs cantonnés à une école (directeur, staff, élève suivant cours, etc.).
     */
    protected function resolveSchoolScopedSchoolId(\App\Models\User $user): ?int
    {
        if ($user->admin_level === 'ECOLE') {
            $id = $user->admin_entity_id ?? $user->school_id;

            return $id ? (int) $id : null;
        }

        if ($user->school_id) {
            return (int) $user->school_id;
        }

        if ($user->hasRole(\App\Models\Role::DIRECTEUR_ECOLE)) {
            $id = \App\Models\School::withoutGlobalScopes()
                ->where('directeur_id', $user->id)
                ->value('id');

            return $id ? (int) $id : null;
        }

        return null;
    }

    /**
     * Check if model table has a given column.
     * We use a lightweight check to avoid schema queries on every request if possible.
     * But Schema::hasColumn is cached by Laravel usually.
     * However, ideally we should know the schema. 
     * Since we control the models, we can assume the columns exist if we apply this scope 
     * only to relevant models.
     */
    protected function hasColumn(Model $model, string $column): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), $column);
    }

    /**
     * Collect all school IDs a teacher has access to (direct + pivot).
     */
    protected function resolveTeacherSchoolIds(\App\Models\User $user): \Illuminate\Support\Collection
    {
        $user->loadMissing('enseignant');

        $enseignant = $user->enseignant;

        if (! $enseignant) {
            return collect($user->school_id ? [$user->school_id] : []);
        }

        return $enseignant->ecoles()->pluck('schools.id')
            ->push($enseignant->school_id)
            ->push($user->school_id)
            ->filter()
            ->unique()
            ->values();
    }
}
