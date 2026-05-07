<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'superadmin@gmail.com')->first();
$app['auth']->guard('api')->setUser($user);
$app['auth']->shouldUse('api');

$anneeId = \App\Models\AnneeScolaire::current()?->id;

$request = Illuminate\Http\Request::create('/api/academic/eleves', 'GET', [
    'niveau_id' => 1 // assuming 1 exists
]);
$request->headers->set('X-Annee-Scolaire-Id', $anneeId);
$request->setUserResolver(function () use ($user) {
    return $user;
});
$app->instance('request', $request);

try {
    $controller = app(\App\Http\Controllers\Api\Academic\EleveController::class);
    $response = $controller->index($request);
    $data = $response->getData(true)['data'] ?? [];
    echo "API response JSON count: " . count($data) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
