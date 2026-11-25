<?php

use App\Models\{Monitoring, FuzzyInferenceResult, MonitoringAlarm, Plant, IotDevice, GoogleSheetsSyncConfig};
use App\Services\GoogleSheets\{GoogleSheetsSyncService, GoogleSheetsTelemetryService, MockGoogleSheetsClient};
use App\Services\MonitoringAlarmService;
use Illuminate\Support\Facades\{DB, Schema};
use Symfony\Component\Process\Process;

$crossSource = in_array('--phase2-restore', $argv, true);
if ($crossSource) {
    require dirname(__DIR__, 2).'/vendor/autoload.php';
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $connection = config('database.connections.mysql');
    if (!app()->environment('testing') || config('database.default') !== 'mysql'
        || $connection['host'] !== '127.0.0.1' || (string) $connection['port'] !== '3337'
        || $connection['database'] !== 'compro_phase2_restore' || !empty($connection['url'])) {
        throw new RuntimeException('Expected the isolated Phase 2 restored schema.');
    }
} else {
    require __DIR__.'/revision_mysql_bootstrap.php';
}

if (($argv[1] ?? null) === '--worker') {
    $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    touch($input['barrier'].'.worker-'.getmypid());
    $deadline = microtime(true) + 20;
    while (!is_file($input['barrier'])) {
        if (microtime(true) > $deadline) throw new RuntimeException('Worker barrier timeout');
        usleep(10000);
    }
    if (isset($input['sheet_row'])) {
        config(['services.google_sheets.telemetry_layout' => 'three_input']);
        $client = new MockGoogleSheetsClient();
        $client->setMockRows([$input['sheet_row']]);
        $sync = new GoogleSheetsSyncService(new GoogleSheetsTelemetryService($client), app(MonitoringAlarmService::class));
        $config = new GoogleSheetsSyncConfig([
            'name' => 'Phase 2 concurrent fixture', 'sheet_name' => 'Telemetry',
            'data_range' => 'A2:J', 'auth_mode' => GoogleSheetsSyncConfig::MODE_MOCK,
            'is_active' => true, 'last_synced_row' => 1,
        ]);
        $config->save();
        $log = $sync->sync($config);
        $status = $log->records_imported === 1 ? 201 : ($log->records_skipped === 1 ? 200 : 422);
        echo json_encode(['status' => $status, 'id' => Monitoring::where('plant_id', $input['payload']['plant_id'])->value('id')]);
        exit;
    }
    $request = Illuminate\Http\Request::create('/api/monitorings', 'POST', [], [], [], [
        'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json',
        'HTTP_X_DEVICE_TOKEN' => $input['token'],
    ], json_encode($input['payload'], JSON_THROW_ON_ERROR));
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $response = $kernel->handle($request);
    $data = json_decode($response->getContent(), true);
    echo json_encode(['status' => $response->getStatusCode(), 'id' => $data['data']['id'] ?? null]);
    $kernel->terminate($request, $response);
    exit;
}

if (!Schema::hasTable('users')) {
    // Initialize only an empty disposable schema. No migrate/rollback/drop commands.
    foreach (glob(database_path('migrations/*.php')) as $file) (require $file)->up();
}
(new Database\Seeders\MonitoringParameterSeeder)->run();
Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\AlarmParameterKeySeeder::class, '--force' => true]);
$plant = Plant::factory()->create();
[$device, $token] = IotDevice::createWithToken('REV-'.bin2hex(random_bytes(8)), 'MySQL concurrent fixture');
$payload = ['plant_id' => $plant->id, 'monitored_at' => now()->toIso8601String(), 'temperature' => 40, 'soil_moisture' => 70, 'water_ph' => 6.5];
$payload[$crossSource ? 'observation_id' : 'event_id'] = bin2hex(random_bytes(12));
$barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'compro-revision-'.bin2hex(random_bytes(8));
$processes = [];
try {
    for ($i = 0; $i < 10; $i++) {
        $arguments = [PHP_BINARY, __FILE__, '--worker'];
        $input = compact('token', 'payload', 'barrier');
        if ($crossSource) {
            $arguments[] = '--phase2-restore';
            if ($i === 0) {
                $input['sheet_row'] = [
                    $device->device_id, $payload['monitored_at'], $plant->id, 40, 70, 6.5,
                    null, null, null, $payload['observation_id'],
                ];
            }
        }
        $process = new Process($arguments);
        $process->setInput(json_encode($input));
        $process->setTimeout(45); $process->start(); $processes[] = $process;
    }
    $deadline = microtime(true) + 20;
    while (count(glob($barrier.'.worker-*')) < 10 && microtime(true) < $deadline) usleep(10000);
    if (count(glob($barrier.'.worker-*')) !== 10) throw new RuntimeException('Workers not ready');
    touch($barrier);
    $results = [];
    foreach ($processes as $process) {
        $process->wait();
        if (!$process->isSuccessful()) throw new RuntimeException($process->getErrorOutput().$process->getOutput());
        $results[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
    }
    $statuses = array_count_values(array_column($results, 'status'));
    $ids = array_unique(array_column($results, 'id'));
    $id = reset($ids);
    $counts = ['observations' => Monitoring::where('plant_id', $plant->id)->count(), 'inferences' => FuzzyInferenceResult::where('monitoring_id', $id)->count(), 'alarms' => MonitoringAlarm::where('triggered_by_monitoring_id', $id)->count(), 'receipts' => DB::table('monitoring_ingestion_keys')->where('monitoring_id', $id)->count()];
    $passed = ($statuses[201] ?? 0) === 1 && ($statuses[200] ?? 0) === 9 && count($ids) === 1 && $id !== null && $counts === ['observations'=>1,'inferences'=>1,'alarms'=>1,'receipts'=>1];
    $output = ['passed' => $passed, 'driver' => 'mysql', 'version' => DB::selectOne('SELECT VERSION() AS version')->version, 'workers' => 10, 'results' => $results, 'counts' => $counts];
    if ($crossSource) {
        $output['channels'] = ['rest_api' => 9, 'google_sheets_mock' => 1];
    }
    $evidence = $crossSource ? 'phase2-cross-source-concurrency.json' : 'mysql-concurrency.json';
    file_put_contents(base_path('docs/revision/evidence/'.$evidence), json_encode($output, JSON_PRETTY_PRINT));
    echo json_encode($output, JSON_PRETTY_PRINT);
    if (!$passed) exit(1);
} finally {
    foreach ($processes as $process) if ($process->isRunning()) $process->stop();
    if (is_file($barrier)) unlink($barrier);
    foreach (glob($barrier.'.worker-*') as $file) unlink($file);
}
