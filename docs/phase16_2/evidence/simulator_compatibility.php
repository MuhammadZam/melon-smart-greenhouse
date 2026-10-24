<?php

$root = dirname(__DIR__, 3);
$probeSocket = @fsockopen('127.0.0.1', 8018, $errno, $error, 0.1);
if ($probeSocket) {
    fclose($probeSocket);
    throw new RuntimeException('Port 8018 is already in use; refusing to reuse another server.');
}
$database = __DIR__.'/simulator.sqlite';
copy($root.'/database/database.sqlite', $database);
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $key => $value) {
    putenv("$key=$value"); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
[$device, $token] = App\Models\IotDevice::createWithToken('PHASE162-SIMULATOR', 'Disposable simulator', 'python_simulator');
$plant = App\Models\Plant::firstOrFail();
$python = $root.'/docs/phase16_2/tools/python/python.exe';
$server = new Symfony\Component\Process\Process([
    PHP_BINARY, '-S', '127.0.0.1:8018', '-t', $root.'/public',
    $root.'/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php',
], $root.'/public');
$server->start();
try {
    $ready = false;
    for ($i = 0; $i < 50; $i++) {
        $socket = @fsockopen('127.0.0.1', 8018, $errno, $error, 0.1);
        if ($socket) { fclose($socket); $ready = true; break; }
        usleep(100000);
    }
    if (!$ready) throw new RuntimeException('Isolated server did not start: '.$server->getErrorOutput());
    $cli = new Symfony\Component\Process\Process([$python, $root.'/iot-simulator/simulator.py', '--once', '--plant-id', (string) $plant->id, '--api-url', 'http://127.0.0.1:8018/api/monitorings'], $root, ['DEVICE_TOKEN' => $token, 'DEVICE_ID' => $device->device_id, 'PYTHONIOENCODING' => 'utf-8']);
    $cli->mustRun();
    $probe = new Symfony\Component\Process\Process([$python, '-B', __DIR__.'/simulator_compatibility.py'], $root);
    $probe->setInput(json_encode(['token' => $token, 'plant_id' => $plant->id, 'device_id' => $device->device_id, 'url' => 'http://127.0.0.1:8018/api/monitorings']));
    $probe->mustRun();
    $out = ['python' => '3.13.17 portable', 'cli_exit' => $cli->getExitCode(), 'cli_http_201' => str_contains($cli->getOutput(), 'SUCCESS (HTTP 201)'), 'replay' => json_decode($probe->getOutput(), true)];
    file_put_contents(__DIR__.'/simulator.json', json_encode($out, JSON_PRETTY_PRINT));
    echo json_encode($out, JSON_PRETTY_PRINT);
} finally {
    $server->stop();
    Illuminate\Support\Facades\DB::disconnect();
    if (is_file($database)) unlink($database);
}
