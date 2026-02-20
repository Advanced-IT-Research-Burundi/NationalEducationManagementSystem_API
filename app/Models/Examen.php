<?php

namespace App\Models;

use App\Traits\HasMatricule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    use HasFactory, HasMatricule;

    protected $fillable = [
        'code',
        'libelle',
        'niveau_id',
        'annee_scolaire_id',
        'type',
    ];

    public function niveau()
    {
        return $this->belongsTo(Niveau::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function sessions()
    {
        return $this->hasMany(SessionExamen::class);
    }
}
