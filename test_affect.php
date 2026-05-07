<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\App\Services\AcademicYearService::setCurrent(1);

$query = \App\Models\AffectationMatiere::with(['enseignant.user', 'matiere', 'school', 'anneeScolaire']);
$query->where('annee_scolaire_id', 1);

echo $query->toSql() . "\n";
print_r($query->getBindings());

$results = $query->get();
echo "Total returned: " . count($results) . "\n";
