<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeScolaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'types_scolaires';

    protected $fillable = [
        'nom',
        'description',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(CycleScolaire::class, 'type_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class, 'type_id');
    }

    public function niveaux(): HasMany
    {
        return $this->hasMany(Niveau::class, 'type_id');
    }
}
