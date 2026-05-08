<?php

namespace Database\Seeders;

use App\Models\TypeScolaire;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $this->command->info('Migrating Type Seeder ...');

        TypeScolaire::create([
            'name' => 'Fondamental',
            'description' => 'Description for Fondamental',
            'actif' => true,
        ]);

        TypeScolaire::create([
            'name' => 'Post-Fondamental',
            'description' => 'Description for Post-Fondamental',
            'actif' => true,
        ]);
    }
}
