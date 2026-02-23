<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = [

    // ===== GENERAL =====
    [
        'nom' => 'Scientifique A',
        'code' => 'SCA',
        'description' => 'Section Scientifique A (Math-Physique)'
    ],
    [
        'nom' => 'Scientifique B',
        'code' => 'SCB',
        'description' => 'Section Scientifique B (Bio-Chimie)'
    ],
    [
        'nom' => 'Economique',
        'code' => 'ECO',
        'description' => 'Section Economique'
    ],
    [
        'nom' => 'Sociale',
        'code' => 'SOC',
        'description' => 'Section Sociale'
    ],
    [
        'nom' => 'Normale',
        'code' => 'NOR',
        'description' => 'Section Pédagogique (Ecole Normale)'
    ],

    // ===== TECHNIQUE =====
    [
        'nom' => 'Technique - Informatique',
        'code' => 'INFO',
        'description' => 'Technique - Informatique'
    ],
    [
        'nom' => 'Technique - Télécommunications',
        'code' => 'TEL',
        'description' => 'Technique - Télécommunications'
    ],
    [
        'nom' => 'Technique - Maintenance Informatique',
        'code' => 'MAI',
        'description' => 'Technique - Maintenance Informatique'
    ],
    [
        'nom' => 'Technique - Electromécanique',
        'code' => 'EME',
        'description' => 'Technique - Electromécanique'
    ],
    [
        'nom' => 'Technique - Electrotechnique',
        'code' => 'ELT',
        'description' => 'Technique - Electrotechnique'
    ],
    [
        'nom' => 'Technique - Electricité Industrielle',
        'code' => 'ELI',
        'description' => 'Technique - Electricité Industrielle'
    ],

    // ===== AUTRES =====
    [
        'nom' => 'Artistique',
        'code' => 'ART',
        'description' => 'Section Artistique'
    ],
    [
        'nom' => 'Sportive',
        'code' => 'SPO',
        'description' => 'Section Sportive'
    ],
    [
        'nom' => 'Autre',
        'code' => 'AUT',
        'description' => 'Autre Section'
    ],
];

        foreach ($sections as $section) {
            Section::create($section);
        }
    }
}
