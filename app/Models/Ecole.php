<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ecole extends Model
{
    /** @use HasFactory<\Database\Factories\EcoleFactory> */
    use HasFactory ,SoftDeletes;

    protected $guarded = [];
}
