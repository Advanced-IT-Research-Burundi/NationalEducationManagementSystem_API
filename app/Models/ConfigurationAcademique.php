<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfigurationAcademique extends Model
{
    use HasFactory;

    protected $table = 'configuration_academique';

    protected $fillable = [
        'current_annee_scolaire_id',
        'current_trimestre_id',
    ];

    public function currentAnneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'current_annee_scolaire_id');
    }

    public function currentTrimestre(): BelongsTo
    {
        return $this->belongsTo(Trimestre::class, 'current_trimestre_id');
    }

    public static function current(): self
    {
        return static::query()->firstOrCreate([], []);
    }
}
