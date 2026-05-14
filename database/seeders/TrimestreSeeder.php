<?php

namespace Database\Seeders;

use App\Models\AnneeScolaire;
use App\Models\ConfigurationAcademique;
use App\Models\Trimestre;
use Illuminate\Database\Seeder;

class TrimestreSeeder extends Seeder
{
    private const TRIMESTRES = ['1er Trimestre', '2e Trimestre', '3e Trimestre'];

    public function run(): void
    {
        $annees = AnneeScolaire::withoutGlobalScopes()->get();

        foreach ($annees as $annee) {
            foreach (self::TRIMESTRES as $nom) {
                Trimestre::updateOrCreate(
                    [
                        'annee_scolaire_id' => $annee->id,
                        'nom' => $nom,
                    ],
                    [
                        'date_debut' => null,
                        'date_fin' => null,
                        'actif' => false,
                        'verrouille' => false,
                    ]
                );
            }
        }

        $activeYear = $annees->firstWhere('est_active', true);
        ConfigurationAcademique::current()->forceFill([
            'current_annee_scolaire_id' => $activeYear?->id,
            'current_trimestre_id' => null,
        ])->save();
    }
}
