<?php

require dirname(__DIR__).'/vendor/autoload.php';

// These tests must never inherit a developer/production database or cached config.
$testDatabase = getenv('COMPRO_REVISION_MYSQL') === '1' ? [
    'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => '3327',
    'DB_DATABASE' => 'compro_revision_test', 'DB_USERNAME' => 'root', 'DB_PASSWORD' => 'revision_test_local',
] : ['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'];
foreach ($testDatabase + [
    'APP_ENV' => 'testing',
    'APP_CONFIG_CACHE' => __DIR__.'/nonexistent-testing-config.php',
    'DB_CONNECTION' => 'sqlite',
    'DB_DATABASE' => ':memory:',
    'DB_URL' => '',
    'CACHE_STORE' => 'array',
    'SESSION_DRIVER' => 'array',
] as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}
