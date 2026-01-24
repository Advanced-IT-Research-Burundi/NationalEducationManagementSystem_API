<?php

namespace Database\Seeders;

use App\Models\Commune;
use App\Models\Pays;
use App\Models\Province;
use App\Models\School;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUsersSeeder extends Seeder
{
    /**
     * Seed test users for each administrative level.
     */
    public function run(): void
    {
        // Get existing entities for relationships
        $pays = Pays::first();
        $province = Province::first();
        $commune = Commune::first();
        $zone = Zone::first();
        $school = School::first();

        // 1. Admin National (niveau PAYS)
        $adminNational = User::firstOrCreate(
            ['email' => 'admin@nems.bi'],
            [
                'name' => 'Admin National',
                'password' => Hash::make('password123'),
                'statut' => 'actif',
                'admin_level' => 'PAYS',
                'admin_entity_id' => $pays?->id,
                'pays_id' => $pays?->id,
            ]
        );
        $adminNational->assignRole('Admin National');
        $this->command->info("Admin National: admin@nems.bi / password123");

        // 2. Directeur Provincial
        if ($province) {
            $directeurProvincial = User::firstOrCreate(
                ['email' => 'provincial@nems.bi'],
                [
                    'name' => 'Directeur Provincial',
                    'password' => Hash::make('password123'),
                    'statut' => 'actif',
                    'admin_level' => 'PROVINCE',
                    'admin_entity_id' => $province->id,
                    'pays_id' => $pays?->id,
                    'province_id' => $province->id,
                ]
            );
            $directeurProvincial->assignRole('Admin National');
            $this->command->info("Directeur Provincial: provincial@nems.bi / password123 (Province: {$province->name})");
        }

        // 3. Agent Communal
        if ($commune) {
            $agentCommunal = User::firstOrCreate(
                ['email' => 'communal@nems.bi'],
                [
                    'name' => 'Agent Communal',
                    'password' => Hash::make('password123'),
                    'statut' => 'actif',
                    'admin_level' => 'COMMUNE',
                    'admin_entity_id' => $commune->id,
                    'pays_id' => $pays?->id,
                    'province_id' => $commune->province_id,
                    'commune_id' => $commune->id,
                ]
            );
            $agentCommunal->assignRole('Admin National');
            $this->command->info("Agent Communal: communal@nems.bi / password123 (Commune: {$commune->name})");
        }

        // 4. Superviseur Zone
        if ($zone) {
            $superviseurZone = User::firstOrCreate(
                ['email' => 'zone@nems.bi'],
                [
                    'name' => 'Superviseur Zone',
                    'password' => Hash::make('password123'),
                    'statut' => 'actif',
                    'admin_level' => 'ZONE',
                    'admin_entity_id' => $zone->id,
                    'pays_id' => $pays?->id,
                    'province_id' => $zone->province_id,
                    'commune_id' => $zone->commune_id,
                    'zone_id' => $zone->id,
                ]
            );
            $superviseurZone->assignRole('Admin National');
            $this->command->info("Superviseur Zone: zone@nems.bi / password123 (Zone: {$zone->name})");
        }

        // 5. Directeur d'Ecole (ECOLE level)
        if ($school) {
            $directeurEcole = User::firstOrCreate(
                ['email' => 'ecole@nems.bi'],
                [
                    'name' => 'Directeur Ecole',
                    'password' => Hash::make('password123'),
                    'statut' => 'actif',
                    'admin_level' => 'ECOLE',
                    'admin_entity_id' => $school->id,
                    'pays_id' => $school->pays_id,
                    'province_id' => $school->province_id,
                    'commune_id' => $school->commune_id,
                    'zone_id' => $school->zone_id,
                    'school_id' => $school->id,
                ]
            );
            $directeurEcole->assignRole('Admin National');
            $this->command->info("Directeur Ecole: ecole@nems.bi / password123 (Ecole: {$school->name})");
        } else {
            $this->command->warn("Aucune ecole trouvee. L'utilisateur de niveau ECOLE n'a pas ete cree.");
        }

        $this->command->newLine();
        $this->command->info("Tous les utilisateurs utilisent le mot de passe: password123");
    }
}
