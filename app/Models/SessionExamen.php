<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionExamen extends Model
{
    use HasFactory;

    protected $table = 'sessions_examen';

    protected $fillable = [
        'examen_id',
        'date_debut',
        'date_fin',
        'statut',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function examen()
    {
        return $this->belongsTo(Examen::class);
    }

    public function centres()
    {
        return $this->hasMany(CentreExamen::class, 'session_id');
    }

    public function inscriptions()
    {
        return $this->hasMany(InscriptionExamen::class, 'session_id');
    }
}
