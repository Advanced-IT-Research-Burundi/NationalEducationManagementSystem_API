<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'commune_id', 'province_id', 'ministere_id', 'pays_id'];

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }
    
    // Parent relationships for convenience
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

    public function collines()
    {
        return $this->hasMany(Colline::class);
    }
}
