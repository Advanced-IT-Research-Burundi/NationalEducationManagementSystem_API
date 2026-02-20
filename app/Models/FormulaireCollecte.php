<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FormulaireCollecte extends Model
{
    use HasFactory, LogsActivity;

    // Field types for champs JSON
    const TYPE_TEXT = 'text';
    const TYPE_NUMBER = 'number';
    const TYPE_DATE = 'date';
    const TYPE_SELECT = 'select';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_TEXTAREA = 'textarea';

    protected $table = 'formulaires_collecte';

    protected $fillable = [
        'campagne_id',
        'titre',
        'description',
        'champs',
        'ordre',
        'created_by',
    ];

    protected $casts = [
        'champs' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->useLogName('formulaires_collecte')
            ->dontSubmitEmptyLogs();
    }

    public function campagne(): BelongsTo
    {
        return $this->belongsTo(CampagneCollecte::class, 'campagne_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reponses(): HasMany
    {
        return $this->hasMany(ReponseCollecte::class, 'formulaire_id');
    }
}
