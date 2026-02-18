<?php

use App\Models\Eleve;

// Check existing matricules
echo "Existing Matricules:\n";
$matricules = Eleve::withTrashed()->withoutGlobalScopes()->orderBy('matricule', 'desc')->limit(10)->pluck('matricule');
print_r($matricules->toArray());

$lastEleve = Eleve::withTrashed()->withoutGlobalScopes()->orderBy('matricule', 'desc')->first();
echo "Last Eleve Matricule: " . ($lastEleve ? $lastEleve->matricule : 'None') . "\n";

$generated = (new Eleve)->generateMatricule();
echo "Generated Matricule: $generated\n";

// Check specifically for '000001'
$exists = Eleve::withTrashed()->withoutGlobalScopes()->where('matricule', '000001')->exists();
echo "Does 000001 exist? " . ($exists ? 'Yes' : 'No') . "\n";
