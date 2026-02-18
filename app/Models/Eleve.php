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
        'school_id',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'est_orphelin' => 'boolean',
        'a_handicap' => 'boolean',
        'type_handicap' => 'encrypted',
        'photo_path' => 'encrypted',
        'contact_tuteur' => 'encrypted',
    ];

    protected $appends = ['nom_complet', 'age'];

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

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
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
    protected static function getScopeColumn(): ?string
    {
        return null;
    }

    protected static function getScopeRelation(): ?string
    {
        return null;
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($eleve) {
            $eleve->created_by = auth()->id();
            $eleve->matricule = $eleve->generateMatricule();
        });
    }

    public function generateMatricule()
    {
        $lastMatricule = self::latest()->first()->matricule ?? '000000';
        $newMatricule = str_pad((int) $lastMatricule + 1, 6, '0', STR_PAD_LEFT);

        return $newMatricule;
    }


    public function ecole()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

}
