<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Enseignant;
use App\Models\Pays;
use App\Models\Commune;
use App\Models\Zone;
use App\Models\Colline;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EnseignementSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Création de 40 utilisateurs et enseignants...');

        // 1. Récupération des données géographiques (comme dans votre UserSeeder)
        $burundi = Pays::where('name', 'Burundi')->first() ?? Pays::first();
        $commune = Commune::where('name', 'BUJUMBURA MAIRIE')->first() ?? Commune::first();
        $zone = Zone::where('name', 'ROHERO')->first() ?? Zone::first();
        $colline = Colline::where('name', 'MUKAZA')->first() ?? Colline::first();

        $qualifications = [
            'Master' => ['Informatique', 'Mathématiques', 'Physique', 'Économie'],
            'Licence' => ['Anglais', 'Français', 'Histoire', 'Géographie'],
            'Doctorat' => ['Biologie', 'Chimie', 'Sociologie'],
            'CAPES' => ['Lettres Modernes', 'SVT', 'EPS']
        ];

        for ($i = 1; $i <= 40; $i++) {
            // 2. Création de l'Utilisateur
            $email = "enseignant{$i}@nems.bi";
            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => "Enseignant Test " . $i,
                    'password' => Hash::make('Advanced2026'),
                    'email_verified_at' => now(),
                    'statut' => 'actif',
                    'admin_level' => 'ECOLE',
                    'pays_id' => $burundi?->id,
                    'commune_id' => $commune?->id,
                    'zone_id' => $zone?->id,
                    'colline_id' => $colline?->id,
                ]
            );

            // Assigner le rôle (nécessite Spatie Permissions)
            $user->syncRoles('Enseignant');

            // 3. Sélection aléatoire d'une qualification
            $qualType = array_rand($qualifications);
            $precision = $qualifications[$qualType][array_rand($qualifications[$qualType])];

            // 4. Création de l'Enseignant lié
            Enseignant::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id'               => 1, // Assurez-vous que l'école ID 1 existe
                    'matricule'               => 'ENS-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'qualification'           => $qualType,
                    'qualification_precision' => $precision,
                    'annees_experience'       => rand(1, 25),
                    'date_embauche'           => now()->subYears(rand(0, 15))->format('Y-m-d'),
                    'telephone'               => '79' . rand(100000, 999999),
                    'statut'                  => 'actif',
                    'created_by'              => 1,
                ]
            );
        }

        $this->command->info('Succès : 40 utilisateurs et profils enseignants créés.');
    }
}
