<?php

// Independent process and HTTP kernel against a disposable DB, never the project DB.
$file = $argv[1] ?? '';
if (!is_file($file) || !str_starts_with(basename($file), 'phase162-') || realpath(dirname($file)) !== realpath(sys_get_temp_dir())) {
    throw new RuntimeException('Worker refused an unsafe database path.');
}
foreach (['APP_ENV' => 'testing', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $file, 'DB_URL' => '', 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array'] as $key => $value) {
    putenv("$key=$value"); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
Illuminate\Support\Facades\DB::statement('PRAGMA busy_timeout = 10000');
$input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
touch($argv[2].'.worker-'.getmypid());
$deadline = microtime(true) + 10;
while (!is_file($argv[2])) {
    if (microtime(true) > $deadline) throw new RuntimeException('Barrier timeout');
    usleep(10000);
}
$request = Illuminate\Http\Request::create('/api/monitorings', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
    'HTTP_X_DEVICE_TOKEN' => $input['token'], 'HTTP_IDEMPOTENCY_KEY' => 'parallel-observation',
], json_encode($input['payload']));
$response = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle($request);
$body = json_decode($response->getContent(), true);
echo json_encode(['status' => $response->getStatusCode(), 'id' => $body['data']['id'] ?? null]);
