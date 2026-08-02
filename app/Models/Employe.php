<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employe extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUT_ACTIF = 'ACTIF';
    public const STATUT_SUSPENDU = 'SUSPENDU';
    public const STATUT_CONGE = 'CONGE';
    public const STATUT_DEMISSIONNAIRE = 'DEMISSIONNAIRE';
    public const STATUT_RETRAITE = 'RETRAITE';

    protected $fillable = [
        'user_id',
        'departement_id',
        'poste_id',
        'service_id',
        'superieur_id',
        'matricule',
        'photo_path',
        'nom',
        'prenom',
        'sexe',
        'date_naissance',
        'lieu_naissance',
        'nationalite',
        'etat_civil',
        'telephone',
        'email',
        'adresse',
        'ville',
        'province',
        'pays',
        'date_embauche',
        'type_contrat',
        'date_debut_contrat',
        'date_fin_contrat',
        'salaire_base',
        'mode_paiement',
        'numero_cnss',
        'numero_nif',
        'lieu_travail',
        'temps_travail',
        'statut',
        'is_archived',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_naissance' => 'date',
        'date_embauche' => 'date',
        'date_debut_contrat' => 'date',
        'date_fin_contrat' => 'date',
        'salaire_base' => 'decimal:2',
        'is_archived' => 'boolean',
    ];

    protected $appends = ['nom_complet'];

    public function getNomCompletAttribute(): string
    {
        return trim(($this->nom ?? '') . ' ' . ($this->prenom ?? ''));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function departement(): BelongsTo
    {
        return $this->belongsTo(Departement::class);
    }

    public function poste(): BelongsTo
    {
        return $this->belongsTo(Poste::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function superieur(): BelongsTo
    {
        return $this->belongsTo(self::class, 'superieur_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modificateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeDocument::class);
    }

    public function formations(): HasMany
    {
        return $this->hasMany(Formation::class);
    }
}
