<?php

use App\Models\Employe;
use App\Models\Role;
use App\Models\User;
use App\Services\HR\EmployeSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach ([Role::ENSEIGNANT, Role::DIRECTEUR_ECOLE, Role::DIRECTEUR_PROVINCIAL, Role::PARENT] as $name) {
        Role::create(['name' => $name, 'guard_name' => 'api']);
    }
});

it('creates the employe with the poste and departement of the role', function () {
    $user = User::factory()->create(['nom' => 'Ndayishimiye', 'prenom' => 'Jean', 'email' => 'jean@example.bi']);

    $user->assignRole(Role::ENSEIGNANT);

    $employe = Employe::with('poste', 'service', 'departement')->where('user_id', $user->id)->first();

    expect($employe)->not->toBeNull()
        ->and($employe->nom)->toBe('Ndayishimiye')
        ->and($employe->prenom)->toBe('Jean')
        ->and($employe->email)->toBe('jean@example.bi')
        ->and($employe->poste->nom)->toBe(Role::ENSEIGNANT)
        ->and($employe->service->nom)->toBe('Personnel des établissements')
        ->and($employe->departement->nom)->toBe('Établissements')
        ->and($employe->matricule)->toStartWith('EMP-');
});

it('moves the employe to the new poste when the role changes', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::ENSEIGNANT);

    $user->syncRoles([Role::DIRECTEUR_PROVINCIAL]);

    $employe = Employe::with('poste', 'departement')->where('user_id', $user->id)->sole();

    expect($employe->poste->nom)->toBe(Role::DIRECTEUR_PROVINCIAL)
        ->and($employe->departement->nom)->toBe('Direction Provinciale');
});

it('gives the highest role poste to a user with several roles', function () {
    $user = User::factory()->create();

    $user->assignRole([Role::ENSEIGNANT, Role::DIRECTEUR_ECOLE]);

    expect(Employe::where('user_id', $user->id)->sole()->poste->nom)->toBe(Role::DIRECTEUR_ECOLE);
});

it('does not create an employe for a parent', function () {
    $user = User::factory()->create();

    $user->assignRole(Role::PARENT);

    expect(Employe::where('user_id', $user->id)->exists())->toBeFalse();
});

it('updates the employe identity when the user is renamed', function () {
    $user = User::factory()->create(['nom' => 'Ancien', 'prenom' => 'Nom']);
    $user->assignRole(Role::ENSEIGNANT);

    $user->update(['nom' => 'Nouveau']);

    expect(Employe::where('user_id', $user->id)->sole()->nom)->toBe('Nouveau');
});

it('links an existing employe with the same email instead of duplicating it', function () {
    $existing = Employe::create([
        'matricule' => 'EMP-EXIST-0001',
        'nom' => 'Kaneza',
        'prenom' => 'Aline',
        'email' => 'aline@example.bi',
        'statut' => 'ACTIF',
        'temps_travail' => 'PLEIN_TEMPS',
    ]);
    $user = User::factory()->create(['email' => 'aline@example.bi']);

    $user->assignRole(Role::ENSEIGNANT);

    expect(Employe::count())->toBe(1)
        ->and($existing->fresh()->user_id)->toBe($user->id)
        ->and($existing->fresh()->matricule)->toBe('EMP-EXIST-0001');
});

it('backfills existing users with the artisan command', function () {
    config(['permission.events_enabled' => false]);
    $users = User::factory()->count(2)->create();
    $users->each->assignRole(Role::ENSEIGNANT);
    config(['permission.events_enabled' => true]);

    expect(Employe::count())->toBe(0);

    $this->artisan('hr:sync-employes')->assertSuccessful();

    expect(Employe::whereIn('user_id', $users->pluck('id'))->count())->toBe(2);
});

it('uses the matricule of the enseignant when it exists', function () {
    $user = User::factory()->create();
    $user->assignRole(Role::ENSEIGNANT);
    expect(Employe::where('user_id', $user->id)->sole()->matricule)->toStartWith('EMP-');

    DB::table('enseignants')->insert([
        'user_id' => $user->id,
        'matricule' => '13654678',
        'statut' => 'ACTIF',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    app(EmployeSyncService::class)->syncUser($user);

    expect(Employe::where('user_id', $user->id)->sole()->matricule)->toBe('13654678');
});
