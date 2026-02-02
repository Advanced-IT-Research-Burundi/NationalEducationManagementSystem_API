<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colline extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'zone_id',
        'commune_id',
        'province_id',
        'ministere_id',
        'pays_id'
    ];

    // Relationships
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

    public function schools()
    {
        return $this->hasMany(School::class);
    }

    // Query Scopes
    public function scopeSearch($query, $search)
    {
        return $query->where('name', 'LIKE', "%{$search}%");
    }

    public function scopeByZone($query, $zoneId)
    {
        return $query->where('zone_id', $zoneId);
    }

    public function scopeByCommune($query, $communeId)
    {
        return $query->where('commune_id', $communeId);
    }

    public function scopeByProvince($query, $provinceId)
    {
        return $query->where('province_id', $provinceId);
    }

    public function scopeByMinistere($query, $ministereId)
    {
        return $query->where('ministere_id', $ministereId);
    }
}
