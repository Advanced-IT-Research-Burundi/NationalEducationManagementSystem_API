<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'classe_id',
        'eleve_id',
        'matiere_id',
        'enseignant_id',
        'trimestre',
        'categorie',
        'ponderation',
        'note',
    ];

    protected $casts = [
        'ponderation' => 'decimal:2',
        'note' => 'decimal:2',
    ];

    const TRIMESTRES = [
        '1er Trimestre',
        '2e Trimestre',
        '3e Trimestre',
    ];

    const CATEGORIES = [
        'TJ',
        'Examen',
    ];

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function matiere(): BelongsTo
    {
        return $this->belongsTo(Matiere::class);
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
    }

    public function scopeByClasse($query, int $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeByEleve($query, int $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }

    public function scopeByTrimestre($query, string $trimestre)
    {
        return $query->where('trimestre', $trimestre);
    }

    public function scopeByCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }
}
