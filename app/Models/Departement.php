<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Departement extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'code',
        'nom',
        'description',
        'departement_parent_id',
        'responsable_id',
        'couleur',
        'statut',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'departement_parent_id');
    }

    public function enfants(): HasMany
    {
        return $this->hasMany(self::class, 'departement_parent_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function postes(): HasMany
    {
        return $this->hasMany(Poste::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function employes(): HasMany
    {
        return $this->hasMany(Employe::class);
    }
}
