<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'nom',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function fonctions(): HasMany
    {
        return $this->hasMany(Fonction::class);
    }

    public function personnels(): HasMany
    {
        return $this->hasMany(PersonnelAdministratif::class);
    }
}
