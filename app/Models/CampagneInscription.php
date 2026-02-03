<?php

namespace App\Models;

use App\Traits\HasDataScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampagneInscription extends Model
{
    use HasFactory, HasDataScope;

    protected $table = 'campagnes_inscription';

    protected $fillable = [
        'annee_scolaire_id',
        'ecole_id',
        'type',
        'date_ouverture',
        'date_cloture',
        'statut',
        'quota_max',
        'created_by',
    ];

    protected $casts = [
        'date_ouverture' => 'date',
        'date_cloture' => 'date',
        'quota_max' => 'integer',
    ];

    // Scopes for HasDataScope
    protected static function getScopeColumn(): ?string
    {
        return 'ecole_id';
    }

    protected static function getScopeRelation(): ?string
    {
        return null; // direct column
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function ecole(): BelongsTo
    {
        return $this->belongsTo(School::class, 'ecole_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'campagne_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function estOuverte(): bool
    {
        return $this->statut === 'ouverte'
            && now()->between($this->date_ouverture->startOfDay(), $this->date_cloture->endOfDay());
    }
}
