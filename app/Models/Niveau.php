<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Niveau extends Model
{
    use HasFactory, SoftDeletes;

    // Cycle constants
    const CYCLE_PRIMAIRE = 'PRIMAIRE';

    const CYCLE_FONDAMENTAL = 'FONDAMENTAL';

    const CYCLE_POST_FONDAMENTAL = 'POST_FONDAMENTAL';

    const CYCLE_SECONDAIRE = 'SECONDAIRE';

    const CYCLE_SUPERIEUR = 'SUPERIEUR';

    protected $table = 'niveaux_scolaires';

    protected $fillable = [
        'nom',
        'code',
        'ordre',
        'cycle',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    protected $appends = ['cycle_label'];

    /**
     * Query Scopes
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeByCycle($query, string $cycle)
    {
        return $query->where('cycle', $cycle);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }

    /**
     * Accessors
     */
    public function getCycleLabelAttribute(): string
    {
        return match ($this->cycle) {
            self::CYCLE_PRIMAIRE => 'Primaire',
            self::CYCLE_FONDAMENTAL => 'Fondamental',
            self::CYCLE_POST_FONDAMENTAL => 'Post-Fondamental',
            self::CYCLE_SECONDAIRE => 'Secondaire',
            self::CYCLE_SUPERIEUR => 'Supérieur',
            default => 'Inconnu',
        };
    }

    /**
     * Relationships
     */
    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'niveau_id');
    }
}
