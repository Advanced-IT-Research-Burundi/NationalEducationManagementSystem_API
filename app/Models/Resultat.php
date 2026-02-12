<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resultat extends Model
{
    use HasFactory;

    protected $fillable = [
        'inscription_examen_id',
        'matiere',
        'note',
        'mention',
        'deliberation',
    ];

    public function inscription()
    {
        return $this->belongsTo(InscriptionExamen::class, 'inscription_examen_id');
    }
}
