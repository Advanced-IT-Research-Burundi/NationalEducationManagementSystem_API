<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReglementScolaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'article_number',
        'intitule',
        'points_retires',
        'sanction',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }
}
