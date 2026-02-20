<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Traits\HasMatricule;
use Illuminate\Database\Eloquent\SoftDeletes;

class Batiment extends Model
{
    /** @use HasFactory<\Database\Factories\BatimentFactory> */
    use HasFactory, SoftDeletes, HasMatricule;

    protected $guarded = [];

    /**
     * Get the ecole that owns the batiment
     */
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the salles for the batiment
     */
    public function salles(): HasMany
    {
        return $this->hasMany(Salle::class);
    }

    /**
     * Get all of the batiment's maintenances
     */
    public function maintenances(): MorphMany
    {
        return $this->morphMany(Maintenance::class, 'maintenable');
    }

    protected static function boot()
    {
        static::creating(function (Batiment $batiment) {
            $batiment->school_id = 1;
        });

        static::updating(function (Batiment $batiment) {
            $batiment->school_id = 1;
        });

        return parent::boot();
    }
}
