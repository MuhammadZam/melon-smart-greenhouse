<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$inferenceService = app(App\Services\FuzzyLogic\MamdaniInferenceService::class);
$oracleData = json_decode(file_get_contents(__DIR__ . '/oracle_scenarios.json'), true);

echo "========================================================================================\n";
echo "COMPARISON: PHP MAMZANI INFERENCE ENGINE VS PYTHON INDEPENDENT ORACLE (PHASE 15.1)\n";
echo "========================================================================================\n";

$passCount = 0;
$totalCount = count($oracleData);

printf("%-20s | %-12s | %-12s | %-14s | %-14s | %-6s\n", "Scenario", "Oracle Score", "PHP Score", "Oracle Num", "PHP Num", "Result");
echo str_repeat("-", 88) . "\n";

foreach ($oracleData as $scKey => $expected) {
    $inputs = $expected['inputs'];
    $step = (float)($expected['step'] ?? 1.0);

    $phpResult = $inferenceService->infer($inputs, $step);

    $statusMatch = ($phpResult['status'] === $expected['status']);
    
    if ($expected['score'] === null) {
        $scoreMatch = ($phpResult['score'] === null);
        $numDiff = 0.0;
        $denDiff = 0.0;
        $scoreDiff = 0.0;
    } else {
        $scoreDiff = abs($phpResult['score'] - $expected['score']);
        $rawScoreDiff = abs($phpResult['raw_score'] - $expected['raw_score']);
        $numDiff = abs($phpResult['numerator'] - $expected['numerator']);
        $denDiff = abs($phpResult['denominator'] - $expected['denominator']);
        $scoreMatch = ($scoreDiff < 1e-4) && ($rawScoreDiff < 1e-4);
    }

    $catMatch = ($phpResult['category'] === $expected['category']);

    $passed = $statusMatch && $scoreMatch && $catMatch && ($numDiff < 1e-3) && ($denDiff < 1e-3);
    if ($passed) {
        $passCount++;
        $statusStr = "PASS";
    } else {
        $statusStr = "FAIL";
    }

    $oracleScoreStr = $expected['score'] !== null ? sprintf("%.2f", $expected['score']) : "NULL";
    $phpScoreStr = $phpResult['score'] !== null ? sprintf("%.2f", $phpResult['score']) : "NULL";
    $oracleNumStr = $expected['numerator'] !== null ? sprintf("%.2f", $expected['numerator']) : "0.00";
    $phpNumStr = sprintf("%.2f", $phpResult['numerator']);

    printf("%-20s | %-12s | %-12s | %-14s | %-14s | %-6s\n", $scKey, $oracleScoreStr, $phpScoreStr, $oracleNumStr, $phpNumStr, $statusStr);

    if (!$passed) {
        echo "  [FAIL DETAIL] Status match: " . ($statusMatch ? "yes" : "no ({$phpResult['status']} vs {$expected['status']})") . "\n";
        echo "  [FAIL DETAIL] Score diff: {$scoreDiff}, Num diff: {$numDiff}, Den diff: {$denDiff}\n";
    }
}

echo str_repeat("=", 88) . "\n";
echo "SUMMARY: {$passCount} / {$totalCount} Scenarios MATCH INDEPENDENT ORACLE EXACTLY!\n";
echo "========================================================================================\n";
