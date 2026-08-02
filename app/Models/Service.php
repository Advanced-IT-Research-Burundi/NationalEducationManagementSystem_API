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
        'departement_id',
        'responsable_id',
        'description',
        'telephone',
        'email',
        'bureau',
        'statut',
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

    public function departement()
    {
        return $this->belongsTo(Departement::class);
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function employes(): HasMany
    {
        return $this->hasMany(Employe::class);
    }

    public function postes(): HasMany
    {
        return $this->hasMany(Poste::class);
    }
}
