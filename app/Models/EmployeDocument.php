<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'employe_id',
        'type_document',
        'nom_original',
        'fichier_path',
        'mime_type',
        'taille',
        'is_primary',
        'created_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'taille' => 'integer',
    ];

    public function employe(): BelongsTo
    {
        return $this->belongsTo(Employe::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
