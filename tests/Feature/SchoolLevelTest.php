<?php

namespace Tests\Feature;

use App\Models\Colline;
use App\Models\Commune;
use App\Models\Ministere;
use App\Models\Niveau;
use App\Models\Pays;
use App\Models\Permission;
use App\Models\Province;
use App\Models\School;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolLevelTest extends TestCase
{
    use RefreshDatabase;

    private function seedGeoAndColline(): Colline
    {
        $pays = Pays::create([
            'name' => 'Burundi',
            'code' => 'BI',
        ]);

        $ministere = Ministere::create([
            'name' => 'Ministère de test',
            'pays_id' => $pays->id,
        ]);

        $province = Province::create([
            'name' => 'Province de test',
            'ministere_id' => $ministere->id,
            'pays_id' => $pays->id,
        ]);

        $commune = Commune::create([
            'name' => 'Commune de test',
            'province_id' => $province->id,
            'ministere_id' => $ministere->id,
            'pays_id' => $pays->id,
        ]);

        $zone = Zone::create([
            'name' => 'Zone de test',
            'commune_id' => $commune->id,
            'province_id' => $province->id,
            'ministere_id' => $ministere->id,
            'pays_id' => $pays->id,
        ]);

        return Colline::create([
            'name' => 'Colline de test',
            'zone_id' => $zone->id,
            'commune_id' => $commune->id,
            'province_id' => $province->id,
            'ministere_id' => $ministere->id,
            'pays_id' => $pays->id,
        ]);
    }

    private function userWithSchoolPermissions(): User
    {
        $user = User::factory()->create([
            'admin_level' => 'PAYS',
            'admin_entity_id' => 1,
        ]);

        foreach (['create_school', 'update_school'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
            $user->givePermissionTo($name);
        }

        return $user;
    }

    public function test_can_create_school_with_levels(): void
    {
        $user = $this->userWithSchoolPermissions();
        Sanctum::actingAs($user);

        $colline = $this->seedGeoAndColline();

        $levelA = Niveau::create([
            'nom' => 'Niveau A',
            'code' => 'NV-A-'.uniqid(),
            'ordre' => 1,
            'actif' => true,
        ]);

        $levelB = Niveau::create([
            'nom' => 'Niveau B',
            'code' => 'NV-B-'.uniqid(),
            'ordre' => 2,
            'actif' => true,
        ]);

        $response = $this->postJson('/api/schools', [
            'name' => 'Test School',
            'type_ecole' => 'PUBLIQUE',
            'niveau' => 'FONDAMENTAL',
            'colline_id' => $colline->id,
            'niveau_scolaire_ids' => [$levelA->id, $levelB->id],
        ]);

        $response->assertStatus(201);

        $schoolId = $response->json('data.id');
        $this->assertNotNull($schoolId);

        $this->assertDatabaseHas('niveau_school', [
            'school_id' => $schoolId,
            'niveau_scolaire_id' => $levelA->id,
        ]);

        $this->assertDatabaseHas('niveau_school', [
            'school_id' => $schoolId,
            'niveau_scolaire_id' => $levelB->id,
        ]);
    }

    public function test_can_update_school_levels(): void
    {
        $user = $this->userWithSchoolPermissions();
        Sanctum::actingAs($user);

        $colline = $this->seedGeoAndColline();

        $school = School::withoutGlobalScopes()->create([
            'name' => 'Existing School',
            'type_ecole' => 'PUBLIQUE',
            'niveau' => 'FONDAMENTAL',
            'colline_id' => $colline->id,
            'zone_id' => $colline->zone_id,
            'commune_id' => $colline->commune_id,
            'province_id' => $colline->province_id,
            'ministere_id' => $colline->ministere_id,
            'pays_id' => $colline->pays_id,
            'statut' => School::STATUS_BROUILLON,
            'created_by' => $user->id,
        ]);

        $levelA = Niveau::create([
            'nom' => 'Niveau U1',
            'code' => 'NV-U1-'.uniqid(),
            'ordre' => 10,
            'actif' => true,
        ]);

        $levelB = Niveau::create([
            'nom' => 'Niveau U2',
            'code' => 'NV-U2-'.uniqid(),
            'ordre' => 11,
            'actif' => true,
        ]);

        $response = $this->putJson("/api/schools/{$school->id}", [
            'name' => 'Updated School',
            'niveau_scolaire_ids' => [$levelA->id, $levelB->id],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('niveau_school', [
            'school_id' => $school->id,
            'niveau_scolaire_id' => $levelA->id,
        ]);

        $this->assertDatabaseHas('niveau_school', [
            'school_id' => $school->id,
            'niveau_scolaire_id' => $levelB->id,
        ]);
    }
}
