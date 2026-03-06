<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CycleScolaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'cycles_scolaires';

    protected $fillable = [
        'nom',
        'description',
        'type_id',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function typeScolaire(): BelongsTo
    {
        return $this->belongsTo(TypeScolaire::class, 'type_id');
    }

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class, 'cycle_id');
    }
}
