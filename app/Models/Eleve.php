<?php

namespace App\Models;

use App\Traits\HasDataScope;
use App\Traits\HasMatricule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Eleve extends Model
{
    use HasDataScope, HasFactory, SoftDeletes, LogsActivity, HasMatricule;

    // Status constants
    const STATUT_ACTIF = 'actif';

    const STATUT_INACTIF = 'inactif';

    const STATUT_TRANSFERE = 'transfere';

    const STATUT_ABANDONNE = 'abandonne';

    const STATUT_DECEDE = 'decede';

    protected $table = 'eleves';

    protected $fillable = [
        'matricule',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'nationalite',
        'province_origine_id',
        'commune_origine_id',
        'zone_origine_id',
        'colline_origine_id',
        'niveau_id',
        'adresse',
        'nom_pere',
        'nom_mere',
        'contact_tuteur',
        'nom_tuteur',
        'photo_path',
        'est_orphelin',
        'a_handicap',
        'type_handicap',
        'statut_global',
        'created_by',
        'school_id',
        'est_redoublant',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'est_orphelin' => 'boolean',
        'a_handicap' => 'boolean',
        'est_redoublant' => 'boolean',
        'type_handicap' => 'string',
        'photo_path' => 'string',
        'contact_tuteur' => 'string',
    ];

    protected $appends = ['nom_complet', 'age'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('eleves')
            ->dontSubmitEmptyLogs();
    }

    /**
     * Query Scopes
     */
    public function scopeActif($query)
    {
        return $query->where('statut_global', self::STATUT_ACTIF);
    }

    public function scopeBySchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
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
    public function getNomCompletAttribute(): string
    {
        return "{$this->prenom} {$this->nom}";
    }

    public function getAgeAttribute(): ?int
    {
        if (!$this->date_naissance) {
            return null;
        }

        return $this->date_naissance->age;
    }

    /**
     * Relationships
     */
    public function provinceOrigine(): BelongsTo
    {
        return $this->belongsTo(Province::class, 'province_origine_id');
    }

    public function communeOrigine(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'commune_origine_id');
    }

    public function zoneOrigine(): BelongsTo
    {
        return $this->belongsTo(Zone::class, 'zone_origine_id');
    }

    public function collineOrigine(): BelongsTo
    {
        return $this->belongsTo(Colline::class, 'colline_origine_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function classes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Classe::class, 'eleve_class', 'eleve_id', 'classe_id')
            ->withPivot(['annee_scolaire', 'date_inscription', 'statut', 'numero_ordre'])
            ->withTimestamps();
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function activeInscription()
    {
        return $this->hasOne(Inscription::class)->latestOfMany();
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementEleve::class);
    }

    public function mouvementsValides(): HasMany
    {
        return $this->hasMany(MouvementEleve::class)
            ->where('statut', 'valide')
            ->orderByDesc('date_mouvement');
    }

    public function dernierMouvement(): BelongsTo
    {
        return $this->belongsTo(MouvementEleve::class)
            ->ofMany('date_mouvement', 'max');
    }

    /**
     * Check if the student can be enrolled (not transferred, deceased, droppped out, etc.)
     */
    public function canEnroll(): bool
    {
        $status = $this->statut_global ?? $this->statut ?? 'actif';
        return !in_array(strtolower($status), [
            self::STATUT_TRANSFERE, 
            self::STATUT_DECEDE, 
            self::STATUT_ABANDONNE,
            'diplome'
        ]);
    }

    /**
     * Check if the student is already enrolled in a specific class
     */
    public function isEnrolledInClass($classeId): bool
    {
        return $this->classes()
            ->where('classes.id', $classeId)
            ->wherePivot('statut', 'ACTIVE')
            ->exists();
    }

    protected static function getScopeColumn(): ?string
    {
        return null;
    }

    protected static function getScopeRelation(): ?string
    {
        return null;
    }

    public function ecole()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

}
