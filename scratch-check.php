<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Simulate login flow through the HTTP kernel
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request = Illuminate\Http\Request::create('/login', 'GET');
$response = $kernel->handle($request);
echo 'GET /login -> ' . $response->getStatusCode() . PHP_EOL;

$request = Illuminate\Http\Request::create('/employee/books', 'GET');
$response = $kernel->handle($request);
echo 'GET /employee/books (guest) -> ' . $response->getStatusCode() . PHP_EOL;
echo 'Redirect to: ' . ($response->headers->get('Location') ?? '(none)') . PHP_EOL;
