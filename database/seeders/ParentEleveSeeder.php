<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Eleve;

class ParentEleveSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get 5 parents
        $parents = User::role('Parent')->take(5)->get();

        // Get 5 students
        $eleves = Eleve::take(5)->get();

        // Check if enough data exists
        if ($parents->count() < 5 || $eleves->count() < 5) {
            $this->command->warn('Not enough parents or students found.');
            return;
        }

        $relations = [
            'pere',
            'mere',
            'tuteur',
            'pere',
            'mere',
        ];

        foreach ($parents as $index => $parent) {
            DB::table('parents')->updateOrInsert(
                [
                    'user_id' => $parent->id,
                    'eleve_id' => $eleves[$index]->id,
                ],
                [
                    'nom_complet' => $parent->name,
                    'relation' => $relations[$index],
                    'telephone' => $parent->telephone,
                    'email' => $parent->email,
                    'adresse' => $parent->adresse,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command->info('5 parents inserted successfully.');
    }
}