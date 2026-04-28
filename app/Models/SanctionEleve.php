<?php

namespace App\Models;

use App\Traits\HasAcademicYearScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SanctionEleve extends Model
{
    use HasAcademicYearScope, HasFactory;

    protected static function academicYearColumn(): ?string
    {
        return 'annee_scolaire_id';
    }

    protected static function academicYearRelation(): ?string
    {
        return null;
    }

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'reglement_id',
        'annee_scolaire_id',
        'user_id',
        'trimestre',
        'date_sanction',
        'observation',
        'points_retires',
    ];

    protected $casts = [
        'date_sanction' => 'date',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function reglement()
    {
        return $this->belongsTo(ReglementScolaire::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }
}
