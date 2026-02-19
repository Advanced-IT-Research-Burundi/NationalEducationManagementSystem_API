<?php

use App\Models\StandardQualite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user, 'sanctum');
});

it('can list all standards de qualité', function () {
    StandardQualite::factory()->count(3)->create();

    $response = $this->getJson('/api/pedagogy/standards-qualite');

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data');
});

it('can create a standard de qualité', function () {
    $payload = [
        'code' => 'SQ-001',
        'libelle' => 'Standard Test',
        'description' => 'Description du standard',
        'criteres' => ['critere1', 'critere2'],
        'poids' => 5,
    ];

    $response = $this->postJson('/api/pedagogy/standards-qualite', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.code', 'SQ-001')
        ->assertJsonPath('data.libelle', 'Standard Test');

    $this->assertDatabaseHas('standards_qualite', [
        'code' => 'SQ-001',
        'libelle' => 'Standard Test',
    ]);
});

it('can show a single standard de qualité', function () {
    $standard = StandardQualite::factory()->create();

    $response = $this->getJson("/api/pedagogy/standards-qualite/{$standard->id}");

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $standard->id)
        ->assertJsonPath('data.code', $standard->code);
});

it('can update a standard de qualité', function () {
    $standard = StandardQualite::factory()->create();

    $response = $this->putJson("/api/pedagogy/standards-qualite/{$standard->id}", [
        'libelle' => 'Libellé modifié',
        'poids' => 8,
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.libelle', 'Libellé modifié')
        ->assertJsonPath('data.poids', 8);

    $this->assertDatabaseHas('standards_qualite', [
        'id' => $standard->id,
        'libelle' => 'Libellé modifié',
        'poids' => 8,
    ]);
});

it('can delete a standard de qualité', function () {
    $standard = StandardQualite::factory()->create();

    $response = $this->deleteJson("/api/pedagogy/standards-qualite/{$standard->id}");

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Standard de qualité supprimé avec succès');

    $this->assertDatabaseMissing('standards_qualite', [
        'id' => $standard->id,
    ]);
});

it('can retrieve criteria for a standard de qualité', function () {
    $standard = StandardQualite::factory()->create([
        'criteres' => ['critere_a', 'critere_b'],
    ]);

    $response = $this->getJson("/api/pedagogy/standards-qualite/{$standard->id}/criteria");

    $response->assertSuccessful()
        ->assertJsonPath('data', ['critere_a', 'critere_b']);
});

it('returns validation errors when creating with missing required fields', function () {
    $response = $this->postJson('/api/pedagogy/standards-qualite', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'libelle']);
});

it('returns validation error for duplicate code', function () {
    StandardQualite::factory()->create(['code' => 'SQ-UNIQUE']);

    $response = $this->postJson('/api/pedagogy/standards-qualite', [
        'code' => 'SQ-UNIQUE',
        'libelle' => 'Another Standard',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['code']);
});

it('returns 401 for unauthenticated requests', function () {
    $this->app['auth']->forgetGuards();

    $response = $this->getJson('/api/pedagogy/standards-qualite');

    $response->assertUnauthorized();
});
