<?php

namespace App\Models;

use App\Traits\HasAcademicYearScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscriptionExamen extends Model
{
    use HasAcademicYearScope, HasFactory;

    protected static function academicYearColumn(): ?string
    {
        return null;
    }

    protected static function academicYearRelation(): ?string
    {
        return 'session.examen';
    }

    protected $table = 'inscriptions_examen';

    protected $fillable = [
        'eleve_id',
        'session_id',
        'centre_id',
        'numero_anonymat',
        'statut',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function session()
    {
        return $this->belongsTo(SessionExamen::class, 'session_id');
    }

    public function centre()
    {
        return $this->belongsTo(CentreExamen::class, 'centre_id');
    }

    public function resultats()
    {
        return $this->hasMany(Resultat::class, 'inscription_examen_id');
    }

    public function certificat()
    {
        return $this->hasOne(Certificat::class, 'inscription_examen_id');
    }
}
