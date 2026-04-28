<?php

namespace App\Models;

use App\Traits\HasAcademicYearScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Evaluation extends Model
{
    use HasAcademicYearScope, HasFactory, SoftDeletes;

    protected static function academicYearColumn(): ?string
    {
        return 'annee_scolaire_id';
    }

    protected static function academicYearRelation(): ?string
    {
        return null;
    }

    protected $fillable = [
        'classe_id',
        'cours_id',
        'annee_scolaire_id',
        'trimestre',
        'type_evaluation',
        'date_passation',
        'note_maximale',
        'created_by',
    ];

    protected $casts = [
        'note_maximale' => 'decimal:2',
        'date_passation' => 'date',
    ];

    const TRIMESTRES = [
        '1er Trimestre',
        '2e Trimestre',
        '3e Trimestre',
    ];

    const TYPES_EVALUATION = [
        'TJ',
        'Interrogation',
        'Devoir',
        'TP',
        'Examen',
    ];

    /**
     * Relationships
     */
    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function cours(): BelongsTo
    {
        return $this->belongsTo(Matiere::class, 'cours_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Query Scopes
     */
    public function scopeByClasse($query, int $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeByCours($query, int $coursId)
    {
        return $query->where('cours_id', $coursId);
    }

    public function scopeByTrimestre($query, string $trimestre)
    {
        return $query->where('trimestre', $trimestre);
    }

    public function scopeByAnneeScolaire($query, int $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type_evaluation', $type);
    }

    public function scopeBySchool($query, int $schoolId)
    {
        return $query->whereHas('classe', function ($q) use ($schoolId) {
            $q->where('school_id', $schoolId);
        });
    }
}
