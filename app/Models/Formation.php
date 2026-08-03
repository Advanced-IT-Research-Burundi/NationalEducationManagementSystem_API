<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Formation extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'employe_id',
        'intitule',
        'organisme',
        'type',
        'domaine',
        'date_debut',
        'date_fin',
        'duree',
        'lieu',
        'cout',
        'description',
        'resultat',
        'est_certifie',
        'numero_certificat',
        'date_obtention',
        'date_expiration',
        'piece_jointe',
        'presence',
        'taux_presence',
        'commentaire',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'date_obtention' => 'date',
        'date_expiration' => 'date',
        'est_certifie' => 'boolean',
        'cout' => 'decimal:2',
        'taux_presence' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
