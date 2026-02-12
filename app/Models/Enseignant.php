<?php

namespace App\Models;

use App\Traits\HasDataScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enseignant extends Model
{
    use HasDataScope, HasFactory, SoftDeletes;

    // Status constants
    const STATUS_ACTIF = 'ACTIF';

    const STATUS_INACTIF = 'INACTIF';

    const STATUS_CONGE = 'CONGE';

    const STATUS_SUSPENDU = 'SUSPENDU';

    const STATUS_RETRAITE = 'RETRAITE';

    // Qualification constants
    const QUALIF_LICENCE = 'LICENCE';

    const QUALIF_MASTER = 'MASTER';

    const QUALIF_DOCTORAT = 'DOCTORAT';

    const QUALIF_DIPLOME_PEDAGOGIQUE = 'DIPLOME_PEDAGOGIQUE';

    const QUALIF_AUTRE = 'AUTRE';

    protected $table = 'enseignants';

    protected $fillable = [
        'user_id',
        'ecole_id',
        'matricule',
        'specialite',
        'qualification',
        'annees_experience',
        'date_embauche',
        'telephone',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'annees_experience' => 'integer',
        'date_embauche' => 'date',
    ];

    protected $appends = ['statut_label', 'qualification_label', 'nom_complet'];

    /**
     * Query Scopes
     */
    public function scopeActif($query)
    {
        return $query->where('statut', self::STATUS_ACTIF);
    }

    public function scopeBySchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeBySpecialite($query, string $specialite)
    {
        return $query->where('specialite', $specialite);
    }

    public function scopeByQualification($query, string $qualification)
    {
        return $query->where('qualification', $qualification);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('matricule', 'LIKE', "%{$search}%")
                ->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
        });
    }

    /**
     * Accessors
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUS_ACTIF => 'Actif',
            self::STATUS_INACTIF => 'Inactif',
            self::STATUS_CONGE => 'En congé',
            self::STATUS_SUSPENDU => 'Suspendu',
            self::STATUS_RETRAITE => 'Retraité',
            default => 'Inconnu',
        };
    }

    public function getQualificationLabelAttribute(): ?string
    {
        return match ($this->qualification) {
            self::QUALIF_LICENCE => 'Licence',
            self::QUALIF_MASTER => 'Master',
            self::QUALIF_DOCTORAT => 'Doctorat',
            self::QUALIF_DIPLOME_PEDAGOGIQUE => 'Diplôme Pédagogique',
            self::QUALIF_AUTRE => 'Autre',
            default => null,
        };
    }

    public function getNomCompletAttribute(): string
    {
        return $this->user?->name ?? 'N/A';
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'ecole_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'affectations_enseignants', 'enseignant_id', 'classe_id')
            ->withPivot(['matiere', 'est_titulaire', 'date_debut', 'date_fin', 'statut'])
            ->withTimestamps();
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationEnseignant::class, 'enseignant_id');
    }

    /**
     * Helper Methods
     */
    public function getActiveClasses()
    {
        return $this->classes()->wherePivot('statut', 'ACTIVE')->get();
    }

    public function isAssignedToClass(int $classeId): bool
    {
        return $this->affectations()
            ->where('classe_id', $classeId)
            ->where('statut', 'ACTIVE')
            ->exists();
    }

    public function canBeAssigned(): bool
    {
        return $this->statut === self::STATUS_ACTIF;
    }
}
