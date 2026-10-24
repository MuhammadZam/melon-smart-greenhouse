<?php

$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$database = config('database.connections.sqlite.database');
if (config('database.default') !== 'sqlite' || realpath($database) !== realpath($root.'/database/database.sqlite')) {
    throw new RuntimeException('Unexpected database target. No migration applied.');
}
$backup = __DIR__.'/pre-migration.sqlite';
if (!is_file($backup) && !copy($database, $backup)) throw new RuntimeException('Backup failed.');
$exit = Illuminate\Support\Facades\Artisan::call('migrate', [
    '--path' => 'database/migrations/2026_10_22_000001_create_monitoring_ingestion_keys_table.php',
    '--force' => true,
]);
$output = Illuminate\Support\Facades\Artisan::output();
file_put_contents(__DIR__.'/migration-applied.txt', $output);
echo $output;
exit($exit);
