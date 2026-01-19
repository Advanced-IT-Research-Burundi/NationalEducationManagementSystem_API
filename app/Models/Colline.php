<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Colline extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'zone_id', 'commune_id', 'province_id', 'ministere_id', 'pays_id'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    // Parent relationships
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
}
