<?php

namespace Database\Factories;

use App\Enums\CampagneStatut;
use App\Enums\CampagneType;
use App\Models\AnneeScolaire;
use App\Models\CampagneInscription;
use App\Models\Ecole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampagneInscription>
 */
class CampagneInscriptionFactory extends Factory
{
    protected $model = CampagneInscription::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dateOuverture = fake()->dateTimeBetween('now', '+1 month');
        $dateCloture = fake()->dateTimeBetween($dateOuverture, '+3 months');

        return [
            'annee_scolaire_id' => AnneeScolaire::factory(),
            'ecole_id' => Ecole::factory(),
            'type' => fake()->randomElement(CampagneType::cases()),
            'date_ouverture' => $dateOuverture,
            'date_cloture' => $dateCloture,
            'statut' => CampagneStatut::Planifiee,
            'quota_max' => fake()->optional(0.7)->numberBetween(50, 500),
            'description' => fake()->optional(0.5)->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Indicate the campagne is for new students.
     */
    public function nouvelle(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CampagneType::Nouvelle,
        ]);
    }

    /**
     * Indicate the campagne is for re-enrollment.
     */
    public function reinscription(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => CampagneType::Reinscription,
        ]);
    }

    /**
     * Indicate the campagne is planned.
     */
    public function planifiee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => CampagneStatut::Planifiee,
        ]);
    }

    /**
     * Indicate the campagne is open.
     */
    public function ouverte(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => CampagneStatut::Ouverte,
            'date_ouverture' => now()->subDays(5),
            'date_cloture' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate the campagne is closed.
     */
    public function cloturee(): static
    {
        return $this->state(fn (array $attributes) => [
            'statut' => CampagneStatut::Cloturee,
            'date_ouverture' => now()->subMonths(2),
            'date_cloture' => now()->subDays(5),
        ]);
    }

    /**
     * Set a specific école.
     */
    public function forEcole(Ecole $ecole): static
    {
        return $this->state(fn (array $attributes) => [
            'ecole_id' => $ecole->id,
        ]);
    }

    /**
     * Set a specific année scolaire.
     */
    public function forAnneeScolaire(AnneeScolaire $anneeScolaire): static
    {
        return $this->state(fn (array $attributes) => [
            'annee_scolaire_id' => $anneeScolaire->id,
        ]);
    }

    /**
     * Set a specific quota.
     */
    public function withQuota(int $quota): static
    {
        return $this->state(fn (array $attributes) => [
            'quota_max' => $quota,
        ]);
    }

    /**
     * Set no quota limit.
     */
    public function withoutQuota(): static
    {
        return $this->state(fn (array $attributes) => [
            'quota_max' => null,
        ]);
    }
}
