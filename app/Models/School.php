<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
    use HasFactory, \App\Traits\HasDataScope, \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [
        'name',
        'code_ecole',
        'type_ecole',
        'niveau',
        'statut',
        'latitude',
        'longitude',
        'colline_id', 
        'zone_id', 
        'commune_id', 
        'province_id', 
        'ministere_id', 
        'pays_id',
        'created_by',
        'validated_by',
        'validated_at'
    ];

    protected $casts = [
        'validated_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    public function colline()
    {
        return $this->belongsTo(Colline::class);
    }

    // Parent relationships
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }
    public function province()
    {
        return $this->belongsTo(Province::class);
    }
    public function ministere()
    {
        return $this->belongsTo(Ministere::class);
    }
    public function pays()
    {
        return $this->belongsTo(Pays::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
