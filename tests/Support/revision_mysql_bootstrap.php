<?php

// Dedicated disposable Docker database, never the application's .env database.
foreach ([
    'APP_ENV' => 'testing', 'APP_CONFIG_CACHE' => __DIR__.'/nonexistent-revision-config.php',
    'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3327',
    'DB_DATABASE' => 'compro_revision_concurrency', 'DB_USERNAME' => 'root',
    'DB_PASSWORD' => 'revision_test_local', 'DB_URL' => '', 'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'file', 'BCRYPT_ROUNDS' => '4', 'APP_URL' => 'http://127.0.0.1:8027',
] as $key => $value) {
    putenv("$key=$value"); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.connections.mysql.database') !== 'compro_revision_concurrency' || (string) config('database.connections.mysql.port') !== '3327') {
    throw new RuntimeException('Unsafe revision database target.');
}
