<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Niveau extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'niveaux_scolaires';

    protected $fillable = [
        'nom',
        'code',
        'ordre',
        'type_id',
        'cycle_id',
        'section_id',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'ordre' => 'integer',
    ];

    /**
     * Query Scopes
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function scopeByCycle($query, int $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }

    /**
     * Relationships
     */
    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'niveau_id');
    }

    public function typeScolaire(): BelongsTo
    {
        return $this->belongsTo(TypeScolaire::class, 'type_id');
    }

    public function cycleScolaire(): BelongsTo
    {
        return $this->belongsTo(CycleScolaire::class, 'cycle_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
}
