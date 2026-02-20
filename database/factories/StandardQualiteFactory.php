<?php

namespace Database\Factories;

use App\Models\StandardQualite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StandardQualite>
 */
class StandardQualiteFactory extends Factory
{
    protected $model = StandardQualite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('SQ-###-??'),
            'libelle' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'criteres' => [fake()->word(), fake()->word(), fake()->word()],
            'poids' => fake()->numberBetween(1, 10),
        ];
    }
}
