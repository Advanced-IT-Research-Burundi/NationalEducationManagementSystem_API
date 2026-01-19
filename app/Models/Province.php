<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Province extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'ministere_id', 'pays_id'];

    public function ministere()
    {
        return $this->belongsTo(Ministere::class);
    }

    public function pays()
    {
        return $this->belongsTo(Pays::class);
    }

    public function communes()
    {
        return $this->hasMany(Commune::class);
    }
}
