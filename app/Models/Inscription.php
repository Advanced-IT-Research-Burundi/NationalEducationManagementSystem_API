<?php

namespace App\Models;

use App\Traits\HasDataScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inscription extends Model
{
    use HasFactory, HasDataScope;

    protected $fillable = [
        'numero_inscription',
        'eleve_id',
        'campagne_id',
        'annee_scolaire_id',
        'ecole_id',
        'niveau_demande_id',
        'type_inscription',
        'statut',
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
    ];

    // Scopes
    protected static function getScopeColumn(): ?string
    {
        return 'ecole_id';
    }

    protected static function getScopeRelation(): ?string
    {
        return null; // Direct column on table
    }

    // Relationships
    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CampagneInscription::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'ecole_id');
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

    public function classe(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            Classe::class,
            AffectationClasse::class,
            'inscription_id', // Foreign key on AffectationClasse table
            'id',             // Foreign key on Classe table
            'id',             // Local key on Inscription table
            'classe_id'       // Local key on AffectationClasse table
        );
    }
}
