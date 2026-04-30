<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait HasDataScope
{
    /**
     * Scope a query to only include records visible to the given user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \App\Models\User $user
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser(Builder $query, User $user)
    {
        $qualify = fn (string $column) => $query->getModel()->qualifyColumn($column);
        $table = $query->getModel()->getTable();

        if ($user->isSuperAdmin() || $user->hasRole(\App\Models\Role::ADMIN_NATIONAL)) {
            return $query;
        }

        // Enseignant multi-école: filter by all assigned schools (pivot + direct)
        if ($user->hasRole(\App\Models\Role::ENSEIGNANT)) {
            $schoolIds = $this->resolveTeacherSchoolIds($user);

            if ($schoolIds->isNotEmpty()) {
                if ($this->isSchoolModel()) {
                    return $query->whereIn($qualify('id'), $schoolIds);
                }

                if (Schema::hasColumn($table, 'school_id')) {
                    return $this->filterBySchoolScope($query, $table, $qualify, $schoolIds);
                }

                return $this->applySchoolColumnsFilter($query, $table, $qualify, $schoolIds);
            }
        }

        $schoolScopeId = $this->resolveSchoolStaffSchoolId($user);

        if ($schoolScopeId !== null) {
            if ($this->isSchoolModel()) {
                return $query->where($qualify('id'), $schoolScopeId);
            }

            if (Schema::hasColumn($table, 'school_id')) {
                return $this->filterBySchoolScope($query, $table, $qualify, collect([$schoolScopeId]));
            }

            return $this->applySchoolColumnsFilter($query, $table, $qualify, collect([$schoolScopeId]));
        }

        $hierarchyChecks = [
            'colline_id',
            'zone_id',
            'commune_id',
            'province_id',
            'ministere_id',
            'pays_id',
        ];

        foreach ($hierarchyChecks as $column) {
            $value = $user->{$column};

            if (! $value) {
                continue;
            }

            if ($this->isSchoolModel()) {
                return $query->where($qualify($column), $value);
            }

            if (Schema::hasColumn($table, $column)) {
                return $query->where($qualify($column), $value);
            }

            $schoolSubquery = function ($q) use ($column, $value) {
                $q->select('id')
                    ->from('schools')
                    ->where($column, $value)
                    ->whereNull('deleted_at');
            };

            if (Schema::hasColumn($table, 'school_id')) {
                return $this->filterBySchoolScope($query, $table, $qualify, $schoolSubquery);
            }

            $hasOrigine = Schema::hasColumn($table, 'ecole_origine_id');
            $hasDestination = Schema::hasColumn($table, 'ecole_destination_id');

            if ($hasOrigine || $hasDestination) {
                return $query->where(function ($q) use ($qualify, $schoolSubquery, $hasOrigine, $hasDestination) {
                    if ($hasOrigine) {
                        $q->whereIn($qualify('ecole_origine_id'), $schoolSubquery);
                    }
                    if ($hasDestination) {
                        $q->orWhereIn($qualify('ecole_destination_id'), $schoolSubquery);
                    }
                });
            }

            return $query->whereRaw('0 = 1');
        }

        return $query->whereRaw('0 = 1');
    }

    /**
     * Une seule école : directeur, personnel d'établissement, profils avec school_id, etc.
     */
    protected function resolveSchoolStaffSchoolId(User $user): ?int
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
     * Collect all school IDs a teacher has access to (direct + pivot).
     */
    protected function resolveTeacherSchoolIds(User $user): \Illuminate\Support\Collection
    {
        $user->loadMissing('enseignant');

        $enseignant = $user->enseignant;

        if (! $enseignant) {
            return collect($user->school_id ? [$user->school_id] : []);
        }

        return $enseignant->ecoles()->withoutGlobalScopes()->pluck('schools.id')
            ->push($enseignant->school_id)
            ->push($user->school_id)
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Filter by school(s) on a model with school_id.
     * For Enseignant, also includes matches from enseignant_school pivot.
     *
     * @param  \Closure|\Illuminate\Support\Collection  $schoolConstraint
     */
    protected function filterBySchoolScope(Builder $query, string $table, callable $qualify, $schoolConstraint): Builder
    {
        if ($table === 'enseignants') {
            return $query->where(function ($q) use ($qualify, $schoolConstraint) {
                $q->whereIn($qualify('school_id'), $schoolConstraint)
                    ->orWhereIn($qualify('id'), function ($sub) use ($schoolConstraint) {
                        $sub->select('enseignant_id')
                            ->from('enseignant_school')
                            ->whereIn('school_id', $schoolConstraint);
                    });
            });
        }

        return $query->whereIn($qualify('school_id'), $schoolConstraint);
    }

    /**
     * Filter by school IDs using ecole_origine_id / ecole_destination_id when school_id is absent.
     */
    protected function applySchoolColumnsFilter(Builder $query, string $table, callable $qualify, \Illuminate\Support\Collection $schoolIds): Builder
    {
        $hasOrigine = Schema::hasColumn($table, 'ecole_origine_id');
        $hasDestination = Schema::hasColumn($table, 'ecole_destination_id');

        if ($hasOrigine || $hasDestination) {
            return $query->where(function ($q) use ($qualify, $schoolIds, $hasOrigine, $hasDestination) {
                if ($hasOrigine) {
                    $q->whereIn($qualify('ecole_origine_id'), $schoolIds);
                }
                if ($hasDestination) {
                    $q->orWhereIn($qualify('ecole_destination_id'), $schoolIds);
                }
            });
        }

        return $query->whereRaw('0 = 1');
    }

    public static function bootHasDataScope()
    {
        static::addGlobalScope(new \App\Scopes\AdminScope);
    }

    protected function isSchoolModel()
    {
        return class_basename($this) === 'School';
    }
}
