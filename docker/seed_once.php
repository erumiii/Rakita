<?php

// Runs `db:seed` only while the users table is empty, so container restarts
// never duplicate the seeded employee/books. Called by docker/entrypoint.sh
// after migrations have run.

require __DIR__.'/../vendor/autoload.php';

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

$app = require __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

try {
    $hasUsers = User::exists();
} catch (Throwable) {
    $hasUsers = true; // table missing or db unreachable; surface the error elsewhere
}

if (! $hasUsers) {
    $kernel->call('db:seed', ['--force' => true]);
    echo $kernel->output();
} else {
    echo "Database already seeded; skipping.\n";
}
