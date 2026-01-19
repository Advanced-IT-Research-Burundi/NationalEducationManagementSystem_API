<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pays extends Model
{
    use HasFactory;

    protected $table = 'pays'; // because 'pays' plural is same or singular? Schema creates 'pays'. Laravel generic pluralizer might get confused with 'pays', assuming singular is 'pay'.
    protected $fillable = ['name', 'code'];

    public function ministeres()
    {
        return $this->hasMany(Ministere::class);
    }
}
