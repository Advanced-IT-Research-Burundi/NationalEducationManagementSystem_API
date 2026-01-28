<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AffectationEnseignant extends Model
{
    use HasFactory, SoftDeletes;

    // Status constants
    const STATUS_ACTIVE = 'ACTIVE';

    const STATUS_TERMINEE = 'TERMINEE';

    const STATUS_ANNULEE = 'ANNULEE';

    protected $table = 'affectations_enseignants';

    protected $fillable = [
        'enseignant_id',
        'classe_id',
        'matiere',
        'est_titulaire',
        'date_debut',
        'date_fin',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'est_titulaire' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    protected $appends = ['statut_label'];

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('statut', self::STATUS_ACTIVE);
    }

    public function scopeByEnseignant($query, int $enseignantId)
    {
        return $query->where('enseignant_id', $enseignantId);
    }

    public function scopeByClasse($query, int $classeId)
    {
        return $query->where('classe_id', $classeId);
    }

    public function scopeTitulaire($query)
    {
        return $query->where('est_titulaire', true);
    }

    /**
     * Accessors
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_TERMINEE => 'Terminée',
            self::STATUS_ANNULEE => 'Annulée',
            default => 'Inconnu',
        };
    }

    /**
     * Relationships
     */
    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class);
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
    public function canTerminate(): bool
    {
        return $this->statut === self::STATUS_ACTIVE;
    }

    public function terminate(): bool
    {
        if (! $this->canTerminate()) {
            return false;
        }

        $this->statut = self::STATUS_TERMINEE;
        $this->date_fin = now();

        return $this->save();
    }
}
