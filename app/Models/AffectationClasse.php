<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffectationClasse extends Model
{
    use HasFactory;

    protected $table = 'affectations_classe';

    protected $fillable = [
        'inscription_id',
        'classe_id',
        'date_affectation',
        'date_fin',
        'est_active',
        'numero_ordre',
        'motif_changement',
        'affecte_par',
    ];

    protected $casts = [
        'date_affectation' => 'date',
        'date_fin' => 'date',
        'est_active' => 'boolean',
    ];

    public function inscription(): BelongsTo
    {
        return $this->belongsTo(Inscription::class);
    }

    public function classe(): BelongsTo
    {
        return $this->belongsTo(Classe::class);
    }

    public function affectePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'affecte_par');
    }
}
