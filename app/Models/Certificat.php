<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificat extends Model
{
    use HasFactory;

    protected $fillable = [
        'inscription_examen_id',
        'numero_unique',
        'date_emission',
        'qr_code',
    ];

    protected $casts = [
        'date_emission' => 'date',
    ];

    public function inscription()
    {
        return $this->belongsTo(InscriptionExamen::class, 'inscription_examen_id');
    }
}
