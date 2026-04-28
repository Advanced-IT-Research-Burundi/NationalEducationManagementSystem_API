<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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

                return $query->whereIn($qualify('school_id'), $schoolIds);
            }
        }

        $schoolScopeId = $this->resolveSchoolStaffSchoolId($user);

        if ($schoolScopeId !== null) {
            if ($this->isSchoolModel()) {
                return $query->where($qualify('id'), $schoolScopeId);
            }

            return $query->where($qualify('school_id'), $schoolScopeId);
        }

        if ($user->colline_id) {
            return $query->where($qualify('colline_id'), $user->colline_id);
        }

        if ($user->zone_id) {
            return $query->where($qualify('zone_id'), $user->zone_id);
        }

        if ($user->commune_id) {
            return $query->where($qualify('commune_id'), $user->commune_id);
        }

        if ($user->province_id) {
            return $query->where($qualify('province_id'), $user->province_id);
        }

        if ($user->ministere_id) {
            return $query->where($qualify('ministere_id'), $user->ministere_id);
        }

        if ($user->pays_id) {
            return $query->where($qualify('pays_id'), $user->pays_id);
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

        return $enseignant->ecoles()->pluck('schools.id')
            ->push($enseignant->school_id)
            ->push($user->school_id)
            ->filter()
            ->unique()
            ->values();
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
