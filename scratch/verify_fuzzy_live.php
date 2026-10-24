<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\FuzzyLogic\MamdaniInferenceService;
use App\Services\FuzzyLogic\FuzzyConclusionService;

$engine = app(MamdaniInferenceService::class);
$conclusionService = app(FuzzyConclusionService::class);

// 1. Baseline Test
$input = [
    'temperature' => 27.0,
    'humidity' => 70.0,
    'soil_moisture' => 70.0,
    'ph' => 6.5
];

$result = $engine->infer($input);
$conclusion = $conclusionService->generate($result);

echo "=== FUZZY MAMDANI ENGINE LIVE VERIFICATION ===\n";
echo "Input: Temp=27, Hum=70, Soil=70, pH=6.5\n";
echo "Score: " . number_format($result['score'], 4) . "\n";
echo "Category: " . $result['category'] . "\n";
echo "Status: " . $result['status'] . "\n";
echo "Activated Rules: " . count($result['activated_rules']) . "\n";
echo "Conclusion: " . $conclusion['conclusion'] . "\n";
echo "Recommendation: " . $conclusion['recommendation'] . "\n";

// 2. Extreme Heat Stress Test
$extremeInput = [
    'temperature' => 38.0,
    'humidity' => 40.0,
    'soil_moisture' => 35.0,
    'ph' => 8.2
];
$extremeResult = $engine->infer($extremeInput);
echo "\n=== EXTREME HEAT STRESS VERIFICATION ===\n";
echo "Input: Temp=38, Hum=40, Soil=35, pH=8.2\n";
echo "Score: " . number_format($extremeResult['score'], 4) . "\n";
echo "Category: " . $extremeResult['category'] . "\n";

// 3. Insufficient Coverage / Edge Case
$coldInput = [
    'temperature' => 10.0,
    'humidity' => 20.0,
    'soil_moisture' => 20.0,
    'ph' => 4.0
];
$coldResult = $engine->infer($coldInput);
echo "\n=== EXTREME COLD/ACID STRESS VERIFICATION ===\n";
echo "Score: " . ($coldResult['score'] !== null ? number_format($coldResult['score'], 4) : 'NULL') . "\n";
echo "Category: " . ($coldResult['category'] ?? 'NONE') . "\n";
echo "Status: " . $coldResult['status'] . "\n";
