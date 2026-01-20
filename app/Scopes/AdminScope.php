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

        // 1. Admin National sees everything
        if ($user->hasRole('Admin National') || $user->admin_level === 'PAYS') {
            return;
        }

        // 2. Filter based on Admin Level
        // We assume the model has columns matching the hierarchy: 
        // pays_id, ministere_id, province_id, commune_id, zone_id, school_id (or id for School)
        
        $level = $user->admin_level;
        $entityId = $user->admin_entity_id;

        if (!$level || !$entityId) {
            // If user has no administrative assignment, maybe they shouldn't see anything?
            // Or maybe fallback to strict policy. For now, let's limit everything.
            $builder->whereRaw('1 = 0'); 
            return;
        }

        switch ($level) {
            case 'MINISTERE':
                 // Usually sees everything within their ministry, or all schools if ministry is national.
                 // If the model has ministere_id, usage it.
                 if ($this->hasColumn($model, 'ministere_id')) {
                     $builder->where('ministere_id', $entityId);
                 }
                break;

            case 'PROVINCE':
                if ($this->hasColumn($model, 'province_id')) {
                    $builder->where('province_id', $entityId);
                }
                break;

            case 'COMMUNE':
                if ($this->hasColumn($model, 'commune_id')) {
                    $builder->where('commune_id', $entityId);
                }
                break;

            case 'ZONE':
                if ($this->hasColumn($model, 'zone_id')) {
                    $builder->where('zone_id', $entityId);
                }
                break;

            case 'ECOLE':
                // If the model IS School, then filter by ID
                if ($model instanceof \App\Models\School) {
                    $builder->where('id', $entityId);
                } 
                // If it's related to school (e.g. Student, Teacher), filter by school_id
                elseif ($this->hasColumn($model, 'school_id')) {
                    $builder->where('school_id', $entityId);
                }
                break;
        }
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
        // One way is checking fillable, but better is checking schema or assuming standard NEMS structure.
        // For performance, we can rely on the fact that we will only apply this trait to models having these FKs.
        // But for safety, checking Schema is good.
        return \Illuminate\Support\Facades\Schema::hasColumn($model->getTable(), $column);
    }
}
