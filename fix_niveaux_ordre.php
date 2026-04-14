<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Niveau;

$mappings = [
    '1' => ['1ere', '1ère', '7ème', '7e'],
    '2' => ['2eme', '2ème', '8ème', '8e'],
    '3' => ['3eme', '3ème', '9ème', '9e'],
    '4' => ['4eme', '4ème', '10ème', '10e', '1ere Post-fondamentale'],
    '5' => ['5eme', '5ème', '2eme Post-fondamentale'],
    '6' => ['6eme', '6ème', '3eme Post-fondamentale'],
];

echo "Mise à jour des ordres des niveaux...\n";

$niveaux = Niveau::all();
foreach ($niveaux as $niveau) {
    $found = false;
    foreach ($mappings as $ordre => $keywords) {
        foreach ($keywords as $keyword) {
            if (stripos($niveau->nom, $keyword) !== false || stripos($niveau->code, $keyword) !== false) {
                $niveau->ordre = (int)$ordre;
                $niveau->save();
                echo "Niveau '{$niveau->nom}' ({$niveau->code}) -> Ordre {$ordre}\n";
                $found = true;
                break 2;
            }
        }
    }
    if (!$found) {
        echo "Attention: Aucun ordre trouvé pour '{$niveau->nom}'\n";
    }
}

echo "Terminé !\n";
