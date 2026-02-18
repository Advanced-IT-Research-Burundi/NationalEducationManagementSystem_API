<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StandardQualite extends Model
{
    use HasFactory;

    protected $table = 'standards_qualite';

    protected $fillable = [
        'code',
        'libelle',
        'description',
        'criteres',
        'poids',
    ];

    protected $casts = [
        'criteres' => 'array',
    ];
}
