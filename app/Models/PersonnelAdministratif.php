<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PersonnelAdministratif extends Model
{
    use HasFactory;

    public const STATUT_ACTIF = 'ACTIF';
    public const STATUT_INACTIF = 'INACTIF';
    public const STATUT_SUSPENDU = 'SUSPENDU';
    public const STATUT_RETRAITE = 'RETRAITE';

    protected $fillable = [
        'user_id',
        'service_id',
        'fonction_id',
        'matricule',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'telephone',
        'email',
        'type_personnel',
        'statut',
        'date_recrutement',
        'adresse',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_recrutement' => 'date',
    ];

    protected $appends = ['nom_complet'];

    public function getNomCompletAttribute(): string
    {
        return trim(($this->nom ?? '') . ' ' . ($this->prenom ?? ''));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function fonction(): BelongsTo
    {
        return $this->belongsTo(Fonction::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(PersonnelAdministratifMouvement::class, 'personnel_administratif_id');
    }
}
