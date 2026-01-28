<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InscriptionEleve extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_ACTIVE = 'ACTIVE';

    const STATUS_TRANSFEREE = 'TRANSFEREE';

    const STATUS_TERMINEE = 'TERMINEE';

    const STATUS_ANNULEE = 'ANNULEE';

    protected $table = 'inscriptions_eleves';

    protected $fillable = [
        'eleve_id',
        'classe_id',
        'annee_scolaire',
        'date_inscription',
        'statut',
        'numero_ordre',
        'observations',
        'created_by',
    ];

    protected $casts = [
        'date_inscription' => 'date',
        'numero_ordre' => 'integer',
    ];

    protected $appends = ['statut_label'];

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('statut', self::STATUS_ACTIVE);
    }

    public function scopeByEleve($query, int $eleveId)
    {
        return $query->where('eleve_id', $eleveId);
    }

    public function scopeByClasse($query, int $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeByAnneeScolaire($query, string $annee)
    {
        return $query->where('annee_scolaire', $annee);
    }

    /**
     * Accessors
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_TRANSFEREE => 'Transférée',
            self::STATUS_TERMINEE => 'Terminée',
            self::STATUS_ANNULEE => 'Annulée',
            default => 'Inconnu',
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Helper Methods
     */
    public function canTransfer(): bool
    {
        return $this->statut === self::STATUS_ACTIVE;
    }

    public function transfer(int $newClasseId, string $anneeScolaire): ?InscriptionEleve
    {
        if (! $this->canTransfer()) {
            return null;
        }

        // Mark current as transferred
        $this->statut = self::STATUS_TRANSFEREE;
        $this->save();

        // Create new enrollment
        return InscriptionEleve::create([
            'eleve_id' => $this->eleve_id,
            'classe_id' => $newClasseId,
            'annee_scolaire' => $anneeScolaire,
            'date_inscription' => now(),
            'statut' => self::STATUS_ACTIVE,
            'created_by' => auth()->id(),
        ]);
    }
}
