<?php

namespace Tests\Feature;

use App\Models\Colline;
use App\Models\Commune;
use App\Models\Ministere;
use App\Models\Pays;
use App\Models\Permission;
use App\Models\Province;
use App\Models\School;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SchoolGeoImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_geo_image_path_for_school_store(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();

        Permission::firstOrCreate([
            'name' => 'create_school',
            'guard_name' => 'api',
        ]);
        $user->givePermissionTo('create_school');

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

        $colline = Colline::create([
            'name' => 'Colline de test',
            'zone_id' => $zone->id,
            'commune_id' => $commune->id,
            'province_id' => $province->id,
            'ministere_id' => $ministere->id,
            'pays_id' => $pays->id,
        ]);

        $file = UploadedFile::fake()->image('geo.jpg', 1200, 800);

        Sanctum::actingAs($user);

        $response = $this->post('/api/schools', [
            'name' => 'Test School',
            'type_ecole' => 'PUBLIQUE',
            'niveau' => 'FONDAMENTAL',
            'colline_id' => $colline->id,
            'latitude' => '-3.38',
            'longitude' => '29.36',
            'geo_image' => $file,
        ]);

        $response->assertStatus(201);

        $school = School::withoutGlobalScopes()->firstOrFail();

        $geoImagePath = $school->geo_image_path;
        $this->assertNotEmpty($geoImagePath);

        Storage::disk('public')->assertExists($geoImagePath);

        $responseJson = $response->json();
        $expectedFromApi = $responseJson['geo_image_path']
            ?? ($responseJson['data']['geo_image_path'] ?? null);

        $this->assertSame($geoImagePath, $expectedFromApi);
    }
}
