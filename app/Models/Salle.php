<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salle extends Model
{
    /** @use HasFactory<\Database\Factories\SalleFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'accessible_handicap' => 'boolean',
        ];
    }

    /**
     * Get the batiment that owns the salle
     */
    public function batiment(): BelongsTo
    {
        return $this->belongsTo(Batiment::class);
    }

    /**
     * Get the equipements for the salle
     */
    public function equipements(): HasMany
    {
        return $this->hasMany(Equipement::class);
    }
}
