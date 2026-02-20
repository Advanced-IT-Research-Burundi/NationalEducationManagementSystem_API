<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CampagneCollecte extends Model
{
    use HasFactory, LogsActivity;

    const STATUT_BROUILLON = 'brouillon';
    const STATUT_OUVERTE = 'ouverte';
    const STATUT_FERMEE = 'fermee';

    const NIVEAU_ZONE = 'zone';
    const NIVEAU_COMMUNE = 'commune';
    const NIVEAU_PROVINCE = 'province';

    protected $table = 'campagnes_collecte';

    protected $fillable = [
        'titre',
        'description',
        'date_debut',
        'date_fin',
        'statut',
        'niveau_validation',
        'annee_scolaire_id',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('campagnes_collecte')
            ->dontSubmitEmptyLogs();
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function formulaires(): HasMany
    {
        return $this->hasMany(FormulaireCollecte::class, 'campagne_id')->orderBy('ordre');
    }

    public function reponses(): HasManyThrough
    {
        return $this->hasManyThrough(ReponseCollecte::class, FormulaireCollecte::class, 'campagne_id', 'formulaire_id');
    }

    public function canSubmit(): bool
    {
        return $this->statut === self::STATUT_OUVERTE;
    }

    public function isOpen(): bool
    {
        return $this->statut === self::STATUT_OUVERTE
            && now()->between($this->date_debut, $this->date_fin);
    }
}
