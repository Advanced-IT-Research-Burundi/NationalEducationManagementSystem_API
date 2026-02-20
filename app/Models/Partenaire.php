<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Partenaire extends Model
{
    /** @use HasFactory<\Database\Factories\PartenaireFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_debut_partenariat' => 'date',
        ];
    }

    /**
     * Get the projets for the partenaire
     */
    public function projets(): HasMany
    {
        return $this->hasMany(ProjetPartenariat::class);
    }
}
