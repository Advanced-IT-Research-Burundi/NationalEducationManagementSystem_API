<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InscriptionEleve extends Model
{
    use HasFactory, LogsActivity;

    // Status constants
    const STATUS_BROUILLON = 'brouillon';

    const STATUS_SOUMIS = 'soumis';

    const STATUS_VALIDE = 'valide';

    const STATUS_REJETE = 'rejete';

    const STATUS_ANNULE = 'annule';

    // Legacy status for compatibility
    const STATUS_ACTIVE = 'ACTIVE';

    const STATUS_TRANSFEREE = 'TRANSFEREE';

    const STATUS_TERMINEE = 'TERMINEE';

    const STATUS_ANNULEE = 'ANNULEE';

    protected $table = 'inscriptions';

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'ecole_id',
        'annee_scolaire_id',
        'campagne_id',
        'niveau_demande_id',
        'date_inscription',
        'type_inscription',
        'statut',
        'numero_inscription',
        'motif_rejet',
        'est_redoublant',
        'pieces_fournies',
        'observations',
        'date_soumission',
        'date_validation',
        'created_by',
        'valide_by',
    ];

    protected function casts(): array
    {
        return [
            'date_inscription' => 'date',
            'date_soumission' => 'datetime',
            'date_validation' => 'datetime',
            'est_redoublant' => 'boolean',
            'pieces_fournies' => 'array',
        ];
    }

    protected $appends = ['statut_label', 'type_label'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('inscriptions_eleves')
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot method to auto-generate numero_inscription
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (InscriptionEleve $inscription) {
            if (empty($inscription->numero_inscription)) {
                $inscription->numero_inscription = self::generateNumeroInscription(
                    $inscription->ecole_id,
                    $inscription->annee_scolaire_id
                );
            }

            if (empty($inscription->statut)) {
                $inscription->statut = self::STATUS_BROUILLON;
            }

            if (empty($inscription->created_by) && Auth::check()) {
                $inscription->created_by = Auth::id();
            }
        });
    }

    /**
     * Generate unique inscription number
     */
    public static function generateNumeroInscription(?int $ecoleId, ?int $anneeScolaireId): string
    {
        $prefix = 'INSCR';
        $year = date('Y');

        $lastInscription = self::where('ecole_id', $ecoleId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInscription ? ((int) substr($lastInscription->numero_inscription, -4)) + 1 : 1;

        return sprintf('%s%s%04d', $prefix, $year, $sequence);
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query): Builder
    {
        return $query->whereIn('statut', [self::STATUS_VALIDE, self::STATUS_ACTIVE]);
    }

    public function scopeBrouillon($query): Builder
    {
        return $query->where('statut', self::STATUS_BROUILLON);
    }

    public function scopeSoumis($query): Builder
    {
        return $query->where('statut', self::STATUS_SOUMIS);
    }

    public function scopeByEleve($query, int $eleveId): Builder
    {
        return $query->where('eleve_id', $eleveId);
    }

    public function scopeByClasse($query, int $classeId): Builder
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeByEcole($query, int $ecoleId): Builder
    {
        return $query->where('ecole_id', $ecoleId);
    }

    public function scopeByAnneeScolaire($query, int $anneeScolaireId): Builder
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeByCampagne($query, int $campagneId): Builder
    {
        return $query->where('campagne_id', $campagneId);
    }

    /**
     * Scope to filter inscriptions by user's administrative hierarchy.
     */
    public function scopeForCurrentUser(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        // Admin National sees everything
        if ($user->hasRole('Admin National') || $user->admin_level === 'PAYS') {
            return $query;
        }

        $level = $user->admin_level;
        $entityId = $user->admin_entity_id;

        if (! $level || ! $entityId) {
            return $query->whereRaw('1 = 0');
        }

        return match ($level) {
            'ECOLE' => $query->where('ecole_id', $entityId),
            'ZONE' => $query->whereHas('ecole', fn ($q) => $q->where('zone_id', $entityId)),
            'COMMUNE' => $query->whereHas('ecole', fn ($q) => $q->where('commune_id', $entityId)),
            'PROVINCE' => $query->whereHas('ecole', fn ($q) => $q->where('province_id', $entityId)),
            'MINISTERE' => $query,
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Accessors
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUS_BROUILLON => 'Brouillon',
            self::STATUS_SOUMIS => 'Soumis',
            self::STATUS_VALIDE, self::STATUS_ACTIVE => 'Validé',
            self::STATUS_REJETE => 'Rejeté',
            self::STATUS_ANNULE, self::STATUS_ANNULEE => 'Annulé',
            self::STATUS_TRANSFEREE => 'Transférée',
            self::STATUS_TERMINEE => 'Terminée',
            default => 'Inconnu',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type_inscription) {
            'nouvelle' => 'Nouvelle inscription',
            'reinscription' => 'Réinscription',
            'transfert_entrant' => 'Transfert entrant',
            default => $this->type_inscription ?? 'Non défini',
        };
    }

    /**
     * Relationships
     */
    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CampagneInscription::class, 'campagne_id');
    }

    public function niveauDemande(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_demande_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_by');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationEleve::class, 'inscription_id');
    }

    /**
     * Workflow Methods
     */
    public function canSubmit(): bool
    {
        return $this->statut === self::STATUS_BROUILLON;
    }

    public function canValidate(): bool
    {
        return $this->statut === self::STATUS_SOUMIS;
    }

    public function canReject(): bool
    {
        return $this->statut === self::STATUS_SOUMIS;
    }

    public function canCancel(): bool
    {
        return in_array($this->statut, [self::STATUS_BROUILLON, self::STATUS_SOUMIS]);
    }

    public function submit(): bool
    {
        if (! $this->canSubmit()) {
            return false;
        }

        $this->statut = self::STATUS_SOUMIS;
        $this->date_soumission = now();

        return $this->save();
    }

    public function validate(): bool
    {
        if (! $this->canValidate()) {
            return false;
        }

        $this->statut = self::STATUS_VALIDE;
        $this->date_validation = now();
        $this->valide_by = Auth::id();

        // Update eleve status
        if ($this->eleve) {
            $this->eleve->update(['statut' => Eleve::STATUS_ACTIF]);
        }

        return $this->save();
    }

    public function reject(string $motif): bool
    {
        if (! $this->canReject()) {
            return false;
        }

        $this->statut = self::STATUS_REJETE;
        $this->motif_rejet = $motif;
        $this->valide_by = Auth::id();

        return $this->save();
    }

    public function cancel(): bool
    {
        if (! $this->canCancel()) {
            return false;
        }

        $this->statut = self::STATUS_ANNULE;

        return $this->save();
    }

    /**
     * Helper Methods
     */
    public function canTransfer(): bool
    {
        return in_array($this->statut, [self::STATUS_VALIDE, self::STATUS_ACTIVE]);
    }
}
