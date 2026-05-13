<?php

namespace App\Models;

use App\Traits\HasAcademicYearScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoteConduite extends Model
{
    use HasAcademicYearScope, HasFactory;

    protected static function academicYearColumn(): ?string
    {
        return 'annee_scolaire_id';
    }

    protected static function academicYearRelation(): ?string
    {
        return null;
    }

    protected $fillable = [
        'eleve_id',
        'inscription_id',
        'classe_id',
        'annee_scolaire_id',
        'trimestre_id',
        'trimestre',
        'note',
    ];

    protected $appends = [
        'trimestre_label',
        'trimestre_meta',
    ];

    public function eleve()
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class);
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function trimestreModel()
    {
        return $this->belongsTo(Trimestre::class, 'trimestre_id');
    }

    public function scopeMatchingTrimestre($query, Trimestre $trimestre)
    {
        return $query->where(function ($q) use ($trimestre) {
            $q->where('trimestre_id', $trimestre->id)
                ->orWhere(function ($q2) use ($trimestre) {
                    $q2->whereNull('trimestre_id')
                        ->where('trimestre', $trimestre->nom);
                });
        });
    }

    public function inscription()
    {
        return $this->belongsTo(Inscription::class);
    }

    public function getTrimestreLabelAttribute(): ?string
    {
        return $this->trimestreModel?->nom ?: $this->attributes['trimestre'] ?? null;
    }

    public function getTrimestreMetaAttribute(): ?array
    {
        $trimestre = $this->trimestreModel;

        if (! $trimestre) {
            return null;
        }

        return [
            'id' => $trimestre->id,
            'nom' => $trimestre->nom,
            'date_debut' => optional($trimestre->date_debut)?->toDateString(),
            'date_fin' => optional($trimestre->date_fin)?->toDateString(),
            'actif' => (bool) $trimestre->actif,
            'verrouille' => (bool) $trimestre->verrouille,
        ];
    }
}
