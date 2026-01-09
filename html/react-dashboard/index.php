<?php
/**
 * Bootstrap Laravel when /react-dashboard/ is accessed directly
 * This ensures the request goes through Laravel routing even if Nginx serves this directory
 */

// Change to the parent directory (html) where the main index.php is
chdir(__DIR__ . '/..');

// Bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$response->send();

$kernel->terminate($request, $response);

