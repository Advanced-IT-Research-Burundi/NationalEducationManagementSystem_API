<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AnneeScolaire extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'annee_scolaires';

    protected $fillable = [
        'code',
        'libelle',
        'date_debut',
        'date_fin',
        'est_active',
    ];

    protected $casts = [
        'est_active' => 'boolean',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    /**
     * Query Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('est_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('date_debut');
    }

    /**
     * Get the currently active school year
     */
    public static function current(): ?self
    {
        $config = ConfigurationAcademique::query()->first();
        if ($config?->current_annee_scolaire_id) {
            return static::withoutGlobalScopes()->find($config->current_annee_scolaire_id);
        }

        return static::active()->first();
    }

    /**
     * Check if this year is currently ongoing
     */
    public function isOngoing(): bool
    {
        $today = now()->toDateString();

        return $this->date_debut <= $today && $this->date_fin >= $today;
    }

    /**
     * Calculate progress percentage
     */
    public function getProgressAttribute(): int
    {
        if (! $this->isOngoing()) {
            return $this->date_fin < now() ? 100 : 0;
        }

        $totalDays = $this->date_debut->diffInDays($this->date_fin);
        $elapsedDays = $this->date_debut->diffInDays(now());

        return $totalDays > 0 ? min(100, (int) round(($elapsedDays / $totalDays) * 100)) : 0;
    }

    /**
     * Get days elapsed
     */
    public function getDaysElapsedAttribute(): int
    {
        if (now() < $this->date_debut) {
            return 0;
        }

        return $this->date_debut->diffInDays(min(now(), $this->date_fin));
    }

    /**
     * Get total days
     */
    public function getTotalDaysAttribute(): int
    {
        return $this->date_debut->diffInDays($this->date_fin);
    }

    /**
     * Relationships
     */
    public function classes(): HasMany
    {
        return $this->hasMany(Classe::class, 'annee_scolaire_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'annee_scolaire_id');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementEleve::class, 'annee_scolaire_id');
    }

    public function trimestres(): HasMany
    {
        return $this->hasMany(Trimestre::class, 'annee_scolaire_id');
    }

    /**
     * Activate this school year (deactivates all others)
     */
    public function activate(): bool
    {
        // Deactivate all other years
        static::where('id', '!=', $this->id)->update(['est_active' => false]);

        // Activate this one
        $updated = $this->update(['est_active' => true]);

        if ($updated) {
            ConfigurationAcademique::current()->forceFill([
                'current_annee_scolaire_id' => $this->id,
                'current_trimestre_id' => null,
            ])->save();
        }

        return $updated;
    }

    /**
     * Deactivate this school year
     */
    public function deactivate(): bool
    {
        return $this->update(['est_active' => false]);
    }
}
