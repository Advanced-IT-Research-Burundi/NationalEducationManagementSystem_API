<?php

namespace App\Models;

use App\Traits\HasDataScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Eleve extends Model
{
    use HasDataScope, HasFactory, SoftDeletes;

    // Status constants
    const STATUS_INSCRIT = 'INSCRIT';

    const STATUS_ACTIF = 'ACTIF';

    const STATUS_SUSPENDU = 'SUSPENDU';

    const STATUS_TRANSFERE = 'TRANSFERE';

    const STATUS_DIPLOME = 'DIPLOME';

    const STATUS_ABANDONNE = 'ABANDONNE';

    // Gender constants
    const SEXE_M = 'M';

    const SEXE_F = 'F';

    protected $table = 'eleves';

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'date_naissance',
        'lieu_naissance',
        'sexe',
        'nom_pere',
        'nom_mere',
        'contact_parent',
        'adresse',
        'school_id',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'date_naissance' => 'date',
    ];

    protected $appends = ['statut_label', 'nom_complet', 'age'];

    /**
     * Query Scopes
     */
    public function scopeActif($query)
    {
        return $query->whereIn('statut', [self::STATUS_INSCRIT, self::STATUS_ACTIF]);
    }

    public function scopeBySchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeBySexe($query, string $sexe)
    {
        return $query->where('sexe', $sexe);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('matricule', 'LIKE', "%{$search}%")
                ->orWhere('nom', 'LIKE', "%{$search}%")
                ->orWhere('prenom', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Accessors
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUS_INSCRIT => 'Inscrit',
            self::STATUS_ACTIF => 'Actif',
            self::STATUS_SUSPENDU => 'Suspendu',
            self::STATUS_TRANSFERE => 'Transféré',
            self::STATUS_DIPLOME => 'Diplômé',
            self::STATUS_ABANDONNE => 'Abandonné',
            default => 'Inconnu',
        };
    }

    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function getAgeAttribute(): ?int
    {
        if (! $this->date_naissance) {
            return null;
        }

        return $this->date_naissance->age;
    }

    /**
     * Relationships
     */
    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'inscriptions_eleves', 'eleve_id', 'classe_id')
            ->withPivot(['annee_scolaire', 'date_inscription', 'statut', 'numero_ordre', 'observations'])
            ->withTimestamps();
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(InscriptionEleve::class, 'eleve_id');
    }

    /**
     * Helper Methods
     */
    public function getCurrentClasse(): ?Classe
    {
        return $this->classes()
            ->wherePivot('statut', 'ACTIVE')
            ->orderByDesc('pivot_date_inscription')
            ->first();
    }

    public function isEnrolledInClass(int $classeId): bool
    {
        return $this->inscriptions()
            ->where('classe_id', $classeId)
            ->where('statut', 'ACTIVE')
            ->exists();
    }

    public function canEnroll(): bool
    {
        return in_array($this->statut, [self::STATUS_INSCRIT, self::STATUS_ACTIF]);
    }
}
