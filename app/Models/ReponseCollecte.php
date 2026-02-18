<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ReponseCollecte extends Model
{
    use HasFactory, LogsActivity;

    const STATUT_BROUILLON = 'brouillon';
    const STATUT_SOUMIS = 'soumis';
    const STATUT_VALIDE_ZONE = 'valide_zone';
    const STATUT_VALIDE_COMMUNE = 'valide_commune';
    const STATUT_VALIDE_PROVINCE = 'valide_province';
    const STATUT_REJETE = 'rejete';

    protected $table = 'reponses_collecte';

    protected $fillable = [
        'formulaire_id',
        'school_id',
        'donnees',
        'statut',
        'soumis_par',
        'soumis_at',
        'valide_par',
        'valide_at',
        'niveau_validation',
        'motif_rejet',
        'created_by',
    ];

    protected $casts = [
        'donnees' => 'array',
        'soumis_at' => 'datetime',
        'valide_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('reponses_collecte')
            ->dontSubmitEmptyLogs();
    }

    public function formulaire(): BelongsTo
    {
        return $this->belongsTo(FormulaireCollecte::class);
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function soumisPar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'soumis_par');
    }

    public function validePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canSubmit(): bool
    {
        return $this->statut === self::STATUT_BROUILLON;
    }

    public function isPendingValidation(): bool
    {
        return in_array($this->statut, [
            self::STATUT_SOUMIS,
            self::STATUT_VALIDE_ZONE,
            self::STATUT_VALIDE_COMMUNE,
        ]);
    }

    public function getNextValidationLevel(): ?string
    {
        return match ($this->statut) {
            self::STATUT_SOUMIS => 'zone',
            self::STATUT_VALIDE_ZONE => 'commune',
            self::STATUT_VALIDE_COMMUNE => 'province',
            default => null,
        };
    }
}
