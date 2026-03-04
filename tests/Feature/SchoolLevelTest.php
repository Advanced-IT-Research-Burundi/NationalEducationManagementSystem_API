<?php

namespace Tests\Feature;

use App\Models\Colline;
use App\Models\Niveau;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SchoolLevelTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        // Mocking permissions might be needed depending on the setup
        // For now let's assume the user can create schools or use sudo
    }

    public function test_can_create_school_with_levels()
    {
        $colline = Colline::factory()->create();
        $levels = Niveau::factory()->count(2)->create();

        $response = $this->actingAs($this->user)->postJson('/api/schools', [
            'name' => 'Test School',
            'type_ecole' => 'PUBLIQUE',
            'niveau' => 'FONDAMENTAL',
            'colline_id' => $colline->id,
            'niveau_scolaire_ids' => $levels->pluck('id')->toArray(),
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('niveau_school', [
            'school_id' => $response->json('school.id'),
            'niveau_scolaire_id' => $levels[0]->id,
        ]);
        $this->assertDatabaseHas('niveau_school', [
            'school_id' => $response->json('school.id'),
            'niveau_scolaire_id' => $levels[1]->id,
        ]);
    }

    public function test_can_update_school_levels()
    {
        $school = School::factory()->create();
        $levels = Niveau::factory()->count(2)->create();

        $response = $this->actingAs($this->user)->putJson("/api/schools/{$school->id}", [
            'name' => 'Updated School',
            'niveau_scolaire_ids' => $levels->pluck('id')->toArray(),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('niveau_school', [
            'school_id' => $school->id,
            'niveau_scolaire_id' => $levels[0]->id,
        ]);
        $this->assertDatabaseHas('niveau_school', [
            'school_id' => $school->id,
            'niveau_scolaire_id' => $levels[1]->id,
        ]);
    }
}
