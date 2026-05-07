<?php

namespace App\Models;

use App\Enums\StatutAcademique;
use App\Traits\HasAcademicYearScope;
use App\Traits\HasDataScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Inscription extends Model
{
    use HasAcademicYearScope, HasDataScope, HasFactory;

    protected $fillable = [
        'numero_inscription',
        'eleve_id',
        'annee_scolaire_id',
        'school_id',
        'niveau_demande_id',
        'type_inscription',
        'statut',
        'statut_academique',
        'date_inscription',
        'date_soumission',
        'date_validation',
        'motif_rejet',
        'est_redoublant',
        'pieces_fournies',
        'observations',
        'soumis_par',
        'valide_par',
        'created_by',
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'date_soumission' => 'datetime',
        'date_validation' => 'datetime',
        'est_redoublant' => 'boolean',
        'pieces_fournies' => 'array',
        'statut_academique' => StatutAcademique::class,
    ];

    protected static function academicYearColumn(): ?string
    {
        return 'annee_scolaire_id';
    }

    protected static function academicYearRelation(): ?string
    {
        return null;
    }

    protected static function getScopeColumn(): ?string
    {
        return 'school_id';
    }

    protected static function getScopeRelation(): ?string
    {
        return null;
    }

    /**
     * Query Scopes
     */
    public function scopeEnCours($query)
    {
        return $query->where('statut_academique', StatutAcademique::EnCours);
    }

    public function scopeAdmis($query)
    {
        return $query->where('statut_academique', StatutAcademique::Admis);
    }

    public function scopeRedouble($query)
    {
        return $query->where('statut_academique', StatutAcademique::Redouble);
    }

    public function scopeByEleve($query, int $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }

    public function scopeByAnneeScolaire($query, int $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    /**
     * Check if this inscription belongs to a non-active year (read-only).
     */
    public function isReadOnly(): bool
    {
        $active = AnneeScolaire::current();

        return ! $active || $active->id !== $this->annee_scolaire_id;
    }

    /**
     * Relationships
     */
    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function niveauDemande(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_demande_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'soumis_par');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function affectation(): HasOne
    {
        return $this->hasOne(AffectationClasse::class, 'inscription_id');
    }

    public function classe(): HasOneThrough
    {
        return $this->hasOneThrough(
            Classe::class,
            AffectationClasse::class,
            'inscription_id',
            'id',
            'id',
            'classe_id'
        );
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function notesConduite(): HasMany
    {
        return $this->hasMany(NoteConduite::class);
    }

    public function sanctions(): HasMany
    {
        return $this->hasMany(SanctionEleve::class);
    }
}
