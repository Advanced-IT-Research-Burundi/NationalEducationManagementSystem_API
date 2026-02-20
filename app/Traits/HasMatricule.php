<?php

namespace App\Traits;

use App\Services\MatriculeService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

trait HasMatricule
{
    /**
     * Boot the trait and register the creating event.
     */
    protected static function bootHasMatricule(): void
    {
        static::creating(function (Model $model) {
            $column = $model->getMatriculeColumn();
            
            // Si la colonne n'existe pas dans le modèle, on ne fait rien
            if (!$column) {
                return;
            }

            $service = app(MatriculeService::class);
            $model->{$column} = $service->generate($model);
        });

        static::updating(function (Model $model) {
            $column = $model->getMatriculeColumn();
            
            // Empêche la modification manuelle du matricule
            if ($column && $model->isDirty($column) && $model->exists) {
                $model->{$column} = $model->getOriginal($column);
            }
        });
    }

    /**
     * Détermine la colonne utilisée pour stocker le matricule.
     */
    public function getMatriculeColumn(): ?string
    {
        // On définit les colonnes possibles selon les tables existantes
        $table = $this->getTable();
        
        return match ($table) {
            'eleves', 'enseignants' => 'matricule',
            'schools' => 'code_ecole',
            'classes', 'examens' => 'code',
            'salles' => 'numero',
            'batiments' => 'nom',
            default => null,
        };
    }
}
