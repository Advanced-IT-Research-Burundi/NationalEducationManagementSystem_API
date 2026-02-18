<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Equipement extends Model
{
    /** @use HasFactory<\Database\Factories\EquipementFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_acquisition' => 'date',
        ];
    }

    /**
     * Get the salle that owns the equipement
     */
    public function salle(): BelongsTo
    {
        return $this->belongsTo(Salle::class);
    }

    /**
     * Get the ecole that owns the equipement
     */
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }

    /**
     * Get all of the equipement's maintenances
     */
    public function maintenances(): MorphMany
    {
        return $this->morphMany(Maintenance::class, 'maintenable');
    }
}
