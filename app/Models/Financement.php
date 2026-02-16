<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Financement extends Model
{
    /** @use HasFactory<\Database\Factories\FinancementFactory> */
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date_decaissement' => 'date',
        ];
    }

    /**
     * Get the projet that owns the financement
     */
    public function projetPartenariat(): BelongsTo
    {
        return $this->belongsTo(ProjetPartenariat::class);
    }
}
