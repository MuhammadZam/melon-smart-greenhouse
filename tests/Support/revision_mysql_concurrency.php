<?php

require __DIR__.'/revision_mysql_bootstrap.php';

use App\Models\{Monitoring, FuzzyInferenceResult, MonitoringAlarm, Plant, IotDevice};
use Illuminate\Support\Facades\{DB, Schema};
use Symfony\Component\Process\Process;

if (($argv[1] ?? null) === '--worker') {
    $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    touch($input['barrier'].'.worker-'.getmypid());
    $deadline = microtime(true) + 20;
    while (!is_file($input['barrier'])) {
        if (microtime(true) > $deadline) throw new RuntimeException('Worker barrier timeout');
        usleep(10000);
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
$payload = ['plant_id' => $plant->id, 'monitored_at' => now()->toIso8601String(), 'temperature' => 40, 'soil_moisture' => 70, 'water_ph' => 6.5, 'event_id' => bin2hex(random_bytes(12))];
$barrier = sys_get_temp_dir().DIRECTORY_SEPARATOR.'compro-revision-'.bin2hex(random_bytes(8));
$processes = [];
try {
    for ($i = 0; $i < 10; $i++) {
        $process = new Process([PHP_BINARY, __FILE__, '--worker']);
        $process->setInput(json_encode(compact('token', 'payload', 'barrier')));
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
    file_put_contents(base_path('docs/revision/evidence/mysql-concurrency.json'), json_encode($output, JSON_PRETTY_PRINT));
    echo json_encode($output, JSON_PRETTY_PRINT);
    if (!$passed) exit(1);
} finally {
    foreach ($processes as $process) if ($process->isRunning()) $process->stop();
    if (is_file($barrier)) unlink($barrier);
    foreach (glob($barrier.'.worker-*') as $file) unlink($file);
}
