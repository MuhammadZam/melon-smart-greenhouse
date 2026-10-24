<?php

namespace Tests\Unit;

use App\Services\FuzzyLogic\FuzzyMembershipService;
use App\Services\FuzzyLogic\FuzzyRuleEvaluationService;
use App\Services\FuzzyLogic\MamdaniInferenceService;
use App\Services\FuzzyLogic\Membership\TrapezoidalMembershipFunction;
use App\Services\FuzzyLogic\Membership\TriangularMembershipFunction;
use InvalidArgumentException;
use Tests\TestCase;

class FuzzyMathematicalValidationTest extends TestCase
{
    protected MamdaniInferenceService $inferenceService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inferenceService = app(MamdaniInferenceService::class);
    }

    // 1. Triangular Membership Function Validation
    public function test_triangular_membership_function_mathematics(): void
    {
        // Segitiga [22.0, 27.0, 32.0]
        $tri = new TriangularMembershipFunction(22.0, 27.0, 32.0);

        // Di luar domain
        $this->assertEquals(0.0, $tri->calculate(20.0));
        $this->assertEquals(0.0, $tri->calculate(35.0));

        // Tepat di batas
        $this->assertEquals(0.0, $tri->calculate(22.0));
        $this->assertEquals(0.0, $tri->calculate(32.0));

        // Lereng naik linear: (24.5 - 22.0) / (27.0 - 22.0) = 2.5 / 5.0 = 0.5
        $this->assertEqualsWithDelta(0.5, $tri->calculate(24.5), 1e-6);

        // Puncak kurva
        $this->assertEquals(1.0, $tri->calculate(27.0));

        // Lereng turun linear: (32.0 - 29.5) / (32.0 - 27.0) = 2.5 / 5.0 = 0.5
        $this->assertEqualsWithDelta(0.5, $tri->calculate(29.5), 1e-6);

        // Validasi penolakan parameter degenerate (a >= c)
        $this->expectException(InvalidArgumentException::class);
        new TriangularMembershipFunction(25.0, 25.0, 25.0);
    }

    public function test_triangular_membership_function_rejects_inverted_params(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TriangularMembershipFunction(30.0, 20.0, 10.0);
    }

    // 2. Trapezoidal Membership Function Validation
    public function test_trapezoidal_membership_function_mathematics(): void
    {
        // Trapesium standar [10, 20, 30, 40]
        $trap = new TrapezoidalMembershipFunction(10.0, 20.0, 30.0, 40.0);

        $this->assertEquals(0.0, $trap->calculate(5.0));
        $this->assertEquals(0.0, $trap->calculate(10.0));
        $this->assertEqualsWithDelta(0.5, $trap->calculate(15.0), 1e-6);
        $this->assertEquals(1.0, $trap->calculate(20.0));
        $this->assertEquals(1.0, $trap->calculate(25.0));
        $this->assertEquals(1.0, $trap->calculate(30.0));
        $this->assertEqualsWithDelta(0.5, $trap->calculate(35.0), 1e-6);
        $this->assertEquals(0.0, $trap->calculate(40.0));
        $this->assertEquals(0.0, $trap->calculate(45.0));

        // Left-shoulder [0, 0, 50, 65]
        $leftShoulder = new TrapezoidalMembershipFunction(0.0, 0.0, 50.0, 65.0);
        $this->assertEquals(1.0, $leftShoulder->calculate(-10.0));
        $this->assertEquals(1.0, $leftShoulder->calculate(0.0));
        $this->assertEquals(1.0, $leftShoulder->calculate(50.0));
        $this->assertEqualsWithDelta(0.5, $leftShoulder->calculate(57.5), 1e-6);
        $this->assertEquals(0.0, $leftShoulder->calculate(65.0));
        $this->assertEquals(0.0, $leftShoulder->calculate(70.0));

        // Right-shoulder [65, 80, 100, 100]
        $rightShoulder = new TrapezoidalMembershipFunction(65.0, 80.0, 100.0, 100.0);
        $this->assertEquals(0.0, $rightShoulder->calculate(50.0));
        $this->assertEquals(0.0, $rightShoulder->calculate(65.0));
        $this->assertEqualsWithDelta(0.5, $rightShoulder->calculate(72.5), 1e-6);
        $this->assertEquals(1.0, $rightShoulder->calculate(80.0));
        $this->assertEquals(1.0, $rightShoulder->calculate(90.0));
        $this->assertEquals(1.0, $rightShoulder->calculate(100.0));
        $this->assertEquals(1.0, $rightShoulder->calculate(120.0));
    }

    public function test_trapezoidal_membership_function_rejects_invalid_params(): void
    {
        // Zero width
        $this->expectException(InvalidArgumentException::class);
        new TrapezoidalMembershipFunction(20.0, 20.0, 20.0, 20.0);
    }

    public function test_trapezoidal_membership_function_rejects_dual_shoulder(): void
    {
        // Both left and right shoulder simultaneously
        $this->expectException(InvalidArgumentException::class);
        new TrapezoidalMembershipFunction(10.0, 10.0, 20.0, 20.0);
    }

    // 3. Scenario A: Semua parameter optimal (Centroid verification against Oracle)
    public function test_scenario_a_optimal_parameters(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 27.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('BAIK', $res['category']);
        $this->assertEqualsWithDelta(2412.6667, $res['numerator'], 1e-3);
        $this->assertEqualsWithDelta(28.0000, $res['denominator'], 1e-3);
        $this->assertEqualsWithDelta(86.166667, $res['raw_score'], 1e-4);
        $this->assertEquals(86.17, $res['score']);
    }

    // 4. Scenario B: Suhu tinggi dan kelembapan tanah rendah (Stres Panas)
    public function test_scenario_b_heat_stress_dry_soil(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 36.0,
            'humidity'      => 50.0,
            'soil_moisture' => 30.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('BURUK', $res['category']);
        $this->assertEqualsWithDelta(629.0000, $res['numerator'], 1e-3);
        $this->assertEqualsWithDelta(35.5000, $res['denominator'], 1e-3);
        $this->assertEqualsWithDelta(17.718310, $res['raw_score'], 1e-4);
        $this->assertEquals(17.72, $res['score']);
    }

    // 5. Scenario C: Kelembapan udara tinggi dan tanah jenuh
    public function test_scenario_c_high_humidity_saturated_soil(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 18.0,
            'humidity'      => 90.0,
            'soil_moisture' => 90.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('BURUK', $res['category']);
        $this->assertEqualsWithDelta(1679.0000, $res['numerator'], 1e-3);
        $this->assertEqualsWithDelta(54.2500, $res['denominator'], 1e-3);
        $this->assertEqualsWithDelta(30.949309, $res['raw_score'], 1e-4);
        $this->assertEquals(30.95, $res['score']);
    }

    // 6. Scenario D: pH terlalu asam
    public function test_scenario_d_acidic_ph(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 27.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 4.5,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('BAIK', $res['category']);
        $this->assertEqualsWithDelta(3222.2000, $res['numerator'], 1e-3);
        $this->assertEqualsWithDelta(44.4500, $res['denominator'], 1e-3);
        $this->assertEqualsWithDelta(72.490439, $res['raw_score'], 1e-4);
        $this->assertEquals(72.49, $res['score']);
    }

    // 7. Scenario E: pH terlalu basa
    public function test_scenario_e_alkaline_ph(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 27.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 8.5,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('BAIK', $res['category']);
        $this->assertEqualsWithDelta(3222.2000, $res['numerator'], 1e-3);
        $this->assertEqualsWithDelta(44.4500, $res['denominator'], 1e-3);
        $this->assertEqualsWithDelta(72.490439, $res['raw_score'], 1e-4);
        $this->assertEquals(72.49, $res['score']);
    }

    // 8. Scenario F: Multi-rule overlap dengan konsekuen berbeda
    public function test_scenario_f_multi_rule_overlap(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 30.0,
            'humidity'      => 62.0,
            'soil_moisture' => 62.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('PERLU_PERHATIAN', $res['category']);
        $this->assertEqualsWithDelta(1365.9333, $res['numerator'], 1e-3);
        $this->assertEqualsWithDelta(24.4000, $res['denominator'], 1e-3);
        $this->assertEqualsWithDelta(55.980874, $res['raw_score'], 1e-4);
        $this->assertEquals(55.98, $res['score']);
    }

    // 9. Scenario G: Titik batas tepat membership function
    public function test_scenario_g_exact_boundary_points(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 22.0,
            'humidity'      => 58.0,
            'soil_moisture' => 58.0,
            'ph'            => 5.8,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('BURUK', $res['category']);
        $this->assertEqualsWithDelta(337.2000, $res['numerator'], 1e-3);
        $this->assertEqualsWithDelta(16.6000, $res['denominator'], 1e-3);
        $this->assertEquals(20.31, $res['score']);
    }

    // 10. Scenario H: Overlap dua himpunan
    public function test_scenario_h_two_sets_overlap(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 23.0,
            'humidity'      => 60.0,
            'soil_moisture' => 60.0,
            'ph'            => 6.0,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('BAIK', $res['category']);
        $this->assertEqualsWithDelta(789.8667, $res['numerator'], 1e-3);
        $this->assertEqualsWithDelta(11.7333, $res['denominator'], 1e-3);
        $this->assertEquals(67.32, $res['score']);
    }

    // 11. Scenario I: Tidak ada aturan aktif
    public function test_scenario_i_insufficient_rule_coverage(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 15.0,
            'humidity'      => 40.0,
            'soil_moisture' => 90.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('INSUFFICIENT_RULE_COVERAGE', $res['status']);
        $this->assertNull($res['score']);
        $this->assertNull($res['category']);
        $this->assertEquals(0.0, $res['numerator']);
        $this->assertEquals(0.0, $res['denominator']);
    }

    // 12. Scenario J: Missing / null sensor
    public function test_scenario_j_missing_sensor(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => null,
            'humidity'      => null,
            'soil_moisture' => 70.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('INSUFFICIENT_RULE_COVERAGE', $res['status']);
        $this->assertNull($res['score']);
    }

    // 13. Scenario K: Nilai input di luar domain fisik normal
    public function test_scenario_k_extreme_out_of_domain(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 55.0,
            'humidity'      => -5.0,
            'soil_moisture' => 110.0,
            'ph'            => 15.0,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('BURUK', $res['category']);
        $this->assertEquals(17.72, $res['score']);
    }

    // 14. Scenario L: Hanya satu aturan aktif (R10)
    public function test_scenario_l_single_rule_active(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 35.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 6.5,
        ]);

        $this->assertEquals('AVAILABLE', $res['status']);
        $this->assertEquals('PERLU_PERHATIAN', $res['category']);
        $this->assertCount(1, $res['activated_rules']);
        $this->assertEquals('R10', $res['activated_rules'][0]['id']);
        $this->assertEqualsWithDelta(1100.0, $res['numerator'], 1e-3);
        $this->assertEqualsWithDelta(20.0, $res['denominator'], 1e-3);
        $this->assertEquals(55.00, $res['score']);
    }

    // 15. Scenario M: Beberapa aturan aktif dengan konsekuen sama (R01 dan R16)
    public function test_scenario_m_multiple_rules_same_consequent(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 27.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 6.5,
        ]);

        $this->assertCount(2, $res['activated_rules']);
        $this->assertEquals('baik', $res['activated_rules'][0]['consequent']);
        $this->assertEquals('baik', $res['activated_rules'][1]['consequent']);
        $this->assertEquals(86.17, $res['score']);
    }

    // 16. Scenario N: Beberapa aturan aktif dengan konsekuen berbeda (R04 dan R15)
    public function test_scenario_n_multiple_rules_different_consequents(): void
    {
        $res = $this->inferenceService->infer([
            'temperature'   => 18.0,
            'humidity'      => 90.0,
            'soil_moisture' => 90.0,
            'ph'            => 6.5,
        ]);

        $this->assertCount(2, $res['activated_rules']);
        $consequents = array_column($res['activated_rules'], 'consequent');
        $this->assertContains('perlu_perhatian', $consequents);
        $this->assertContains('buruk', $consequents);
        $this->assertEquals(30.95, $res['score']);
    }

    // 17. Scenario O: Output membership left-shoulder vs right-shoulder
    public function test_scenario_o_pure_shoulders(): void
    {
        // Left-shoulder murni (Buruk)
        $left = $this->inferenceService->infer([
            'temperature'   => 35.0,
            'humidity'      => 45.0,
            'soil_moisture' => 40.0,
            'ph'            => 6.5,
        ]);
        $this->assertEquals('BURUK', $left['category']);
        $this->assertEquals(17.72, $left['score']);

        // Right-shoulder murni (Baik)
        $right = $this->inferenceService->infer([
            'temperature'   => 27.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 6.5,
        ]);
        $this->assertEquals('BAIK', $right['category']);
        $this->assertEquals(86.17, $right['score']);
    }

    // 18. Scenario P: Resolusi sampling berbeda (step = 0.5 dan step = 0.1)
    public function test_scenario_p_sampling_resolution_sensitivity(): void
    {
        $inputs = [
            'temperature'   => 27.0,
            'humidity'      => 70.0,
            'soil_moisture' => 70.0,
            'ph'            => 6.5,
        ];

        // Default step = 1.0
        $resStep1 = $this->inferenceService->infer($inputs, 1.0);
        $this->assertEquals(86.17, $resStep1['score']);
        $this->assertEqualsWithDelta(2412.6667, $resStep1['numerator'], 1e-3);
        $this->assertEqualsWithDelta(28.0000, $resStep1['denominator'], 1e-3);

        // Step = 0.5
        $resStep05 = $this->inferenceService->infer($inputs, 0.5);
        $this->assertEquals(86.04, $resStep05['score']);
        $this->assertEqualsWithDelta(4775.0833, $resStep05['numerator'], 1e-3);
        $this->assertEqualsWithDelta(55.5000, $resStep05['denominator'], 1e-3);

        // Step = 0.1
        $resStep01 = $this->inferenceService->infer($inputs, 0.1);
        $this->assertEquals(85.93, $resStep01['score']);
        $this->assertEqualsWithDelta(23675.0167, $resStep01['numerator'], 1e-3);
        $this->assertEqualsWithDelta(275.5000, $resStep01['denominator'], 1e-3);
    }
}
