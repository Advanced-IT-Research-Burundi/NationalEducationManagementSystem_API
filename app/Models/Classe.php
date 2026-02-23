<?php

namespace App\Models;

use App\Traits\HasDataScope;
use App\Traits\HasMatricule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Classe extends Model
{
    use HasDataScope, HasFactory, SoftDeletes, HasMatricule;

    // Status constants
    const STATUS_ACTIVE = 'ACTIVE';

    const STATUS_INACTIVE = 'INACTIVE';

    const STATUS_ARCHIVEE = 'ARCHIVEE';

    protected $table = 'classes';

    protected $fillable = [
        'nom',
        'code',
        'niveau_id',
        'section_id',
        'school_id',
        'annee_scolaire_id',
        'local',
        'salle',
        'capacite',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'capacite' => 'integer',
    ];

    protected $appends = ['statut_label', 'effectif'];

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('statut', self::STATUS_ACTIVE);
    }

    public function scopeBySchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeByNiveau($query, int $niveauId)
    {
        return $query->where('niveau_id', $niveauId);
    }

    public function scopeByAnneeScolaire($query, int $anneeId)
    {
        return $query->where('annee_scolaire_id', $anneeId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Accessors
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_INACTIVE => 'Inactive',
            self::STATUS_ARCHIVEE => 'Archivée',
            default => 'Inconnu',
        };
    }

    public function getEffectifAttribute(): int
    {
        return $this->inscriptions()->where('statut', 'ACTIVE')->count();
    }

    /**
     * Relationships
     */
    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function enseignants(): BelongsToMany
    {
        return $this->belongsToMany(Enseignant::class, 'affectations_enseignants', 'classe_id', 'enseignant_id')
            ->withPivot(['matiere', 'est_titulaire', 'date_debut', 'date_fin', 'statut'])
            ->withTimestamps();
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationEnseignant::class, 'classe_id');
    }

    public function inscriptions(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(
            Inscription::class,
            AffectationClasse::class,
            'classe_id',      // Foreign key on AffectationClasse table
            'id',             // Foreign key on Inscription table
            'id',             // Local key on Classe table
            'inscription_id'  // Local key on AffectationClasse table
        );
    }

    public function eleves(): BelongsToMany
    {
        return $this->belongsToMany(Eleve::class, 'inscriptions_eleves', 'classe_id', 'eleve_id')
            ->withPivot(['annee_scolaire', 'date_inscription', 'statut', 'numero_ordre'])
            ->withTimestamps();
    }

    /**
     * Helper Methods
     */
    public function getTitulaire(): ?Enseignant
    {
        return $this->enseignants()
            ->wherePivot('est_titulaire', true)
            ->wherePivot('statut', 'ACTIVE')
            ->first();
    }

    public function hasCapacity(): bool
    {
        if (is_null($this->capacite)) {
            return true;
        }

        return $this->effectif < $this->capacite;
    }


    
}
