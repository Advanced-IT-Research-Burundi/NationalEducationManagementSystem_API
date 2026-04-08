<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategorieCours extends Model
{
    use HasFactory;

    protected $table = 'categories_cours';

    protected $fillable = [
        'nom',
        'ordre',
        'afficher_bulletin',
    ];

    protected $casts = [
        'ordre' => 'integer',
        'afficher_bulletin' => 'boolean',
    ];

    /**
     * Query Scopes
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }

    public function scopeVisibleOnBulletin($query)
    {
        return $query->where('afficher_bulletin', true);
    }

    /**
     * Relationships
     */
    public function cours(): HasMany
    {
        return $this->hasMany(Matiere::class, 'categorie_cours_id');
    }
}
