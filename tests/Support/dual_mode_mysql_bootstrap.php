<?php

// Only disposable Docker databases on the existing revision test server.
$database = in_array('--suite', $argv ?? [], true) ? 'compro_revision_test' : 'compro_pump_control_test';
foreach ([
    'APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => __DIR__.'/nonexistent-pump-config.php',
    'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3327',
    'DB_DATABASE' => $database, 'DB_USERNAME' => 'root', 'DB_PASSWORD' => 'revision_test_local',
    'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'file',
    'APP_URL' => 'http://127.0.0.1:8028', 'BCRYPT_ROUNDS' => '4',
] as $key => $value) {
    putenv("$key=$value"); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (!in_array(config('database.connections.mysql.database'), ['compro_revision_test', 'compro_pump_control_test'], true) || (string) config('database.connections.mysql.port') !== '3327') {
    throw new RuntimeException('Unsafe pump test database.');
}
