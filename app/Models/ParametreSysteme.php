<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParametreSysteme extends Model
{
    use HasFactory;

    protected $table = 'parametres_systeme';

    protected $guarded = [];

    public static function get(string $cle, $default = null)
    {
        $parametre = static::where('cle', $cle)->first();

        if (! $parametre) {
            return $default;
        }

        return match ($parametre->type) {
            'boolean' => (bool) $parametre->valeur,
            'integer' => (int) $parametre->valeur,
            'json' => json_decode($parametre->valeur, true),
            default => $parametre->valeur,
        };
    }

    public static function set(string $cle, $valeur, string $type = 'string', ?string $description = null): void
    {
        $valeurFormatted = $type === 'json' ? json_encode($valeur) : $valeur;

        static::updateOrCreate(
            ['cle' => $cle],
            [
                'valeur' => $valeurFormatted,
                'type' => $type,
                'description' => $description,
            ]
        );
    }
}
