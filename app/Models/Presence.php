<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presence extends Model
{
    /** @use HasFactory<\Database\Factories\PresenceFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'heure_arrivee' => 'datetime:H:i',
            'heure_depart' => 'datetime:H:i',
        ];
    }

    /**
     * Get the enseignant that owns the presence
     */
    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }
}
