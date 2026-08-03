<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fonction extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'code',
        'nom',
        'description',
        'grade',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function personnels(): HasMany
    {
        return $this->hasMany(PersonnelAdministratif::class);
    }
}
