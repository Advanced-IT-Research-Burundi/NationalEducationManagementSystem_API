<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maintenance extends Model
{
    /** @use HasFactory<\Database\Factories\MaintenanceFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_demande' => 'date',
            'date_intervention' => 'date',
            'date_fin' => 'date',
        ];
    }

    /**
     * Get the parent maintenable model (batiment or equipement)
     */
    public function maintenable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the demandeur (user who requested the maintenance)
     */
    public function demandeur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'demandeur_id');
    }

    /**
     * Get the technicien (user assigned to the maintenance)
     */
    public function technicien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technicien_id');
    }
}
