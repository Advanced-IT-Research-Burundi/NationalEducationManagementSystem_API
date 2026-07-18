<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PersonnelAdministratifMouvement extends Model
{
    use HasFactory;

    protected $fillable = [
        'personnel_administratif_id',
        'ancien_service_id',
        'nouveau_service_id',
        'ancienne_fonction_id',
        'nouvelle_fonction_id',
        'type_mouvement',
        'date_mouvement',
        'motif',
        'created_by',
    ];

    protected $casts = [
        'date_mouvement' => 'date',
    ];

    public function personnelAdministratif(): BelongsTo
    {
        return $this->belongsTo(PersonnelAdministratif::class);
    }

    public function ancienService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'ancien_service_id');
    }

    public function nouveauService(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'nouveau_service_id');
    }

    public function ancienneFonction(): BelongsTo
    {
        return $this->belongsTo(Fonction::class, 'ancienne_fonction_id');
    }

    public function nouvelleFonction(): BelongsTo
    {
        return $this->belongsTo(Fonction::class, 'nouvelle_fonction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
