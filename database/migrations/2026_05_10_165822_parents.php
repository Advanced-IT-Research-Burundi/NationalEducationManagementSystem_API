<?php

namespace Database\Seeders;

use App\Models\Eleve;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\ParentEleve;

class ParentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $eleves = Eleve::take(10)->get();

        if ($eleves->count() < 10) {
            $this->command->warn('Less than 10 students found.');
            return;
        }

        $relations = [
            'Père',
            'Mère',
            'Tuteur',
        ];

        foreach ($eleves as $index => $eleve) {

            // Create user account for parent
            $user = User::create([
                'name' => 'Parent '.$eleve->prenom.' '.$eleve->nom,
                'email' => 'parent'.$index.'@example.com',
                'password' => Hash::make('password'),
                'school_id' => $eleve->school_id,
                'statut' => 'actif',
            ]);

            // Optional if using Spatie roles
            // $user->assignRole('parent');

            // Create parent record
            ParentEleve::create([
                'user_id' => $user->id,
                'eleve_id' => $eleve->id,
                'nom_complet' => 'Parent '.$eleve->prenom.' '.$eleve->nom,
                'relation' => $relations[$index % count($relations)],
                'telephone' => '+2577900000'.$index,
                'email' => 'parent'.$index.'@example.com',
                'adresse' => 'Gitega, Burundi',
            ]);
        }

        $this->command->info('10 parents created successfully.');
    }
}