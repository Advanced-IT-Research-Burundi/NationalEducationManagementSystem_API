<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formation extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'formateur_id',
        'lieu',
        'capacite',
        'statut',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function formateur()
    {
        return $this->belongsTo(User::class, 'formateur_id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'participants_formation')
            ->withPivot('statut_participation', 'commentaires')
            ->withTimestamps();
    }
}
