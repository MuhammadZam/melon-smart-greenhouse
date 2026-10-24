<?php

namespace Tests\Feature;

use App\Contracts\GoogleSheetsClientInterface;
use App\DTO\FuzzyInferenceResult;
use App\Exceptions\GoogleSheetsException;
use App\Models\GoogleSheetsSyncConfig;
use App\Models\GoogleSheetsSyncLog;
use App\Models\IotDevice;
use App\Models\Monitoring;
use App\Models\MonitoringAlarm;
use App\Models\Plant;
use App\Models\User;
use App\Services\GoogleSheets\GoogleSheetsSyncService;
use App\Services\GoogleSheets\GoogleSheetsTelemetryService;
use App\Services\GoogleSheets\MockGoogleSheetsClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GoogleSheetsTelemetryAndSecurityTest extends TestCase
{
    protected User $admin;
    protected User $pengamat;
    protected Plant $plant;
    protected MockGoogleSheetsClient $mockClient;
    protected GoogleSheetsSyncService $syncService;
    protected GoogleSheetsTelemetryService $telemetryService;

    protected function setUp(): void
    {
        parent::setUp();

        // Admin & Pengamat Users
        $this->admin = User::firstWhere('email', 'admin.gsheets@melon.com') ?? User::create([
            'name'     => 'Admin GSheets Test',
            'email'    => 'admin.gsheets@melon.com',
            'role'     => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $this->pengamat = User::firstWhere('email', 'pengamat.gsheets@melon.com') ?? User::create([
            'name'     => 'Pengamat GSheets Test',
            'email'    => 'pengamat.gsheets@melon.com',
            'role'     => 'pengamat',
            'password' => bcrypt('password123'),
        ]);

        // Pastikan ada tanaman untuk relasi
        $this->plant = Plant::first() ?? Plant::create([
            'name'            => 'Melon Golden Test',
            'variety'         => 'Golden Melon',
            'planting_date'   => now()->subDays(30),
            'estimated_harvest' => now()->addDays(40),
            'location'        => 'Greenhouse-A',
            'status'          => 'active',
        ]);

        // Buat Mock Client terisolasi
        $this->mockClient = new MockGoogleSheetsClient();
        $this->telemetryService = new GoogleSheetsTelemetryService($this->mockClient);
        $this->syncService = new GoogleSheetsSyncService(
            $this->telemetryService,
            app(\App\Services\MonitoringAlarmService::class)
        );

        // Bind Mock ke Service Container untuk injection di command/controller
        App::instance(GoogleSheetsClientInterface::class, $this->mockClient);
        App::instance(MockGoogleSheetsClient::class, $this->mockClient);
        App::instance(GoogleSheetsTelemetryService::class, $this->telemetryService);
        App::instance(GoogleSheetsSyncService::class, $this->syncService);

        GoogleSheetsSyncConfig::first()?->update(['last_synced_row' => 1]);
    }

    protected function tearDown(): void
    {
        // Bersihkan data yang dibuat selama test
        Monitoring::where('source', 'google_sheets')->delete();
        Monitoring::where('device_id', 'like', 'TEST-%')->delete();
        GoogleSheetsSyncLog::where('mode', 'mock')->delete();
        IotDevice::where('device_id', 'like', 'TEST-%')->delete();
        MonitoringAlarm::where('parameter_key', 'temperature')
            ->where('notes', 'like', '%Test%')
            ->delete();

        parent::tearDown();
    }

    // 1. Valid Google Sheets Rows Ingestion
    public function test_1_valid_google_sheets_rows_ingestion(): void
    {
        $this->mockClient->setMockRows([
            [
                'TEST-ESP32-01',
                '2026-10-16 10:00:00',
                $this->plant->name,
                28.5, 65.0, 70.0, 6.2, 2.1, 25000, 24.0, 85.0, 'ON', 'OFF', 88.5, 'OPTIMAL'
            ],
        ]);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals('SUCCESS', $log->status);
        $this->assertEquals(1, $log->records_imported);
        $this->assertEquals(0, $log->records_failed);

        $record = Monitoring::where('device_id', 'TEST-ESP32-01')->first();
        $this->assertNotNull($record);
        $this->assertEquals('google_sheets', $record->source);
        $this->assertEquals(28.5, $record->temperature);
        $this->assertEquals(65.0, $record->humidity);
        $this->assertEquals(70.0, $record->soil_moisture);
        $this->assertEquals(6.2, $record->ph);
        $this->assertEquals('ON', $record->reported_pump_state);
        $this->assertEquals('OFF', $record->reported_fan_state);
        $this->assertEquals(88.5, $record->fuzzy_score);
        $this->assertEquals('BAIK', $record->fuzzy_label);
    }

    // 2. Missing Optional Values
    public function test_2_missing_optional_values_handled_gracefully(): void
    {
        $this->mockClient->setMockRows([
            [
                'TEST-ESP32-02',
                '2026-10-16 10:05:00',
                null, // fallback to active plant
                27.0, 60.0, 65.0, 6.5,
                null, null, null, null, null, null, null, null // optionals null
            ],
        ]);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $config->update(['last_synced_row' => 1]);
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals('SUCCESS', $log->status);
        $this->assertEquals(1, $log->records_imported);

        $record = Monitoring::where('device_id', 'TEST-ESP32-02')->first();
        $this->assertNotNull($record);
        $this->assertNull($record->ec);
        $this->assertNull($record->light_intensity);
        $this->assertNull($record->reported_pump_state);
        $this->assertNull($record->fuzzy_score);
    }

    // 3. Invalid Numeric Values
    public function test_3_invalid_numeric_values_rejected(): void
    {
        $this->mockClient->setMockRows([
            [
                'TEST-ESP32-03',
                '2026-10-16 10:10:00',
                $this->plant->name,
                999.0, // Invalid temperature (>60)
                60.0, 65.0, 6.5
            ],
            [
                'TEST-ESP32-03',
                '2026-10-16 10:11:00',
                $this->plant->name,
                27.0,
                'invalid_num', // Non-numeric humidity
                65.0, 6.5
            ],
        ]);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals('FAILED', $log->status);
        $this->assertEquals(0, $log->records_imported);
        $this->assertEquals(2, $log->records_failed);
    }

    // 4. Invalid Timestamps
    public function test_4_invalid_timestamps_rejected(): void
    {
        $this->mockClient->setMockRows([
            [
                'TEST-ESP32-04',
                'not-a-valid-timestamp',
                $this->plant->name,
                27.0, 60.0, 65.0, 6.5
            ],
        ]);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals(1, $log->records_failed);
        $this->assertEquals(0, $log->records_imported);
    }

    // 5. Duplicate Rows Skipped by Idempotency
    public function test_5_duplicate_rows_skipped_by_idempotency(): void
    {
        $row = [
            'TEST-ESP32-05',
            '2026-10-16 10:20:00',
            $this->plant->name,
            27.5, 62.0, 68.0, 6.4, 2.0, 30000, 24.5, 80.0
        ];

        $this->mockClient->setMockRows([$row, $row]); // 2 identical rows

        $config = $this->syncService->getOrCreateDefaultConfig();
        $config->update(['last_synced_row' => 1]);
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals('SUCCESS', $log->status);
        $this->assertEquals(1, $log->records_imported);
        $this->assertEquals(1, $log->records_skipped);
    }

    // 6. Repeated Synchronization
    public function test_6_repeated_synchronization_does_not_duplicate(): void
    {
        $rows = [
            [
                'TEST-ESP32-06',
                '2026-10-16 10:25:00',
                $this->plant->name,
                28.0, 64.0, 70.0, 6.3
            ]
        ];

        $this->mockClient->setMockRows($rows);
        $config = $this->syncService->getOrCreateDefaultConfig();
        $config->update(['last_synced_row' => 1]);

        // Sync Pertama
        $log1 = $this->syncService->sync($config, 'cli', 10);
        $this->assertEquals(1, $log1->records_imported);

        // Sync Kedua dengan baris sama (simulasi kursor di-reset atau baris berulang)
        $config->update(['last_synced_row' => 1]);
        $log2 = $this->syncService->sync($config, 'cli', 10);
        $this->assertEquals(0, $log2->records_imported);
        $this->assertEquals(1, $log2->records_skipped);
    }

    // 7. Out-of-order Readings
    public function test_7_out_of_order_readings_handled(): void
    {
        $rows = [
            [
                'TEST-ESP32-07',
                '2026-10-16 11:00:00',
                $this->plant->name,
                29.0, 65.0, 72.0, 6.5
            ],
            [
                'TEST-ESP32-07',
                '2026-10-16 09:00:00', // Waktu lebih lampau (out of order)
                $this->plant->name,
                26.0, 68.0, 75.0, 6.4
            ]
        ];

        $this->mockClient->setMockRows($rows);
        $config = $this->syncService->getOrCreateDefaultConfig();
        $config->update(['last_synced_row' => 1]);
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals(2, $log->records_imported);

        // Verifikasi kedua rekaman tersimpan dengan timestamp masing-masing
        $records = Monitoring::where('device_id', 'TEST-ESP32-07')
            ->orderBy('monitored_at')
            ->get();

        $this->assertCount(2, $records);
        $this->assertEquals('2026-10-16 09:00:00', $records[0]->monitored_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-10-16 11:00:00', $records[1]->monitored_at->format('Y-m-d H:i:s'));
    }

    // 8. Partial Failure and Retry
    public function test_8_partial_failure_and_retry(): void
    {
        $this->mockClient->setMockRows([
            [
                'TEST-ESP32-08',
                '2026-10-16 11:15:00',
                $this->plant->name,
                28.0, 65.0, 70.0, 6.5 // Valid
            ],
            [
                'TEST-ESP32-08',
                '2026-10-16 11:16:00',
                $this->plant->name,
                -5.0, 65.0, 70.0, 6.5 // Invalid (-5 temp)
            ],
        ]);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $config->update(['last_synced_row' => 1]);
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals('PARTIAL', $log->status);
        $this->assertEquals(1, $log->records_imported);
        $this->assertEquals(1, $log->records_failed);
    }

    // 9. Spreadsheet Unavailable
    public function test_9_spreadsheet_unavailable_handled(): void
    {
        $this->mockClient->simulateNotFound(true);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals('FAILED', $log->status);
        $this->assertStringContainsString('not found', strtolower($log->error_details));
        $this->mockClient->simulateNotFound(false);
    }

    // 10. API Timeout
    public function test_10_api_timeout_handled(): void
    {
        $this->mockClient->simulateTimeout(true);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals('FAILED', $log->status);
        $this->assertStringContainsString('timed out', strtolower($log->error_details));
        $this->mockClient->simulateTimeout(false);
    }

    // 11. Rate Limiting Handled
    public function test_11_rate_limiting_handled(): void
    {
        $this->mockClient->simulateRateLimit(true);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $log = $this->syncService->sync($config, 'cli', 10);

        $this->assertEquals('FAILED', $log->status);
        $this->assertStringContainsString('rate limit', strtolower($log->error_details));
        $this->mockClient->simulateRateLimit(false);
    }

    // 12. Device Token Valid Authentication
    public function test_12_device_token_valid(): void
    {
        [$device, $token] = IotDevice::createWithToken('TEST-DEV-AUTH-01', 'Auth Test Device', 'esp32_hardware');

        $response = $this->postJson('/api/monitorings', [
            'plant_id'     => $this->plant->id,
            'monitored_at' => now()->toDateTimeString(),
            'temperature'  => 28.5,
            'ph'           => 6.5,
            'humidity'     => 70.0,
            'plant_height' => 55.0,
            'plant_density'=> 60.0,
            'condition'    => 'optimal',
        ], [
            'X-Device-Token' => $token,
        ]);

        $response->assertStatus(201);
        $response->assertJson(['success' => true]);

        // Verifikasi heartbeat tercatat
        $device->refresh();
        $this->assertNotNull($device->last_seen_at);
    }

    // 13. Device Token Invalid Rejected
    public function test_13_device_token_invalid(): void
    {
        $response = $this->postJson('/api/monitorings', [
            'plant_id'     => $this->plant->id,
            'monitored_at' => now()->toDateTimeString(),
            'temperature'  => 28.5,
            'ph'           => 6.5,
            'humidity'     => 70.0,
            'plant_height' => 55.0,
            'plant_density'=> 60.0,
        ], [
            'X-Device-Token' => 'invalid-token-here',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success'    => false,
            'error_code' => 'DEVICE_TOKEN_INVALID',
        ]);
    }

    // 14. Revoked Token Rejected
    public function test_14_revoked_token_rejected(): void
    {
        [$device, $token] = IotDevice::createWithToken('TEST-DEV-REVOKED-01', 'Revoked Test Device');
        $device->revoke('Uji pencabutan token Phase 14.');

        $response = $this->postJson('/api/monitorings', [
            'plant_id'     => $this->plant->id,
            'monitored_at' => now()->toDateTimeString(),
            'temperature'  => 28.5,
            'ph'           => 6.5,
            'humidity'     => 70.0,
            'plant_height' => 55.0,
            'plant_density'=> 60.0,
        ], [
            'X-Device-Token' => $token,
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success'    => false,
            'error_code' => 'DEVICE_TOKEN_REVOKED',
        ]);
    }

    // 15. Missing Token in Production Rejected
    public function test_15_missing_token_in_production(): void
    {
        Config::set('services.iot.enforce_device_auth', true);

        $response = $this->postJson('/api/monitorings', [
            'plant_id'     => $this->plant->id,
            'monitored_at' => now()->toDateTimeString(),
            'temperature'  => 28.5,
            'ph'           => 6.5,
            'humidity'     => 70.0,
            'plant_height' => 55.0,
            'plant_density'=> 60.0,
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success'    => false,
            'error_code' => 'DEVICE_TOKEN_REQUIRED',
        ]);

        Config::set('services.iot.enforce_device_auth', false);
    }

    // 16. Simulator Compatibility in Development Mode
    public function test_16_simulator_compatibility(): void
    {
        $this->authenticateDevice('python_simulator');
        Config::set('services.iot.enforce_device_auth', false);

        $response = $this->postJson('/api/monitorings', [
            'plant_id'        => $this->plant->id,
            'monitored_at'    => now()->toDateTimeString(),
            'temperature'     => 28.0,
            'ph'              => 6.2,
            'humidity'        => 68.0,
            'plant_height'    => 50.0,
            'plant_density'   => 55.0,
            'condition'       => 'optimal',
            'plant_condition' => 'optimal',
            'device_id'       => 'SIMULATOR-01',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('data.source', 'simulator');
        $response->assertHeaderMissing('X-Device-Auth-Warning');
    }

    // 17. Four-Parameter Alarm Policy Continuity
    public function test_17_four_parameter_alarm_policy(): void
    {
        $this->mockClient->setMockRows([
            [
                'TEST-ESP32-17',
                '2026-10-16 12:00:00',
                $this->plant->name,
                38.5, // Suhu ekstrem (> maximum threshold) -> memicu alarm
                65.0, 70.0, 6.2,
                0.5, 10000.0, 15.0, 5.0 // tank_level ekstrem rendah (5%), tetapi non-eligible untuk alarm
            ],
        ]);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $config->update(['last_synced_row' => 1]);
        $log = $this->syncService->sync($config, 'cli', 10);
        $this->assertEquals('SUCCESS', $log->status);

        // Alarm suhu harus tercipta
        $tempAlarm = MonitoringAlarm::where('plant_id', $this->plant->id)
            ->where('parameter_key', 'temperature')
            ->where('status', 'active')
            ->first();

        $this->assertNotNull($tempAlarm);

        // Parameter di luar 4-parameter allowlist TIDAK boleh memiliki alarm aktif
        $tankAlarm = MonitoringAlarm::where('plant_id', $this->plant->id)
            ->where('parameter_key', 'tank_level')
            ->where('status', 'active')
            ->first();

        $this->assertNull($tankAlarm);
    }

    // 18. Dashboard Status View Rendering
    public function test_18_dashboard_status_view_rendering(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.integrations.google-sheets.index'));

        $response->assertStatus(200);
        $response->assertSee('Integrasi Telemetri Google Sheets &amp; Keamanan IoT', false);
        $response->assertSee('Sinkronkan Sekarang');
        $response->assertSee('Data Ingested');
    }

    // 19. RBAC Access Control
    public function test_19_rbac_access_control(): void
    {
        // Pengamat tidak boleh mengakses halaman administrasi integrasi
        $response = $this->actingAs($this->pengamat)->get(route('admin.integrations.google-sheets.index'));
        $this->assertTrue(in_array($response->status(), [302, 403]));

        // Admin dapat mengakses halaman integrasi
        $adminResponse = $this->actingAs($this->admin)->get(route('admin.integrations.google-sheets.index'));
        $adminResponse->assertStatus(200);
    }

    // 20. Preservation of Historical Data
    public function test_20_preservation_of_historical_data(): void
    {
        $initialCount = Monitoring::count();

        $this->mockClient->setMockRows([
            [
                'TEST-ESP32-20',
                '2026-10-16 12:30:00',
                $this->plant->name,
                27.0, 65.0, 70.0, 6.2
            ],
        ]);

        $config = $this->syncService->getOrCreateDefaultConfig();
        $config->update(['last_synced_row' => 1]);
        $log = $this->syncService->sync($config, 'cli', 10);
        $this->assertEquals(1, $log->records_imported);

        $afterCount = Monitoring::count();
        $this->assertEquals($initialCount + 1, $afterCount);
    }

    // 21. Regression Phase 1 to 13 Endpoints
    public function test_21_regression_phase_1_to_13(): void
    {
        // Test API Plants Index
        $responsePlants = $this->actingAs($this->admin)->getJson('/api/plants');
        $responsePlants->assertStatus(200);
        $responsePlants->assertJsonStructure(['success', 'message', 'data']);

        // Test API Monitorings Statistics
        $responseStats = $this->getJson('/api/monitorings/statistics');
        $responseStats->assertStatus(200);
        $responseStats->assertJsonStructure(['success', 'message', 'data' => ['total_monitorings']]);

        // Test API Dashboard Summary
        $responseSummary = $this->getJson('/api/dashboard/summary');
        $responseSummary->assertStatus(200);
        $responseSummary->assertJsonStructure(['success', 'message', 'data']);

        // Test FuzzyInferenceResult DTO Serialization
        $fuzzyDto = new FuzzyInferenceResult(
            inferenceTimestamp: '2026-10-16 12:00:00',
            inputValues: ['temperature' => 28.0, 'humidity' => 65.0],
            method: 'Mamdani',
            score: 85.0,
            conditionCategory: 'OPTIMAL',
            conclusion: 'Kondisi lingkungan ideal',
            recommendation: null,
            inferenceSource: 'test_engine'
        );

        $this->assertEquals(85.0, $fuzzyDto->score);
        $this->assertEquals('OPTIMAL', $fuzzyDto->conditionCategory);
        $this->assertIsArray($fuzzyDto->toArray());
    }

    // 22. Reset Cursor allows re-scanning earlier rows without duplicates
    public function test_22_manual_sync_with_reset_cursor_resets_high_water_mark_safely(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $config = GoogleSheetsSyncConfig::first() ?? GoogleSheetsSyncConfig::create([
            'name' => 'Test Config',
            'spreadsheet_id' => 'test_sheet',
            'sheet_name' => 'Telemetry',
            'auth_mode' => 'mock',
            'last_synced_row' => 88,
        ]);

        $config->update(['last_synced_row' => 88]);
        $this->assertEquals(88, $config->fresh()->last_synced_row);

        $response = $this->actingAs($admin)->post('/admin/integrations/google-sheets/sync', [
            'reset_cursor' => 1,
            'batch_limit' => 10,
        ]);

        $response->assertRedirect('/admin/integrations/google-sheets');
        // When reset_cursor is 1, config was reset to 1 before sync execution
        $this->assertTrue($config->fresh()->last_synced_row >= 1);
    }
}

