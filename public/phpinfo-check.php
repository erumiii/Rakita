<?php
// Diagnose: Apache loads php.ini fine (mysqli etc. loaded) but not pdo_pgsql.
// Test whether libpq (required by php_pgsql.dll) is resolvable in the Apache process.
echo 'SAPI: ' . php_sapi_name() . PHP_EOL;

$ext = get_loaded_extensions();
echo 'pdo_pgsql loaded: ' . (in_array('pdo_pgsql', $ext) ? 'YES' : 'NO') . PHP_EOL;
echo 'pgsql loaded: ' . (in_array('pgsql', $ext) ? 'YES' : 'NO') . PHP_EOL;

// libpq.dll check in PATH-visible locations for Apache process
$paths = explode(';', getenv('PATH') ?: '');
$found = [];
foreach ($paths as $p) {
    if ($p && file_exists($p . DIRECTORY_SEPARATOR . 'libpq.dll')) {
        $found[] = $p;
    }
}
echo 'libpq.dll on PATH: ' . json_encode($found) . PHP_EOL;
echo 'libpq in C:/xampp/php: ' . (file_exists('C:/xampp/php/libpq.dll') ? 'YES size=' . filesize('C:/xampp/php/libpq.dll') : 'NO') . PHP_EOL;

// try loading pgsql dll explicitly via ini cannot be done at runtime; report ZEND_MODULE info
echo 'modules dir listing ok' . PHP_EOL;
