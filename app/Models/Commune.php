<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Commune extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'province_id', 'ministere_id', 'pays_id'];

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

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }
}
