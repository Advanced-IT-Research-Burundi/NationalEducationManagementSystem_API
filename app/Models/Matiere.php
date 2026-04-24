<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Matiere extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'code',
        'categorie_cours_id',
        'est_principale',
        'ponderation_tj',
        'ponderation_examen',
        'credit_heures',
        'section_id',
        'niveau_id',
        'description',
        'coefficient',
        'heures_par_semaine',
        'actif',
    ];

    protected $casts = [
        'actif' => 'boolean',
        'est_principale' => 'boolean',
        'coefficient' => 'integer',
        'heures_par_semaine' => 'integer',
        'ponderation_tj' => 'decimal:2',
        'ponderation_examen' => 'decimal:2',
        'credit_heures' => 'decimal:2',
    ];

    /**
     * Auto-generate code on creation.
     */
    protected static function booted(): void
    {
        static::creating(function (Matiere $matiere) {
            if (empty($matiere->code)) {
                $matiere->code = self::generateCode($matiere->nom);
            }
        });
    }

    /**
     * Generate a unique code from the course name.
     */
    private static function generateCode(string $nom): string
    {
        // Take first 3 chars uppercase + random suffix
        $prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $nom), 0, 3));
        $suffix = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $code = $prefix . $suffix;

        // Ensure uniqueness
        while (self::withTrashed()->where('code', $code)->exists()) {
            $suffix = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $code = $prefix . $suffix;
        }

        return $code;
    }

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('actif', true);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('nom', 'LIKE', "%{$search}%")
                ->orWhere('code', 'LIKE', "%{$search}%");
        });
    }

    public function scopeByCategorie($query, int $categorieId)
    {
        return $query->where('categorie_cours_id', $categorieId);
    }

    public function scopeBySection($query, int $sectionId)
    {
        return $query->where(function ($q) use ($sectionId) {
            $q->where('section_id', $sectionId)
              ->orWhereHas('sections', function ($sq) use ($sectionId) {
                  $sq->where('sections.id', $sectionId);
              });
        });
    }

    public function scopeByNiveau($query, int $niveauId)
    {
        return $query->where(function ($q) use ($niveauId) {
            $q->where('niveau_id', $niveauId)
              ->orWhereHas('niveaux', function ($nq) use ($niveauId) {
                  $nq->where('niveaux_scolaires.id', $niveauId);
              });
        });
    }

    /**
     * Filter matieres by levels associated with a specific school.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where(function ($q) use ($schoolId) {
            // Check legacy niveau_id
            $q->whereIn('niveau_id', function ($sub) use ($schoolId) {
                $sub->select('niveau_scolaire_id')
                    ->from('niveau_school')
                    ->where('school_id', $schoolId);
            })
            // Check many-to-many relationship
            ->orWhereHas('niveaux.schools', function ($sq) use ($schoolId) {
                $sq->where('schools.id', $schoolId);
            });
        });
    }

    /**
     * Relationships
     */
    public function categorieCours(): BelongsTo
    {
        return $this->belongsTo(CategorieCours::class, 'categorie_cours_id');
    }

    public function niveau(): BelongsTo
    {
        return $this->belongsTo(Niveau::class, 'niveau_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function niveaux(): BelongsToMany
    {
        return $this->belongsToMany(Niveau::class, 'matiere_niveaux', 'matiere_id', 'niveau_id')
            ->withTimestamps();
    }

    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(Section::class, 'matiere_sections', 'matiere_id', 'section_id')
            ->withTimestamps();
    }

    public function enseignant(): BelongsTo
    {
        return $this->belongsTo(Enseignant::class, 'enseignant_id');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationMatiere::class);
    }

    public function enseignants()
    {
        return $this->belongsToMany(Enseignant::class, 'affectations_matieres')
            ->withPivot(['school_id', 'annee_scolaire_id', 'statut'])
            ->withTimestamps();
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'cours_id');
    }

    public function resultats(): HasMany
    {
        return $this->hasMany(Resultat::class);
    }
}
