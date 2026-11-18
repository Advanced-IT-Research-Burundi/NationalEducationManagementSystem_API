<?php

use App\Models\Employe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create(['is_super_admin' => true, 'statut' => 'actif']), 'sanctum');

    $this->departementId = DB::table('departements')->insertGetId([
        'code' => 'DEP-A',
        'nom' => 'Pédagogie',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->employe = Employe::create([
        'matricule' => 'EMP-TEST-0001',
        'nom' => 'Bizimana',
        'prenom' => 'Alice',
        'sexe' => 'M',
        'telephone' => '63739880',
        'departement_id' => $this->departementId,
        'statut' => 'ACTIF',
        'temps_travail' => 'PLEIN_TEMPS',
    ]);
});

it('updates the modified fields of an employe', function () {
    $this->putJson("/api/hr/employes/{$this->employe->id}", [
        'nom' => 'Bizimana',
        'prenom' => 'Alice',
        'sexe' => 'F',
        'telephone' => '79000000',
    ])->assertSuccessful();

    expect($this->employe->fresh())
        ->sexe->toBe('F')
        ->telephone->toBe('79000000');
});

it('clears a field sent as null', function () {
    $this->putJson("/api/hr/employes/{$this->employe->id}", [
        'nom' => 'Bizimana',
        'prenom' => 'Alice',
        'telephone' => null,
        'departement_id' => null,
    ])->assertSuccessful();

    expect($this->employe->fresh())
        ->telephone->toBeNull()
        ->departement_id->toBeNull();
});

it('keeps required columns when they are sent as null', function () {
    $this->putJson("/api/hr/employes/{$this->employe->id}", [
        'nom' => 'Bizimana',
        'prenom' => 'Alice',
        'statut' => null,
        'temps_travail' => null,
        'matricule' => null,
    ])->assertSuccessful();

    expect($this->employe->fresh())
        ->statut->toBe('ACTIF')
        ->temps_travail->toBe('PLEIN_TEMPS')
        ->matricule->toBe('EMP-TEST-0001');
});

it('deletes an employe and removes it from the list', function () {
    $this->deleteJson("/api/hr/employes/{$this->employe->id}")->assertSuccessful();

    $this->assertSoftDeleted('employes', ['id' => $this->employe->id]);

    $this->getJson('/api/hr/employes')
        ->assertSuccessful()
        ->assertJsonMissing(['id' => $this->employe->id, 'matricule' => 'EMP-TEST-0001']);
});

it('stores an uploaded photo and exposes its url', function () {
    Storage::fake('public');

    $this->post("/api/hr/employes/{$this->employe->id}", [
        '_method' => 'PUT',
        'nom' => 'Bizimana',
        'prenom' => 'Alice',
        'photo_path' => UploadedFile::fake()->image('alice.png'),
    ], ['Accept' => 'application/json'])->assertSuccessful();

    $employe = $this->employe->fresh();

    Storage::disk('public')->assertExists($employe->photo_path);
    expect($employe->photo_path)->toStartWith('hr/employees/photos/')
        ->and($employe->photo_url)->toEndWith('/storage/'.$employe->photo_path);
});

it('returns no photo url when the photo file is missing', function () {
    Storage::fake('public');
    $this->employe->update(['photo_path' => 'hr/employees/photos/absente.png']);

    expect($this->employe->fresh()->photo_url)->toBeNull();
});
