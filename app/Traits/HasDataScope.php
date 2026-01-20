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
        // 1. National Admin sees everything
        if ($user->role && $user->role->slug === 'admin_national') {
            return $query;
        }

        // 2. Hierarchical Filtering
        // We check from most specific to most general.
        // Assuming the model corresponds to one of these levels or has these FKs.
        
        // If the user is restricted to a School
        if ($user->school_id) {
            // If the model IS a School, filter by ID
            if ($this->isSchoolModel()) {
                 return $query->where('id', $user->school_id);
            }
            // If the model belongs to a school (e.g. Student), filter by school_id column
            return $query->where('school_id', $user->school_id);
        }

        // If the user is restricted to a Colline (not realistic for User but possible for data)
        if ($user->colline_id) {
             return $query->where('colline_id', $user->colline_id);
        }

        // Zone
        if ($user->zone_id) {
             return $query->where('zone_id', $user->zone_id);
        }

        // Commune
        if ($user->commune_id) {
             return $query->where('commune_id', $user->commune_id);
        }

        // Province
        if ($user->province_id) {
             return $query->where('province_id', $user->province_id);
        }

        // Ministere
        if ($user->ministere_id) {
             return $query->where('ministere_id', $user->ministere_id);
        }

        // Pays
        if ($user->pays_id) {
             return $query->where('pays_id', $user->pays_id);
        }

        // Fallback: If no administrative context found, return nothing (safety)
        // Or return everything if that's the default policy (usually safe is better)
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
