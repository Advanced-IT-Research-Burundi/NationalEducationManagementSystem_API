<?php

namespace App\Models;

use App\Traits\HasAcademicYearScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasAcademicYearScope, HasFactory;

    protected static function academicYearColumn(): ?string
    {
        return null;
    }

    protected static function academicYearRelation(): ?string
    {
        return 'evaluation';
    }

    protected $fillable = [
        'evaluation_id',
        'eleve_id',
        'note',
    ];

    protected $casts = [
        'note' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }
}
