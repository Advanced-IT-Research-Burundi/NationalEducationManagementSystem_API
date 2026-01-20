<?php

namespace Database\Seeders;

use App\Models\Colline;
use App\Models\Commune;
use App\Models\Pays;
use App\Models\Province;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BurundiAdministrativeDivisionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Get Burundi ID
        $burundi = Pays::where('name', 'Burundi')->first();
        if (!$burundi) {
            $this->command->error('Burundi not found in pays table. Please run PaysSeeder first.');
            return;
        }

        // 2. Load SQL file
        $sqlPath = database_path('data/les provinces du burundi.sql');
        if (!File::exists($sqlPath)) {
            $this->command->error("SQL file not found at: $sqlPath");
            return;
        }

        // 3. Drop temporary tables if exist to avoid conflicts
        Schema::dropIfExists('new_collines');
        Schema::dropIfExists('new_zones');
        Schema::dropIfExists('new_communes');
        Schema::dropIfExists('new_provinces');

        $this->command->info('Importing SQL file into temporary tables...');
        DB::unprepared(File::get($sqlPath));

        // 4. Migrate Data
        $this->command->info('Migrating provinces...');
        $newProvinces = DB::table('new_provinces')->get();
        // Map old_id => new_id
        $provinceMap = [];

        foreach ($newProvinces as $newProv) {
            $province = Province::create([
                'name' => $newProv->name,
                'pays_id' => $burundi->id,
            ]);
            $provinceMap[$newProv->id] = $province->id;
        }

        $this->command->info('Migrating communes...');
        $newCommunes = DB::table('new_communes')->get();
        $communeMap = [];
        foreach ($newCommunes as $newCom) {
            if (!isset($provinceMap[$newCom->new_provinces_id])) {
                $this->command->warn("Skipping commune {$newCom->name} (ID: {$newCom->id}) because new_provinces_id {$newCom->new_provinces_id} not found.");
                continue;
            }
            $commune = Commune::create([
                'name' => $newCom->name,
                'province_id' => $provinceMap[$newCom->new_provinces_id],
                'pays_id' => $burundi->id, 
            ]);
            $communeMap[$newCom->id] = $commune->id;
        }

        $this->command->info('Migrating zones...');
        $newZones = DB::table('new_zones')->get();
        $zoneMap = [];
        foreach ($newZones as $newZone) {
            if (!isset($communeMap[$newZone->new_commune_id])) {
                 $this->command->warn("Skipping zone {$newZone->name} (ID: {$newZone->id}) because new_commune_id {$newZone->new_commune_id} not found.");
                continue;
            }
            // Get province from commune
            $commune = Commune::find($communeMap[$newZone->new_commune_id]);
            
            $zone = Zone::create([
                'name' => $newZone->name,
                'commune_id' => $commune->id,
                'province_id' => $commune->province_id,
                'pays_id' => $burundi->id,
            ]);
            $zoneMap[$newZone->id] = $zone->id;
        }

        $this->command->info('Migrating collines...');
        // Chunking collines as there might be many
        DB::table('new_collines')->orderBy('id')->chunk(500, function ($newCollines) use ($zoneMap, $burundi) {
            foreach ($newCollines as $newColline) {
                 if (!isset($zoneMap[$newColline->new_zone_id])) {
                    // This might be noisy, maybe log only once or ignore silently if expected
                    continue;
                }
                $zone = Zone::find($zoneMap[$newColline->new_zone_id]);
                
                Colline::create([
                    'name' => $newColline->name,
                    'zone_id' => $zone->id,
                    'commune_id' => $zone->commune_id,
                    'province_id' => $zone->province_id,
                    'pays_id' => $burundi->id,
                ]);
            }
        });

        // 5. Cleanup
        $this->command->info('Cleaning up temporary tables...');
        Schema::dropIfExists('new_collines');
        Schema::dropIfExists('new_zones');
        Schema::dropIfExists('new_communes');
        Schema::dropIfExists('new_provinces');

        $this->command->info('Seeding completed successfully!');
    }
}
