<?php

namespace App\Models;

use Database\Factories\CarriereFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Carriere extends Model
{
    /** @use HasFactory<CarriereFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    /**
     * Get the enseignant that owns the carriere
     */
    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    /**
     * Get the ecole associated with this carriere
     */
    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }
}
