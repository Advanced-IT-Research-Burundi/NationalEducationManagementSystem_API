<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trimestre extends Model
{
    use HasFactory;

    protected $table = 'trimestres';

    protected $fillable = [
        'annee_scolaire_id',
        'nom',
        'date_debut',
        'date_fin',
        'actif',
        'verrouille',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'actif' => 'boolean',
        'verrouille' => 'boolean',
    ];

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'trimestre_id');
    }

    public function noteConduites(): HasMany
    {
        return $this->hasMany(NoteConduite::class, 'trimestre_id');
    }

    public function sanctions(): HasMany
    {
        return $this->hasMany(SanctionEleve::class, 'trimestre_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('date_debut')->orderBy('nom');
    }

    public function scopeForDate($query, $date)
    {
        $target = \Illuminate\Support\Carbon::parse($date)->toDateString();

        return $query
            ->whereNotNull('date_debut')
            ->whereNotNull('date_fin')
            ->whereDate('date_debut', '<=', $target)
            ->whereDate('date_fin', '>=', $target);
    }
}
