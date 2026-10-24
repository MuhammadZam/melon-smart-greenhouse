<?php

namespace Tests\Feature;

use App\Models\Actuator;
use App\Models\FuzzyInferenceResult;
use App\Models\Monitoring;
use App\Models\MonitoringAlarm;
use App\Models\Plant;
use App\Models\User;
use App\Services\FuzzyLogic\FuzzyConclusionService;
use App\Services\FuzzyLogic\FuzzyMembershipService;
use App\Services\FuzzyLogic\FuzzyPipelineService;
use App\Services\FuzzyLogic\FuzzyRuleEvaluationService;
use App\Services\FuzzyLogic\MamdaniInferenceService;
use App\Services\FuzzyLogic\Membership\TrapezoidalMembershipFunction;
use App\Services\FuzzyLogic\Membership\TriangularMembershipFunction;
use App\Services\MonitoringAlarmService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FuzzyLogicInferenceTest extends TestCase
{
    protected User $admin;
    protected User $pengamat;
    protected Plant $plant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateDevice();

        $this->admin = User::firstWhere('email', 'admin.fuzzy@melon.com') ?? User::create([
            'name'     => 'Admin Fuzzy Tester',
            'email'    => 'admin.fuzzy@melon.com',
            'role'     => 'admin',
            'password' => bcrypt('password123'),
        ]);

        $this->pengamat = User::firstWhere('email', 'pengamat.fuzzy@melon.com') ?? User::create([
            'name'     => 'Pengamat Fuzzy Tester',
            'email'    => 'pengamat.fuzzy@melon.com',
            'role'     => 'pengamat',
            'password' => bcrypt('password123'),
        ]);

        $this->plant = Plant::firstWhere('name', 'Golden Melon Greenhouse Test') ?? Plant::create([
            'name'             => 'Golden Melon Greenhouse Test',
            'variety'          => 'Golden Aroma',
            'location'         => 'Greenhouse-B1',
            'planting_date'    => now()->subDays(20),
            'status'           => 'active',
            'target_harvest_date' => now()->addDays(50),
            'expected_yield'   => 120.0,
        ]);

        Cache::forget('fuzzy_inference_runtime_enabled');
    }

    // 1. Triangular Membership Function
    public function test_01_triangular_membership_function(): void
    {
        // Segitiga [20, 25, 30]
        $fn = new TriangularMembershipFunction(20.0, 25.0, 30.0);

        $this->assertEquals(0.0, $fn->calculate(15.0)); // Di bawah batas bawah
        $this->assertEquals(0.0, $fn->calculate(20.0)); // Batas bawah
        $this->assertEquals(0.5, $fn->calculate(22.5)); // Lereng naik (22.5 - 20) / (25 - 20) = 0.5
        $this->assertEquals(1.0, $fn->calculate(25.0)); // Puncak segitiga
        $this->assertEquals(0.5, $fn->calculate(27.5)); // Lereng turun (30 - 27.5) / (30 - 25) = 0.5
        $this->assertEquals(0.0, $fn->calculate(30.0)); // Batas atas
        $this->assertEquals(0.0, $fn->calculate(35.0)); // Di atas batas atas
    }

    // 2. Trapezoidal Membership Function
    public function test_02_trapezoidal_membership_function(): void
    {
        // Trapesium [10, 20, 30, 40]
        $fn = new TrapezoidalMembershipFunction(10.0, 20.0, 30.0, 40.0);

        $this->assertEquals(0.0, $fn->calculate(5.0));  // Di bawah batas bawah
        $this->assertEquals(0.5, $fn->calculate(15.0)); // Lereng naik
        $this->assertEquals(1.0, $fn->calculate(20.0)); // Titik datar awal
        $this->assertEquals(1.0, $fn->calculate(25.0)); // Titik datar tengah
        $this->assertEquals(1.0, $fn->calculate(30.0)); // Titik datar akhir
        $this->assertEquals(0.5, $fn->calculate(35.0)); // Lereng turun
        $this->assertEquals(0.0, $fn->calculate(45.0)); // Di atas batas atas

        // Left-shoulder test: [0, 0, 50, 65]
        $leftShoulder = new TrapezoidalMembershipFunction(0.0, 0.0, 50.0, 65.0);
        $this->assertEquals(1.0, $leftShoulder->calculate(-5.0));
        $this->assertEquals(1.0, $leftShoulder->calculate(0.0));
        $this->assertEquals(1.0, $leftShoulder->calculate(40.0));

        // Right-shoulder test: [75, 85, 100, 100]
        $rightShoulder = new TrapezoidalMembershipFunction(75.0, 85.0, 100.0, 100.0);
        $this->assertEquals(1.0, $rightShoulder->calculate(95.0));
        $this->assertEquals(1.0, $rightShoulder->calculate(100.0));
        $this->assertEquals(1.0, $rightShoulder->calculate(110.0));
    }

    // 3. Membership Boundary Values
    public function test_03_membership_boundary_values(): void
    {
        $fn = new TriangularMembershipFunction(10.0, 20.0, 30.0);
        $this->assertGreaterThanOrEqual(0.0, $fn->calculate(-100.0));
        $this->assertLessThanOrEqual(1.0, $fn->calculate(20.0));
        $this->assertLessThanOrEqual(1.0, $fn->calculate(500.0));
    }

    // 4. AND Minimum Operator
    public function test_04_and_minimum_operator(): void
    {
        $membershipService = app(FuzzyMembershipService::class);
        $ruleService = new FuzzyRuleEvaluationService([
            [
                'id'          => 'TEST_AND',
                'name'        => 'Test AND Operator',
                'operator'    => 'and',
                'antecedents' => [
                    'temperature' => 'optimal',
                    'humidity'    => 'optimal',
                ],
                'consequent'  => 'baik',
                'weight'      => 1.0,
                'enabled'     => true,
            ]
        ]);

        $snapshot = [
            'temperature' => ['optimal' => 0.8],
            'humidity'    => ['optimal' => 0.4],
        ];

        $eval = $ruleService->evaluateRules($snapshot);
        $this->assertEquals(1, $eval['active_count']);
        // min(0.8, 0.4) = 0.4
        $this->assertEquals(0.4, $eval['activated_rules'][0]['firing_strength']);
    }

    // 5. OR Maximum Operator
    public function test_05_or_maximum_operator(): void
    {
        $ruleService = new FuzzyRuleEvaluationService([
            [
                'id'          => 'TEST_OR',
                'name'        => 'Test OR Operator',
                'operator'    => 'or',
                'antecedents' => [
                    'temperature' => 'panas',
                    'humidity'    => 'kering',
                ],
                'consequent'  => 'buruk',
                'weight'      => 1.0,
                'enabled'     => true,
            ]
        ]);

        $snapshot = [
            'temperature' => ['panas' => 0.3],
            'humidity'    => ['kering' => 0.9],
        ];

        $eval = $ruleService->evaluateRules($snapshot);
        // max(0.3, 0.9) = 0.9
        $this->assertEquals(0.9, $eval['activated_rules'][0]['firing_strength']);
    }

    // 6. Rule Activation and Firing Strength
    public function test_06_rule_activation_and_firing_strength(): void
    {
        $membershipService = app(FuzzyMembershipService::class);
        $snapshot = $membershipService->fuzzify([
            'temperature'   => 27.0, // Optimal peak
            'humidity'      => 70.0, // Optimal peak
            'soil_moisture' => 70.0, // Ideal peak
            'ph'            => 6.5,  // Optimal peak
        ]);

        $ruleService = app(FuzzyRuleEvaluationService::class);
        $eval = $ruleService->evaluateRules($snapshot);

        $this->assertGreaterThan(0, $eval['active_count']);
        $this->assertGreaterThanOrEqual(0.9, $eval['max_firing_strength']);
    }

    // 7. Implication (Mamdani Minimum)
    public function test_07_implication_mamdani_minimum(): void
    {
        $inferenceService = app(MamdaniInferenceService::class);
        $result = $inferenceService->infer([
            'temperature'   => 27.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('AVAILABLE', $result['status']);
        $this->assertNotEmpty($result['aggregated_curve']);
        // Pastikan derajat keanggotaan pada kurva agregasi tidak melebihi 1.0
        foreach ($result['aggregated_curve'] as $z => $mu) {
            $this->assertLessThanOrEqual(1.0, $mu);
            $this->assertGreaterThanOrEqual(0.0, $mu);
        }
    }

    // 8. Aggregation (Maximum Envelope)
    public function test_08_aggregation_maximum(): void
    {
        $inferenceService = app(MamdaniInferenceService::class);
        $result = $inferenceService->infer([
            'temperature'   => 33.0, // Panas moderat
            'humidity'      => 60.0,
            'soil_moisture' => 60.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('AVAILABLE', $result['status']);
        $this->assertNotNull($result['score']);
    }

    // 9. Centroid Defuzzification (COG)
    public function test_09_centroid_defuzzification(): void
    {
        $inferenceService = app(MamdaniInferenceService::class);
        $result = $inferenceService->infer([
            'temperature'   => 27.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 6.5,
        ]);

        $this->assertNotNull($result['score']);
        $this->assertEquals(86.17, $result['score']);
        $this->assertEqualsWithDelta(2412.6667, $result['numerator'], 1e-3);
        $this->assertEqualsWithDelta(28.0000, $result['denominator'], 1e-3);
        $this->assertEquals('BAIK', $result['category']);
    }

    // 10. Deterministic Score Reproducibility
    public function test_10_deterministic_score_reproducibility(): void
    {
        $inferenceService = app(MamdaniInferenceService::class);
        $inputs = [
            'temperature'   => 28.5,
            'humidity'      => 68.0,
            'soil_moisture' => 72.0,
            'ph'            => 6.4,
        ];

        $run1 = $inferenceService->infer($inputs);
        $run2 = $inferenceService->infer($inputs);

        $this->assertEquals($run1['score'], $run2['score']);
        $this->assertEquals($run1['category'], $run2['category']);
    }

    // 11. Valid Input End-to-End Evaluation
    public function test_11_valid_input_end_to_end_evaluation(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 27.0,
            'humidity'             => 70.0,
            'soil_moisture'        => 70.0,
            'ph'                   => 6.5,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 80.0,
            'source'               => 'manual',
        ]);

        $pipeline = app(FuzzyPipelineService::class);
        $result = $pipeline->process($monitoring, 'laravel_engine');

        $this->assertNotNull($result);
        $this->assertEquals('AVAILABLE', $result->inference_status);
        $this->assertNotNull($result->fuzzy_score);
        $this->assertEquals('BAIK', $result->fuzzy_category);
        $this->assertNotNull($result->conclusion);
        $this->assertNotNull($result->recommendation);
    }

    // 12. Missing Sensor Produces INSUFFICIENT_DATA
    public function test_12_missing_sensor_produces_insufficient_data(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 27.0,
            'humidity'             => 70.0,
            'soil_moisture'        => null, // Missing sensor (soil_moisture is nullable in DB)
            'ph'                   => 6.5,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 80.0,
            'source'               => 'manual',
        ]);

        $pipeline = app(FuzzyPipelineService::class);
        $result = $pipeline->process($monitoring, 'laravel_engine');

        $this->assertNotNull($result);
        $this->assertEquals('INSUFFICIENT_DATA', $result->inference_status);
        $this->assertNull($result->fuzzy_score);
        $this->assertNull($result->fuzzy_category);
        $this->assertStringContainsString('tidak lengkap', strtolower($result->conclusion));
    }

    // 13. Invalid Sensor Value Produces INSUFFICIENT_DATA
    public function test_13_invalid_sensor_value_produces_insufficient_data(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 95.0, // Outside valid physics range!
            'humidity'             => 70.0,
            'soil_moisture'        => 70.0,
            'ph'                   => 6.5,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 80.0,
            'source'               => 'manual',
        ]);

        $pipeline = app(FuzzyPipelineService::class);
        $result = $pipeline->process($monitoring, 'laravel_engine');

        $this->assertEquals('INSUFFICIENT_DATA', $result->inference_status);
        $this->assertNull($result->fuzzy_score);
    }

    // 14. Uncovered Rule Produces INSUFFICIENT_RULE_COVERAGE
    public function test_14_uncovered_rule_produces_insufficient_rule_coverage(): void
    {
        $membershipService = app(FuzzyMembershipService::class);
        // Custom empty ruleset
        $ruleService = new FuzzyRuleEvaluationService([]);
        $inferenceService = new MamdaniInferenceService($membershipService, $ruleService, config('fuzzy'));

        $output = $inferenceService->infer([
            'temperature'   => 27.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('INSUFFICIENT_RULE_COVERAGE', $output['status']);
        $this->assertNull($output['score']);
    }

    // 15. Duplicate Inference Prevention (Idempotency)
    public function test_15_duplicate_inference_prevention(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 28.0,
            'humidity'             => 70.0,
            'soil_moisture'        => 65.0,
            'ph'                   => 6.2,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 75.0,
            'source'               => 'manual',
        ]);

        $pipeline = app(FuzzyPipelineService::class);

        $result1 = $pipeline->process($monitoring, 'laravel_engine');
        $countAfter1 = FuzzyInferenceResult::where('monitoring_id', $monitoring->id)->count();

        $result2 = $pipeline->process($monitoring, 'laravel_engine');
        $countAfter2 = FuzzyInferenceResult::where('monitoring_id', $monitoring->id)->count();

        $this->assertEquals(1, $countAfter1);
        $this->assertEquals(1, $countAfter2);
        $this->assertEquals($result1->id, $result2->id);
    }

    // 16. Rule Versioning Support
    public function test_16_rule_versioning_support(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 28.0,
            'humidity'             => 70.0,
            'soil_moisture'        => 65.0,
            'ph'                   => 6.2,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 75.0,
            'source'               => 'manual',
        ]);

        $pipeline = app(FuzzyPipelineService::class);

        // Versi 1
        config(['fuzzy.rule_version' => 'v1.0-provisional']);
        $pipeline->process($monitoring, 'laravel_engine');

        // Versi 2
        config(['fuzzy.rule_version' => 'v2.0-experimental']);
        $pipeline->process($monitoring, 'laravel_engine');

        $inferences = FuzzyInferenceResult::where('monitoring_id', $monitoring->id)->get();
        $this->assertCount(2, $inferences);
        $this->assertTrue($inferences->contains('rule_version', 'v1.0-provisional'));
        $this->assertTrue($inferences->contains('rule_version', 'v2.0-experimental'));
    }

    // 17. External Fuzzy Result Preservation
    public function test_17_external_fuzzy_result_preservation(): void
    {
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 34.0,
            'humidity'             => 55.0,
            'soil_moisture'        => 50.0,
            'ph'                   => 6.0,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 75.0,
            'source'               => 'google_sheets',
            'fuzzy_score'          => 88.5,
            'fuzzy_label'          => 'BAIK',
        ]);

        $pipeline = app(FuzzyPipelineService::class);

        $externalPayload = [
            'score'              => 88.5,
            'condition_category' => 'BAIK',
            'conclusion'         => 'Hasil inferensi dari ESP32 firmware.',
            'recommendation'     => 'Pertahankan aerasi.',
        ];

        $result = $pipeline->process($monitoring, 'google_sheets_payload', $externalPayload);

        $this->assertEquals('google_sheets_payload', $result->inference_source);
        $this->assertEquals(88.5, $result->fuzzy_score);
        $this->assertEquals('Hasil inferensi dari ESP32 firmware.', $result->conclusion);
    }

    // 18. Web Monitoring Integration
    public function test_18_web_monitoring_integration(): void
    {
        $response = $this->actingAs($this->admin)->post(route('monitorings.store'), [
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now()->toDateTimeString(),
            'temperature'          => 26.5,
            'humidity'             => 68.0,
            'soil_moisture'        => 72.0,
            'ph'                   => 6.4,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau Segar',
            'plant_height'         => 85.0,
            'notes'                => 'Pengujian inferensi web form Phase 15',
        ]);

        $response->assertRedirect(route('monitorings.index'));

        $monitoring = Monitoring::latest('id')->first();
        $this->assertNotNull($monitoring->latestFuzzyInference);
        $this->assertEquals('AVAILABLE', $monitoring->latestFuzzyInference->inference_status);
        $this->assertNotNull($monitoring->fuzzy_score);
    }

    // 19. REST API Integration
    public function test_19_rest_api_integration(): void
    {
        $response = $this->actingAs($this->admin)->postJson('/api/monitorings', [
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now()->toDateTimeString(),
            'temperature'          => 28.0,
            'humidity'             => 70.0,
            'soil_moisture'        => 75.0,
            'ph'                   => 6.5,
            'plant_condition'      => 'optimal',
            'leaf_color'           => 'Hijau Segar',
            'plant_height'         => 80.0,
            'canopy_density'       => 70.0,
            'notes'                => 'API ingestion fuzzy check',
        ]);

        $response->assertStatus(201);

        $monitoring = Monitoring::latest('id')->first();
        $this->assertNotNull($monitoring->latestFuzzyInference);
        $this->assertEquals('api_ingest', $monitoring->latestFuzzyInference->inference_source);
    }

    // 20. Google Sheets Integration Mock
    public function test_20_google_sheets_integration_mock(): void
    {
        $mockClient = new \App\Services\GoogleSheets\MockGoogleSheetsClient();
        $mockClient->setMockRows([
            [
                'device_id'       => 'TEST-FUZZY-GS',
                'timestamp'       => '2026-10-16 11:00:00',
                'plant_code'      => $this->plant->name,
                'temperature'     => 27.5,
                'humidity'        => 70.0,
                'soil_moisture'   => 75.0,
                'ph'              => 6.5,
                'ec'              => 2.1,
                'light_intensity' => 35000,
                'nutrient_temp'   => 23.0,
                'tank_level'      => 85.0,
                'pump_status'     => 'ON',
                'fan_status'      => 'OFF',
            ]
        ]);

        $telemetryService = new \App\Services\GoogleSheets\GoogleSheetsTelemetryService($mockClient);
        $alarmService = app(MonitoringAlarmService::class);
        $syncService = new \App\Services\GoogleSheets\GoogleSheetsSyncService($telemetryService, $alarmService);

        $config = $syncService->getOrCreateDefaultConfig();
        $log = $syncService->sync($config, 'cli', 5);

        $this->assertEquals('SUCCESS', $log->status);
        $monitoring = Monitoring::where('device_id', 'TEST-FUZZY-GS')->first();
        $this->assertNotNull($monitoring);
        $this->assertNotNull($monitoring->latestFuzzyInference);
        $this->assertEquals('google_sheets_sync', $monitoring->latestFuzzyInference->inference_source);
    }

    // 21. Python Simulator Compatibility
    public function test_21_python_simulator_compatibility(): void
    {
        $payload = [
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now()->toDateTimeString(),
            'temperature'          => 29.5,
            'humidity'             => 65.0,
            'soil_moisture'        => 68.0,
            'ph'                   => 6.3,
            'plant_condition'      => 'optimal',
            'leaf_color'           => 'Hijau',
            'plant_height'         => 88.0,
            'canopy_density'       => 75.0,
            'notes'                => 'Simulator IoT payload test',
        ];

        $response = $this->actingAs($this->admin)->postJson('/api/monitorings', $payload);
        $response->assertStatus(201);

        $monitoring = Monitoring::latest('id')->first();
        $this->assertNotNull($monitoring->latestFuzzyInference);
    }

    // 22. Dashboard Rendering and Provisional Warning Label
    public function test_22_dashboard_rendering_and_provisional_label(): void
    {
        // Pastikan ada satu data inferensi
        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 27.0,
            'humidity'             => 70.0,
            'soil_moisture'        => 70.0,
            'ph'                   => 6.5,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 80.0,
            'source'               => 'manual',
        ]);
        app(FuzzyPipelineService::class)->process($monitoring, 'laravel_engine');

        $response = $this->actingAs($this->pengamat)->get(route('dashboard', ['plant_id' => $this->plant->id]));

        $response->assertStatus(200);
        $response->assertSee('Analisis Kondisi Greenhouse — Fuzzy Logic');
        $response->assertSee('Aturan Fuzzy Simulasi — Belum Divalidasi Tim Elektro.');
        $response->assertSee('Skor Kondisi');
    }

    // 23. History Page and Filters
    public function test_23_history_page_and_filters(): void
    {
        $response = $this->actingAs($this->pengamat)->get(route('fuzzy.history', [
            'plant_id' => $this->plant->id,
            'category' => 'BAIK',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Riwayat Analisis');
        $response->assertSee('Mamdani Inference Engine');
    }

    // 24. Admin RBAC Fuzzy Config
    public function test_24_admin_rbac_fuzzy_config(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.fuzzy.config'));
        $response->assertStatus(200);
        $response->assertSee('Konfigurasi & Audit');
        $response->assertSee('PROVISIONAL_FOR_SOFTWARE_TESTING');

        // Test Admin toggle
        $toggle = $this->actingAs($this->admin)->post(route('admin.fuzzy.toggle'));
        $toggle->assertRedirect(route('admin.fuzzy.config'));
    }

    // 25. Pengamat RBAC Fuzzy Config Forbidden
    public function test_25_pengamat_rbac_fuzzy_config_forbidden(): void
    {
        $response = $this->actingAs($this->pengamat)->get(route('admin.fuzzy.config'));
        $response->assertStatus(403);

        $toggle = $this->actingAs($this->pengamat)->post(route('admin.fuzzy.toggle'));
        $toggle->assertStatus(403);
    }

    // 26. Stale Data Detection
    public function test_26_stale_data_detection(): void
    {
        $oldMonitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now()->subDays(3), // 72 hours ago
            'temperature'          => 27.0,
            'humidity'             => 70.0,
            'soil_moisture'        => 70.0,
            'ph'                   => 6.5,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 80.0,
            'source'               => 'manual',
        ]);

        $pipeline = app(FuzzyPipelineService::class);
        $result = $pipeline->process($oldMonitoring, 'laravel_engine');

        $this->assertEquals('STALE', $result->inference_status);
        $this->assertNotNull($result->fuzzy_score); // Score is calculated, but status indicates stale
    }

    // 27. Out of Order Telemetry Handling
    public function test_27_out_of_order_telemetry_handling(): void
    {
        $mNew = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 27.0,
            'humidity'             => 70.0,
            'soil_moisture'        => 70.0,
            'ph'                   => 6.5,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 80.0,
            'source'               => 'manual',
        ]);
        $pipeline = app(FuzzyPipelineService::class);
        $pipeline->process($mNew, 'laravel_engine');

        // Old monitoring ingested after the new one
        $mOld = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now()->subHours(5),
            'temperature'          => 35.0,
            'humidity'             => 50.0,
            'soil_moisture'        => 45.0,
            'ph'                   => 5.5,
            'plant_condition'      => 'warning',
            'plant_color'          => 'Kuning',
            'plant_height'         => 75.0,
            'source'               => 'manual',
        ]);
        $pipeline->process($mOld, 'laravel_engine');

        // Both inferences must exist
        $this->assertNotNull($mNew->latestFuzzyInference);
        $this->assertNotNull($mOld->latestFuzzyInference);
    }

    // 28. Failure Isolation on Inference Exception
    public function test_28_failure_isolation_on_inference_exception(): void
    {
        // Mock inference service yang throw exception
        $mockInference = $this->createMock(MamdaniInferenceService::class);
        $mockInference->method('infer')->willThrowException(new \RuntimeException("Simulated inference engine crash"));

        $pipeline = new FuzzyPipelineService($mockInference, app(FuzzyConclusionService::class));

        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 27.0,
            'humidity'             => 70.0,
            'soil_moisture'        => 70.0,
            'ph'                   => 6.5,
            'plant_condition'      => 'optimal',
            'plant_color'          => 'Hijau',
            'plant_height'         => 80.0,
            'source'               => 'manual',
        ]);

        // Proses tidak boleh melempar exception ke luar
        $result = $pipeline->process($monitoring, 'laravel_engine');

        $this->assertNotNull($result);
        $this->assertEquals('ERROR', $result->inference_status);
        $this->assertNull($result->fuzzy_score);
        $this->assertDatabaseHas('monitorings', ['id' => $monitoring->id]);
    }

    // 29. Four Parameter Alarm Policy Compatibility
    public function test_29_four_parameter_alarm_compatibility(): void
    {
        // Pastikan hanya 4 parameter yang eligible untuk alarm
        $this->assertTrue(MonitoringAlarmService::isAlarmEligible('temperature'));
        $this->assertTrue(MonitoringAlarmService::isAlarmEligible('humidity'));
        $this->assertTrue(MonitoringAlarmService::isAlarmEligible('soil_moisture'));
        $this->assertTrue(MonitoringAlarmService::isAlarmEligible('ph'));

        // Parameter lainnya TIDAK boleh menghasilkan alarm
        $this->assertFalse(MonitoringAlarmService::isAlarmEligible('ec'));
        $this->assertFalse(MonitoringAlarmService::isAlarmEligible('light_intensity'));
        $this->assertFalse(MonitoringAlarmService::isAlarmEligible('tank_level'));
        $this->assertFalse(MonitoringAlarmService::isAlarmEligible('fuzzy_score'));
    }

    // 30. Actuator Simulation Compatibility
    public function test_30_actuator_simulation_compatibility(): void
    {
        // Skor fuzzy buruk TIDAK boleh otomatis menyalakan aktuator fisik atau simulasi
        Actuator::where('code', 'TEST-ACT-FUZZY')->delete();
        $actuator = Actuator::create([
            'code'             => 'TEST-ACT-FUZZY',
            'name'             => 'Pompa Uji Fuzzy',
            'type'             => Actuator::TYPE_IRRIGATION_PUMP,
            'simulation_state' => Actuator::STATE_OFF,
            'mode'             => Actuator::MODE_SIMULATION,
            'is_enabled'       => true,
        ]);

        $monitoring = Monitoring::create([
            'plant_id'             => $this->plant->id,
            'monitored_at'         => now(),
            'temperature'          => 40.0, // Sangat panas
            'humidity'             => 35.0, // Sangat kering
            'soil_moisture'        => 30.0, // Sangat kering
            'ph'                   => 5.0,  // Asam
            'plant_condition'      => 'critical',
            'plant_color'          => 'Kuning',
            'plant_height'         => 80.0,
            'source'               => 'manual',
        ]);

        app(FuzzyPipelineService::class)->process($monitoring, 'laravel_engine');

        // Verifikasi aktuator tetap OFF (tidak dipicu otomatis oleh fuzzy)
        $actuator->refresh();
        $this->assertEquals(Actuator::STATE_OFF, $actuator->simulation_state);
    }

    // 31. Phase 1 to 14 Regression Verification
    public function test_31_phase_1_to_14_regression_verification(): void
    {
        $this->actingAs($this->pengamat)->get(route('dashboard'))->assertStatus(200);
        $this->actingAs($this->pengamat)->get(route('monitorings.index'))->assertStatus(200);
        $this->actingAs($this->pengamat)->get(route('plants.index'))->assertStatus(200);
        $this->actingAs($this->pengamat)->get(route('alarms.index'))->assertStatus(200);
        $this->actingAs($this->pengamat)->get(route('actuators.index'))->assertStatus(200);
    }
}
