<?php

namespace App\Models;

use App\Traits\HasDataScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Eleve extends Model
{
    use HasDataScope, HasFactory, SoftDeletes;

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
        'colline_origine_id',
        'adresse',
        'nom_pere',
        'nom_mere',
        'contact_tuteur',
        'nom_tuteur',
        'photo_path',
        'est_orphelin',
        'a_handicap',
        'type_handicap',
        'ecole_origine_id',
        'statut_global',
        'created_by',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'est_orphelin' => 'boolean',
        'a_handicap' => 'boolean',
    ];

    protected $appends = ['nom_complet', 'age'];

    /**
     * Query Scopes
     */
    public function scopeActif($query)
    {
        return $query->where('statut_global', self::STATUT_ACTIF);
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
        if (! $this->date_naissance) {
            return null;
        }

        return $this->date_naissance->age;
    }

    /**
     * Relationships
     */
    public function collineOrigine(): BelongsTo
    {
        return $this->belongsTo(Colline::class, 'colline_origine_id');
    }

    public function ecoleOrigine(): BelongsTo
    {
        return $this->belongsTo(School::class, 'ecole_origine_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class);
    }

    public function inscriptionsEleves(): HasMany
    {
        return $this->hasMany(InscriptionEleve::class);
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

    // Scopes for HasDataScope - No direct scope column on Eleve anymore globally, usually scoped via Inscription
    // But if we want to scope Eleve list by school... we can't easily unless we join inscriptions.
    // For now, let's leave default implementation or null.
    // However, HasDataScope trait requires getScopeColumn.
    // Since Eleve is global, maybe we return null so it's visible to higher levels?
    // Or we implement a scope that joins with inscriptions?
    // The previous implementation used school_id.
    // Let's set it to null for now (all access or custom scope need implementation).
    protected static function getScopeColumn(): ?string
    {
        return null;
    }

    protected static function getScopeRelation(): ?string
    {
        return null;
    }

    /**
     * Helper Methods for Mouvements
     */

    /**
     * Vérifie si l'élève peut être transféré.
     */
    public function canBeTransferred(): bool
    {
        return $this->statut_global === self::STATUT_ACTIF;
    }

    /**
     * Vérifie si l'élève peut être réintégré.
     */
    public function canBeReintegrated(): bool
    {
        return in_array($this->statut_global, [
            self::STATUT_INACTIF,
            self::STATUT_ABANDONNE,
        ]);
    }

    /**
     * Vérifie si l'élève a un mouvement en attente pour une année scolaire.
     */
    public function hasPendingMouvement(?int $anneeScolaireId = null): bool
    {
        $query = $this->mouvements()->where('statut', 'en_attente');

        if ($anneeScolaireId) {
            $query->where('annee_scolaire_id', $anneeScolaireId);
        }

        return $query->exists();
    }

    /**
     * Compte les redoublements de l'élève.
     */
    public function countRedoublements(?int $niveauId = null): int
    {
        return MouvementEleve::countRedoublements($this->id, $niveauId);
    }

    /**
     * Vérifie si l'élève peut encore redoubler (max 2 fois par niveau).
     */
    public function canRedouble(int $niveauId): bool
    {
        return $this->countRedoublements($niveauId) < 2;
    }
}
