<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conge extends Model
{
    /** @use HasFactory<\Database\Factories\CongeFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
            'date_approbation' => 'datetime',
        ];
    }

    /**
     * Get the enseignant that owns the conge
     */
    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    /**
     * Get the approuveur (user who approved)
     */
    public function approuveur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approuveur_id');
    }
}
