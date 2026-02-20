<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjetPartenariat extends Model
{
    /** @use HasFactory<\Database\Factories\ProjetPartenariatFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'projets_partenariat';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_debut' => 'date',
            'date_fin' => 'date',
        ];
    }

    /**
     * Get the partenaire that owns the projet
     */
    public function partenaire(): BelongsTo
    {
        return $this->belongsTo(Partenaire::class);
    }

    /**
     * Get the responsable (user)
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Get the financements for the projet
     */
    public function financements(): HasMany
    {
        return $this->hasMany(Financement::class);
    }
}
