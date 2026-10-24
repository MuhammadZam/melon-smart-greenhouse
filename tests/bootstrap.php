<?php

require dirname(__DIR__).'/vendor/autoload.php';

// These tests must never inherit a developer/production database or cached config.
foreach ([
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
