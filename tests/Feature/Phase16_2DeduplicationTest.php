<?php

namespace Tests\Feature;

use App\Models\FuzzyInferenceResult;
use App\Models\Monitoring;
use App\Models\Plant;
use App\Services\MonitoringIngestionService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Phase16_2DeduplicationTest extends TestCase
{
    public function test_ten_concurrent_http_kernel_requests_commit_exactly_once(): void
    {
        $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.'phase162-'.\Illuminate\Support\Str::random(24).'.sqlite';
        touch($file);
        $barrier = $file.'.ready';
        config(['database.connections.sqlite.database' => $file]);
        DB::purge('sqlite');
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--database' => 'sqlite', '--force' => true]);
        $this->seed([\Database\Seeders\MonitoringParameterSeeder::class, \Database\Seeders\AlarmParameterKeySeeder::class]);
        $plant = Plant::factory()->create();
        [$device, $token] = \App\Models\IotDevice::createWithToken('CONCURRENT-DEVICE', 'Parallel fixture');
        $payload = $this->payload(); $payload['plant_id'] = $plant->id; $payload['temperature'] = 40;
        $processes = [];
        try {
            for ($i = 0; $i < 10; $i++) {
                $process = new \Symfony\Component\Process\Process([PHP_BINARY, base_path('tests/Support/ingestion_worker.php'), $file, $barrier]);
                $process->setInput(json_encode(['token' => $token, 'payload' => $payload]));
                $process->setTimeout(40); $process->start(); $processes[] = $process;
            }
            $deadline = microtime(true) + 15;
            while (count(glob($barrier.'.worker-*')) < 10 && microtime(true) < $deadline) usleep(10000);
            $this->assertCount(10, glob($barrier.'.worker-*'), 'All workers must reach the barrier before release.');
            touch($barrier);
            $results = [];
            foreach ($processes as $process) {
                $process->wait();
                $this->assertTrue($process->isSuccessful(), $process->getErrorOutput().$process->getOutput());
                $results[] = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
            }
            $statuses = array_count_values(array_column($results, 'status'));
            $this->assertSame(1, $statuses[201] ?? 0); $this->assertSame(9, $statuses[200] ?? 0);
            $this->assertCount(1, array_unique(array_column($results, 'id')));
            $this->assertSame(1, Monitoring::count());
            $this->assertSame(1, FuzzyInferenceResult::count());
            $this->assertSame(1, \App\Models\MonitoringAlarm::count());
            $this->assertSame(1, DB::table('monitoring_ingestion_keys')->count());
            file_put_contents(base_path('docs/phase16_2/evidence/concurrent.json'), json_encode(['workers' => 10, 'driver' => 'sqlite', 'results' => $results, 'monitorings' => Monitoring::count(), 'inferences' => FuzzyInferenceResult::count()], JSON_PRETTY_PRINT));
        } finally {
            foreach ($processes as $process) if ($process->isRunning()) $process->stop();
            DB::disconnect('sqlite');
            foreach ([$file, $barrier, $file.'-wal', $file.'-shm'] as $temporary) if (is_file($temporary)) unlink($temporary);
            foreach (glob($barrier.'.worker-*') as $marker) unlink($marker);
        }
    }

    public function test_additive_migration_and_rollback_preserve_legacy_records(): void
    {
        $before = Monitoring::count();
        $migration = require database_path('migrations/2026_10_22_000001_create_monitoring_ingestion_keys_table.php');
        $migration->down();
        Monitoring::create($this->attributes()); Monitoring::create($this->attributes());
        $migration->up();
        $this->assertSame($before + 2, Monitoring::count());
        $this->assertSame(0, DB::table('monitoring_ingestion_keys')->count());
        $migration->down();
        $this->assertSame($before + 2, Monitoring::count());
    }

    public function test_ten_identical_api_retries_create_one_observation_and_inference(): void
    {
        $this->authenticateDevice();
        $before = Monitoring::count(); $id = null;
        for ($i = 0; $i < 10; $i++) {
            $r = $this->postJson('/api/monitorings', $this->payload(), ['Idempotency-Key' => 'stable-event-001'])
                ->assertStatus($i === 0 ? 201 : 200)->assertJsonPath('meta.replayed', $i !== 0);
            $id ??= $r->json('data.id');
            $r->assertJsonPath('data.id', $id);
        }
        $this->assertSame($before + 1, Monitoring::count());
        $this->assertSame(1, FuzzyInferenceResult::where('monitoring_id', $id)->count());
        $this->assertSame(1, DB::table('monitoring_ingestion_keys')->count());
    }

    public function test_timestamp_fallback_normalizes_equivalent_timezones(): void
    {
        $this->authenticateDevice();
        $payload = $this->payload(); $payload['monitored_at'] = '2026-10-22T10:00:00+07:00';
        $id = $this->postJson('/api/monitorings', $payload)->assertCreated()->json('data.id');
        $payload['monitored_at'] = '2026-10-22T03:00:00Z';
        $this->postJson('/api/monitorings', $payload)->assertOk()->assertJsonPath('data.id', $id);
    }

    public function test_distinct_subsecond_events_are_not_collapsed(): void
    {
        $this->authenticateDevice(); $payload = $this->payload(); $before = Monitoring::count();
        foreach (['2026-10-22T10:00:00.123456+07:00','2026-10-22T10:00:00.654321+07:00'] as $time) {
            $payload['monitored_at'] = $time;
            $this->postJson('/api/monitorings', $payload)->assertCreated();
        }
        $this->assertSame($before + 2, Monitoring::count());
        $this->assertSame(['2026-10-22 10:00:00.123456','2026-10-22 10:00:00.654321'], DB::table('monitoring_ingestion_keys')->orderBy('observed_at')->pluck('observed_at')->all());
        $payload['monitored_at'] = '2026-10-22T03:00:00.123456Z';
        $this->postJson('/api/monitorings', $payload)->assertOk();
    }

    public function test_identical_sensor_values_at_different_times_or_event_ids_are_preserved(): void
    {
        $this->authenticateDevice();
        $before = Monitoring::count(); $payload = $this->payload();
        $this->postJson('/api/monitorings', $payload)->assertCreated();
        $payload['monitored_at'] = '2026-10-22 10:00:01';
        $this->postJson('/api/monitorings', $payload)->assertCreated();
        foreach (['event-A','event-B'] as $event) $this->postJson('/api/monitorings', $payload + ['event_id' => $event])->assertCreated();
        $this->assertSame($before + 4, Monitoring::count());
    }

    public function test_changed_payload_with_same_key_is_conflict_without_overwrite(): void
    {
        $this->authenticateDevice(); $payload = $this->payload();
        $id = $this->postJson('/api/monitorings', $payload, ['Idempotency-Key' => 'event-1'])->assertCreated()->json('data.id');
        $payload['temperature'] = 38;
        $this->postJson('/api/monitorings', $payload, ['Idempotency-Key' => 'event-1'])->assertConflict()->assertJsonPath('error_code', 'OBSERVATION_IDENTITY_CONFLICT');
        $this->assertEquals(27, Monitoring::find($id)->temperature);
        $this->assertSame(1, DB::table('monitoring_ingestion_keys')->count());
    }

    public static function badKeys(): array
    {
        return [[''], ['with space'], [str_repeat('x',129)], ['bad/key'], ["bad\nkey"]];
    }

    /** @dataProvider badKeys */
    public function test_invalid_idempotency_key_is_rejected(string $key): void
    {
        $this->authenticateDevice();
        $this->postJson('/api/monitorings', $this->payload(), ['Idempotency-Key' => $key])->assertUnprocessable()->assertJsonValidationErrors('idempotency_key');
        $this->assertSame(0, DB::table('monitoring_ingestion_keys')->count());
    }

    public function test_conflicting_header_body_keys_are_rejected(): void
    {
        $this->authenticateDevice();
        $this->postJson('/api/monitorings', $this->payload() + ['event_id' => 'different'], ['Idempotency-Key' => 'event-1'])->assertUnprocessable();
    }

    public function test_same_data_from_different_sources_and_devices_are_not_lost(): void
    {
        $attributes = $this->attributes(); $service = app(MonitoringIngestionService::class);
        $first = $service->ingest($attributes);
        $attributes['source'] = 'google_sheets'; $second = $service->ingest($attributes);
        $attributes['device_id'] = 'OTHER-DEVICE'; $third = $service->ingest($attributes);
        $this->assertCount(3, array_unique([$first['monitoring']->id,$second['monitoring']->id,$third['monitoring']->id]));
    }

    public function test_legacy_duplicates_are_reported_as_conflict_not_deleted(): void
    {
        $attributes = $this->attributes();
        Monitoring::create($attributes); Monitoring::create($attributes);
        $before = Monitoring::count();
        try { app(MonitoringIngestionService::class)->ingest($attributes); $this->fail('Expected legacy conflict'); }
        catch (\Symfony\Component\HttpKernel\Exception\ConflictHttpException $e) { $this->assertSame(409, $e->getStatusCode()); }
        $this->assertSame($before, Monitoring::count());
        $this->assertSame(0, DB::table('monitoring_ingestion_keys')->count());
    }

    public function test_unambiguous_legacy_event_is_replayed_without_rewriting_history(): void
    {
        $legacy = Monitoring::create($this->attributes()); $snapshot = $legacy->fresh()->getRawOriginal();
        $result = app(MonitoringIngestionService::class)->ingest($this->attributes());
        $this->assertFalse($result['created']); $this->assertSame($legacy->id, $result['monitoring']->id);
        $this->assertSame($snapshot, $legacy->fresh()->getRawOriginal());
    }

    private function payload(): array
    {
        return ['plant_id' => Plant::first()->id, 'monitored_at' => '2026-10-22 10:00:00', 'temperature' => 27, 'humidity' => 70, 'soil_moisture' => 70, 'ph' => 6.5, 'plant_height' => 40, 'plant_density' => 60, 'condition' => 'optimal'];
    }

    public function attributes(): array
    {
        $a = $this->payload(); unset($a['condition']);
        return $a + ['source' => 'api', 'device_id' => 'AUDIT-DEVICE', 'plant_condition' => 'optimal', 'plant_color' => 'Hijau', 'notes' => 'Isolated event'];
    }
}
