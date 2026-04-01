<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasMatricule;

class Section extends Model
{
    use HasMatricule, HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'code',
        'description',
        'type_id',
        'niveau_id',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Relationships
     */
    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class);
    }

    public function typeScolaire(): BelongsTo
    {
        return $this->belongsTo(TypeScolaire::class, 'type_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }
}
