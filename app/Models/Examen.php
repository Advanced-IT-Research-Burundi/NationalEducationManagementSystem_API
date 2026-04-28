<?php

namespace App\Models;

use App\Traits\HasAcademicYearScope;
use App\Traits\HasMatricule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Examen extends Model
{
    use HasAcademicYearScope, HasFactory, HasMatricule;

    protected static function academicYearColumn(): ?string
    {
        return 'annee_scolaire_id';
    }

    protected static function academicYearRelation(): ?string
    {
        return null;
    }

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
