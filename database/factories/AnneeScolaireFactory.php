<?php

namespace Database\Factories;

use App\Models\AnneeScolaire;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnneeScolaire>
 */
class AnneeScolaireFactory extends Factory
{
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2020, 2025);

        return [
            'code' => $startYear.'-'.($startYear + 1),
            'libelle' => 'Année scolaire '.$startYear.'-'.($startYear + 1),
            'date_debut' => "{$startYear}-09-01",
            'date_fin' => ($startYear + 1).'-07-31',
            'est_active' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'est_active' => true,
        ]);
    }
}
