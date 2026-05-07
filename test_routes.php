<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$routes = app('router')->getRoutes();
foreach ($routes as $r) {
    if (strpos(strtolower($r->uri()), 'affectation') !== false) {
        echo $r->uri() . " => " . get_class($r->getController() ?? new stdClass) . "@" . $r->getActionMethod() . "\n";
    }
}
