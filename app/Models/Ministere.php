<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ministere extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'pays_id'];

    public function pays()
    {
        return $this->belongsTo(Pays::class);
    }

    public function provinces()
    {
        return $this->hasMany(Province::class);
    }
}
