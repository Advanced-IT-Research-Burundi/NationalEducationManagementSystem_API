<?php

namespace App\Models;

use App\Enums\CampagneStatut;
use App\Enums\CampagneType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampagneInscription extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'campagnes_inscription';

    protected $fillable = [
        'annee_scolaire_id',
        'ecole_id',
        'type',
        'date_ouverture',
        'date_cloture',
        'statut',
        'quota_max',
        'description',
        'created_by',
    ];

    protected $casts = [
        'type' => CampagneType::class,
        'statut' => CampagneStatut::class,
        'date_ouverture' => 'date',
        'date_cloture' => 'date',
        'quota_max' => 'integer',
    ];

    protected $appends = ['type_label', 'statut_label', 'is_open', 'inscriptions_count'];

    /**
     * Query Scopes
     */
    public function scopeOuverte($query)
    {
        return $query->where('statut', CampagneStatut::Ouverte);
    }

    public function scopePlanifiee($query)
    {
        return $query->where('statut', CampagneStatut::Planifiee);
    }

    public function scopeCloturee($query)
    {
        return $query->where('statut', CampagneStatut::Cloturee);
    }

    public function scopeByAnneeScolaire($query, int $anneeScolaireId)
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeByEcole($query, int $ecoleId)
    {
        return $query->where('ecole_id', $ecoleId);
    }

    public function scopeByType($query, CampagneType|string $type)
    {
        if (is_string($type)) {
            $type = CampagneType::from($type);
        }

        return $query->where('type', $type);
    }

    /**
     * Accessors
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label() ?? 'Inconnu';
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->statut?->label() ?? 'Inconnu';
    }

    public function getIsOpenAttribute(): bool
    {
        return $this->statut === CampagneStatut::Ouverte
            && now()->between($this->date_ouverture, $this->date_cloture);
    }

    public function getInscriptionsCountAttribute(): int
    {
        return $this->inscriptions()->count();
    }

    public function getQuotaRemainingAttribute(): ?int
    {
        if ($this->quota_max === null) {
            return null;
        }

        return max(0, $this->quota_max - $this->inscriptions_count);
    }

    /**
     * Check if quota is reached
     */
    public function isQuotaReached(): bool
    {
        if ($this->quota_max === null) {
            return false;
        }

        return $this->inscriptions_count >= $this->quota_max;
    }

    /**
     * Check if campagne can be opened
     */
    public function canOpen(): bool
    {
        return $this->statut === CampagneStatut::Planifiee
            && now()->lessThanOrEqualTo($this->date_cloture);
    }

    /**
     * Check if campagne can be closed
     */
    public function canClose(): bool
    {
        return $this->statut === CampagneStatut::Ouverte;
    }

    /**
     * Open the campagne
     */
    public function ouvrir(): bool
    {
        if (! $this->canOpen()) {
            return false;
        }

        return $this->update(['statut' => CampagneStatut::Ouverte]);
    }

    /**
     * Close the campagne
     */
    public function cloturer(): bool
    {
        if (! $this->canClose()) {
            return false;
        }

        return $this->update(['statut' => CampagneStatut::Cloturee]);
    }

    /**
     * Relationships
     */
    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(Ecole::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(InscriptionEleve::class, 'campagne_id');
    }
}
