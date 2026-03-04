<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Matiere extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'code',
        'description',
        'coefficient',
        'heures_par_semaine',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'coefficient' => 'integer',
        'heures_par_semaine' => 'integer',
    ];

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Relationships
     */
    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationEnseignant::class);
    }

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'affectations_matieres')
            ->withPivot(['school_id', 'annee_scolaire_id', 'statut'])
            ->withTimestamps();
    }

    public function resultats(): HasMany
    {
        return $this->hasMany(Resultat::class);
    }
}
