<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentreExamen extends Model
{
    use HasFactory;

    protected $table = 'centres_examen';

    protected $fillable = [
        'school_id',
        'session_id',
        'capacite',
        'responsable_id',
    ];

    public function ecole()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function session()
    {
        return $this->belongsTo(SessionExamen::class, 'session_id');
    }

    public function responsable()
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function inscriptions()
    {
        return $this->hasMany(InscriptionExamen::class, 'centre_id');
    }
}
