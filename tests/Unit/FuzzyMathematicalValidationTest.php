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

    public function test_three_input_centroids_against_independent_discrete_sums(): void
    {
        // Hand sums over integer z=0..100: full good/right shoulder, bad/left shoulder, symmetric middle.
        foreach ([[27,70,6.5,2412.6666666667,28,86.17], [36,30,6.5,629,35.5,17.72], [35,70,6.5,1100,20,55]] as [$temp,$soil,$ph,$numerator,$denominator,$score]) {
            $res = $this->inferenceService->infer(['temperature'=>$temp,'soil_moisture'=>$soil,'water_ph'=>$ph]);
            $this->assertSame('AVAILABLE', $res['status']);
            $this->assertEqualsWithDelta($numerator, $res['numerator'], 0.0001);
            $this->assertEqualsWithDelta($denominator, $res['denominator'], 0.0001);
            $this->assertEquals($score, $res['score']);
        }
    }

    public function test_humidity_cannot_change_three_input_inference(): void
    {
        $input = ['temperature'=>27,'soil_moisture'=>70,'water_ph'=>6.5];
        $a = $this->inferenceService->infer($input);
        foreach ([0,50,100,null] as $humidity) {
            $b = $this->inferenceService->infer($input + ['humidity'=>$humidity]);
            $this->assertEquals($a, $b);
        }
        $this->assertCount(1, $a['activated_rules']);
        $this->assertSame('T3-R01', $a['activated_rules'][0]['id']);
    }

    public function test_removed_rule_combination_reports_insufficient_coverage(): void
    {
        $res = $this->inferenceService->infer(['temperature'=>18,'soil_moisture'=>90,'water_ph'=>6.5]);
        $this->assertSame('INSUFFICIENT_RULE_COVERAGE', $res['status']);
        $this->assertNull($res['score']);
    }

    public function test_all_rules_reference_only_available_memberships(): void
    {
        $this->assertSame(['temperature','soil_moisture','water_ph'], array_keys(config('fuzzy.inputs')));
        foreach (config('fuzzy.rules') as $rule) {
            foreach ($rule['antecedents'] as $key=>$set) $this->assertArrayHasKey($set, config('fuzzy.inputs.'.$key.'.sets'));
            $this->assertStringContainsString('PROVISIONAL', $rule['conclusion']);
        }
        $this->assertCount(8, config('fuzzy.rules'));
    }
}
