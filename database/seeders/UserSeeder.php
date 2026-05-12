<?php

namespace Database\Seeders;

use App\Models\Colline;
use App\Models\Commune;
use App\Models\Pays;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Migrating User Seeder ...');

        // Get Burundi
        $burundi = Pays::where('name', 'Burundi')->first();
        if (!$burundi) {
            $this->command->error('Burundi not found!');
            return;
        }

        // Get geographic location: Rohero zone, Mukaza colline
        // Rohero is in Bujumbura Mairie
        $commune = Commune::where('name', 'BUJUMBURA MAIRIE')->first();
        $zone = Zone::where('name', 'ROHERO')->first();
        $colline = Colline::where('name', 'MUKAZA')->first();

        if (!$commune || !$zone || !$colline) {
            $this->command->warn('Warning: Mukaza/Rohero location not found. Using default geographic IDs.');
            $commune = $commune ?? Commune::first();
            $zone = $zone ?? Zone::first();
            $colline = $colline ?? Colline::first();
        }

        // User definitions with roles
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@nems.bi',
                'password' => Hash::make('Advanced2026'),
                'role' => 'Super Administrateur',
                'admin_level' => 'PAYS',
            ],
            [
                'name' => 'Admin National',
                'email' => 'admin.national@nems.bi',
                'password' => Hash::make('Advanced2026'),
                'role' => 'Admin National',
                'admin_level' => 'PAYS',
            ],
            [
                'name' => 'Admin Ministère',
                'email' => 'admin.ministere@nems.bi',
                'password' => Hash::make('Advanced2026'),
                'role' => 'Admin Ministère',
                'admin_level' => 'MINISTERE',
            ],
            [
                'name' => 'Directeur Provincial',
                'email' => 'directeur.provincial@nems.bi',
                'password' => Hash::make('Advanced2026'),
                'role' => 'Directeur Provincial',
                'admin_level' => 'PROVINCE',
            ],
            [
                'name' => 'Agent Communal',
                'email' => 'agent.communal@nems.bi',
                'password' => Hash::make('Advanced2026'),
                'role' => 'Agent Communal',
                'admin_level' => 'COMMUNE',
            ],
            [
                'name' => 'Superviseur Zone',
                'email' => 'superviseur.zone@nems.bi',
                'password' => Hash::make('Advanced2026'),
                'role' => 'Superviseur Zone',
                'admin_level' => 'ZONE',
            ],
            [
                'name' => 'Directeur École',
                'email' => 'directeur.ecole@nems.bi',
                'password' => Hash::make('Advanced2026'),
                'role' => 'Directeur École',
                'admin_level' => 'ECOLE',
            ],
            [
                'name' => 'Enseignant',
                'email' => 'enseignant@nems.bi',
                'password' => Hash::make('Advanced2026'),
                'role' => 'Enseignant',
                'admin_level' => 'ECOLE',
            ],
            [
                'name' => 'Personnel Administratif',
                'email' => 'personnel.admin@nems.bi',
                'password' => Hash::make('Advanced2026'),
                'role' => 'Personnel Administratif',
                'admin_level' => 'ECOLE',
            ],
            
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']);

            $user = User::updateOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'email_verified_at' => now(),
                    'statut' => 'actif',
                    'admin_level' => $userData['admin_level'],
                    'pays_id' => $burundi->id,
                    'commune_id' => $commune?->id,
                    'zone_id' => $zone?->id,
                    'colline_id' => $colline?->id,
                ]
            );

            // Assign role
            $user->syncRoles($role);

            $this->command->line("✓ Created user: {$user->name} ({$role})");
        }

        $this->command->info('User Seeder completed successfully! (9 users created)');
    }
}
