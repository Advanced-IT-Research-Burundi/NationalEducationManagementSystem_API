<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\School;
use App\Models\User;

class Inspection extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'inspecteur_id',
        'date_prevue',
        'date_realisation',
        'type',
        'statut',
        'rapport',
        'note_globale',
    ];

    protected $casts = [
        'date_prevue' => 'date',
        'date_realisation' => 'date',
        'note_globale' => 'decimal:2',
    ];

    public function ecole()
    {
        return $this->belongsTo(School::class);
    }

    public function inspecteur()
    {
        return $this->belongsTo(User::class, 'inspecteur_id');
    }
}
