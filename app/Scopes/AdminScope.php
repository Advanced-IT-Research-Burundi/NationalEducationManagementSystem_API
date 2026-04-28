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
                    $builder->whereIn($qualify('school_id'), $schoolIds);
                }

                return;
            }
            // Sinon: ce compte peut aussi être défini comme staff d'école / directeur sans affectations encore
        }

        $schoolScopeId = $this->resolveSchoolScopedSchoolId($user);

        if ($schoolScopeId !== null) {
            if ($model instanceof \App\Models\School) {
                $builder->where($qualify('id'), $schoolScopeId);
            } elseif ($this->hasColumn($model, 'school_id')) {
                $builder->where($qualify('school_id'), $schoolScopeId);
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
                if ($this->hasColumn($model, 'ministere_id')) {
                    $builder->where($qualify('ministere_id'), $entityId);
                }
                break;

            case 'PROVINCE':
                if ($this->hasColumn($model, 'province_id')) {
                    $builder->where($qualify('province_id'), $entityId);
                }
                break;

            case 'COMMUNE':
                if ($this->hasColumn($model, 'commune_id')) {
                    $builder->where($qualify('commune_id'), $entityId);
                }
                break;

            case 'ZONE':
                if ($this->hasColumn($model, 'zone_id')) {
                    $builder->where($qualify('zone_id'), $entityId);
                }
                break;

            case 'ECOLE':
                // Déjà traité via resolveSchoolScopedSchoolId lorsque les FK sont cohérentes
                if ($model instanceof \App\Models\School) {
                    $builder->where($qualify('id'), $entityId);
                } elseif ($this->hasColumn($model, 'school_id')) {
                    $builder->where($qualify('school_id'), $entityId);
                }
                break;
        }
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
