<?php

namespace App\Models;

use App\Enums\StatutMouvement;
use App\Enums\TypeMouvement;
use App\Traits\HasDataScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MouvementEleve extends Model
{
    use HasDataScope, HasFactory;

    protected $table = 'mouvements_eleve';

    protected $fillable = [
        'eleve_id',
        'annee_scolaire_id',
        'type_mouvement',
        'date_mouvement',
        'ecole_origine_id',
        'ecole_destination_id',
        'classe_origine_id',
        'motif',
        'document_reference',
        'document_path',
        'statut',
        'valide_par',
        'date_validation',
        'observations',
        'created_by',
    ];

    protected $appends = ['type_label', 'statut_label'];

    /**
     * Boot method for auto-assignment and validation.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (MouvementEleve $mouvement) {
            if (empty($mouvement->statut)) {
                $mouvement->statut = StatutMouvement::EnAttente->value;
            }

            if (empty($mouvement->created_by) && Auth::check()) {
                $mouvement->created_by = Auth::id();
            }

            if (empty($mouvement->date_mouvement)) {
                $mouvement->date_mouvement = now()->toDateString();
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date_mouvement' => 'date',
            'date_validation' => 'datetime',
            'type_mouvement' => TypeMouvement::class,
            'statut' => StatutMouvement::class,
        ];
    }

    /**
     * Query Scopes
     */
    public function scopeByEleve(Builder $query, int $eleveId): Builder
    {
        return $query->where('eleve_id', $eleveId);
    }

    public function scopeByAnneeScolaire(Builder $query, int $anneeScolaireId): Builder
    {
        return $query->where('annee_scolaire_id', $anneeScolaireId);
    }

    public function scopeByType(Builder $query, TypeMouvement|string $type): Builder
    {
        $value = $type instanceof TypeMouvement ? $type->value : $type;

        return $query->where('type_mouvement', $value);
    }

    public function scopeByEcoleOrigine(Builder $query, int $ecoleId): Builder
    {
        return $query->where('ecole_origine_id', $ecoleId);
    }

    public function scopeByEcoleDestination(Builder $query, int $ecoleId): Builder
    {
        return $query->where('ecole_destination_id', $ecoleId);
    }

    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', StatutMouvement::EnAttente->value);
    }

    public function scopeValide(Builder $query): Builder
    {
        return $query->where('statut', StatutMouvement::Valide->value);
    }

    public function scopeRejete(Builder $query): Builder
    {
        return $query->where('statut', StatutMouvement::Rejete->value);
    }

    public function scopeTransferts(Builder $query): Builder
    {
        return $query->whereIn('type_mouvement', [
            TypeMouvement::TransfertSortant->value,
            TypeMouvement::TransfertEntrant->value,
        ]);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('date_mouvement', '>=', now()->subDays($days));
    }

    /**
     * Scope to filter mouvements by user's administrative hierarchy.
     */
    public function scopeForCurrentUser(Builder $query): Builder
    {
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('Admin National') || $user->admin_level === 'PAYS') {
            return $query;
        }

        $level = $user->admin_level;
        $entityId = $user->admin_entity_id;

        if (! $level || ! $entityId) {
            return $query->whereRaw('1 = 0');
        }

        return match ($level) {
            'ECOLE', 'SCHOOL' => $query->where(function ($q) use ($entityId) {
                $q->where('ecole_origine_id', $entityId)
                    ->orWhere('ecole_destination_id', $entityId);
            }),
            'ZONE' => $query->where(function ($q) use ($entityId) {
                $q->whereHas('ecoleOrigine', fn ($sub) => $sub->where('zone_id', $entityId))
                    ->orWhereHas('ecoleDestination', fn ($sub) => $sub->where('zone_id', $entityId));
            }),
            'COMMUNE' => $query->where(function ($q) use ($entityId) {
                $q->whereHas('ecoleOrigine', fn ($sub) => $sub->where('commune_id', $entityId))
                    ->orWhereHas('ecoleDestination', fn ($sub) => $sub->where('commune_id', $entityId));
            }),
            'PROVINCE' => $query->where(function ($q) use ($entityId) {
                $q->whereHas('ecoleOrigine', fn ($sub) => $sub->where('province_id', $entityId))
                    ->orWhereHas('ecoleDestination', fn ($sub) => $sub->where('province_id', $entityId));
            }),
            'MINISTERE' => $query,
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * Accessors
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type_mouvement?->label() ?? 'Non défini';
    }

    public function getStatutLabelAttribute(): string
    {
        return $this->statut?->label() ?? 'Non défini';
    }

    public function getTypeColorAttribute(): string
    {
        return $this->type_mouvement?->color() ?? 'secondary';
    }

    public function getStatutColorAttribute(): string
    {
        return $this->statut?->color() ?? 'secondary';
    }

    /**
     * Relationships
     */
    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecoleOrigine(): BelongsTo
    {
        return $this->belongsTo(School::class, 'ecole_origine_id');
    }

    public function ecoleDestination(): BelongsTo
    {
        return $this->belongsTo(School::class, 'ecole_destination_id');
    }

    public function classeOrigine(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'classe_origine_id');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Workflow Methods
     */
    public function canValidate(): bool
    {
        return $this->statut === StatutMouvement::EnAttente;
    }

    public function canReject(): bool
    {
        return $this->statut === StatutMouvement::EnAttente;
    }

    public function canModify(): bool
    {
        return $this->statut === StatutMouvement::EnAttente;
    }

    /**
     * Valide le mouvement et applique les effets sur l'élève.
     */
    public function validate(?string $observations = null): bool
    {
        if (! $this->canValidate()) {
            return false;
        }

        return DB::transaction(function () use ($observations) {
            $this->statut = StatutMouvement::Valide;
            $this->date_validation = now();
            $this->valide_par = Auth::id();

            if ($observations) {
                $this->observations = $observations;
            }

            $saved = $this->save();

            if ($saved) {
                $this->applyEffectsOnEleve();
            }

            return $saved;
        });
    }

    /**
     * Rejette le mouvement.
     */
    public function reject(string $motifRejet): bool
    {
        if (! $this->canReject()) {
            return false;
        }

        $this->statut = StatutMouvement::Rejete;
        $this->date_validation = now();
        $this->valide_par = Auth::id();
        $this->observations = $motifRejet;

        return $this->save();
    }

    /**
     * Applique les effets du mouvement sur le statut de l'élève.
     */
    protected function applyEffectsOnEleve(): void
    {
        if (! $this->type_mouvement?->affectsEleveStatus()) {
            return;
        }

        $newStatus = $this->type_mouvement->resultingEleveStatus();

        if ($newStatus && $this->eleve) {
            $this->eleve->update(['statut_global' => $newStatus]);
        }
    }

    /**
     * Business Rule Methods
     */

    /**
     * Vérifie si ce mouvement nécessite une validation hiérarchique.
     */
    public function requiresHierarchicalValidation(): bool
    {
        return $this->type_mouvement?->requiresValidation() ?? false;
    }

    /**
     * Vérifie si le mouvement est un transfert inter-écoles.
     */
    public function isTransfertInterEcoles(): bool
    {
        return $this->type_mouvement === TypeMouvement::TransfertSortant
            && $this->ecole_destination_id !== null
            && $this->ecole_origine_id !== $this->ecole_destination_id;
    }

    /**
     * Vérifie si l'élève peut avoir ce type de mouvement.
     */
    public function isValidForEleve(): bool
    {
        if (! $this->eleve) {
            return false;
        }

        // Un élève décédé ne peut plus avoir de mouvement
        if ($this->eleve->statut_global === Eleve::STATUT_DECEDE) {
            return false;
        }

        // Un transfert entrant nécessite que l'élève soit en statut transféré
        if ($this->type_mouvement === TypeMouvement::TransfertEntrant) {
            return $this->eleve->statut_global === Eleve::STATUT_TRANSFERE;
        }

        // Une réintégration nécessite que l'élève soit inactif ou abandonné
        if ($this->type_mouvement === TypeMouvement::Reintegration) {
            return in_array($this->eleve->statut_global, [
                Eleve::STATUT_INACTIF,
                Eleve::STATUT_ABANDONNE,
            ]);
        }

        return true;
    }

    /**
     * Vérifie s'il existe déjà un mouvement en attente pour cet élève cette année.
     */
    public static function hasPendingMouvement(int $eleveId, int $anneeScolaireId): bool
    {
        return static::query()
            ->byEleve($eleveId)
            ->byAnneeScolaire($anneeScolaireId)
            ->enAttente()
            ->exists();
    }

    /**
     * Compte les redoublements d'un élève pour un niveau donné.
     */
    public static function countRedoublements(int $eleveId, ?int $niveauId = null): int
    {
        $query = static::query()
            ->byEleve($eleveId)
            ->byType(TypeMouvement::Redoublement)
            ->valide();

        // Si on veut filtrer par niveau, on joint avec la classe d'origine
        if ($niveauId) {
            $query->whereHas('classeOrigine', fn ($q) => $q->where('niveau_id', $niveauId));
        }

        return $query->count();
    }

    /**
     * Crée un mouvement de transfert sortant.
     *
     * @param array{
     *     eleve_id: int,
     *     annee_scolaire_id: int,
     *     ecole_origine_id: int,
     *     ecole_destination_id?: int,
     *     classe_origine_id?: int,
     *     motif: string,
     *     document_reference?: string,
     *     document_path?: string,
     *     observations?: string
     * } $data
     */
    public static function createTransfertSortant(array $data): static
    {
        return static::create([
            ...$data,
            'type_mouvement' => TypeMouvement::TransfertSortant->value,
            'date_mouvement' => $data['date_mouvement'] ?? now()->toDateString(),
        ]);
    }

    /**
     * Crée un mouvement d'abandon.
     *
     * @param array{
     *     eleve_id: int,
     *     annee_scolaire_id: int,
     *     ecole_origine_id: int,
     *     classe_origine_id?: int,
     *     motif: string,
     *     observations?: string
     * } $data
     */
    public static function createAbandon(array $data): static
    {
        return static::create([
            ...$data,
            'type_mouvement' => TypeMouvement::Abandon->value,
            'date_mouvement' => $data['date_mouvement'] ?? now()->toDateString(),
        ]);
    }

    /**
     * Crée un mouvement de passage au niveau supérieur.
     *
     * @param array{
     *     eleve_id: int,
     *     annee_scolaire_id: int,
     *     ecole_origine_id: int,
     *     classe_origine_id: int,
     *     observations?: string
     * } $data
     */
    public static function createPassage(array $data): static
    {
        $mouvement = static::create([
            ...$data,
            'type_mouvement' => TypeMouvement::Passage->value,
            'date_mouvement' => $data['date_mouvement'] ?? now()->toDateString(),
            'motif' => $data['motif'] ?? 'Passage au niveau supérieur',
            'statut' => StatutMouvement::Valide->value,
            'date_validation' => now(),
            'valide_par' => Auth::id(),
        ]);

        return $mouvement;
    }

    /**
     * Crée un mouvement de redoublement.
     *
     * @param array{
     *     eleve_id: int,
     *     annee_scolaire_id: int,
     *     ecole_origine_id: int,
     *     classe_origine_id: int,
     *     motif: string,
     *     observations?: string
     * } $data
     */
    public static function createRedoublement(array $data): static
    {
        $mouvement = static::create([
            ...$data,
            'type_mouvement' => TypeMouvement::Redoublement->value,
            'date_mouvement' => $data['date_mouvement'] ?? now()->toDateString(),
            'statut' => StatutMouvement::Valide->value,
            'date_validation' => now(),
            'valide_par' => Auth::id(),
        ]);

        return $mouvement;
    }
}
