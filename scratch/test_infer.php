<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$svc = app(App\Services\FuzzyLogic\MamdaniInferenceService::class);
$res = $svc->infer([
    'temperature'   => 35.0,
    'humidity'      => 45.0,
    'soil_moisture' => 40.0,
    'ph'            => 6.5,
]);

echo "Status: " . $res['status'] . "\n";
echo "Score: " . $res['score'] . "\n";
echo "Category: " . $res['category'] . "\n";

// Let's compute numerator and denominator manually from aggregated curve
$num = 0.0;
$den = 0.0;
foreach ($res['aggregated_curve'] as $z => $mu) {
    if ($mu > 0) {
        $zVal = (float)$z;
        $num += $zVal * $mu;
        $den += $mu;
        // echo "z=$z: mu=$mu (z*mu=" . ($zVal * $mu) . ")\n";
    }
}
echo "Calculated Num: $num\n";
echo "Calculated Den: $den\n";
echo "Defuzzified Score (Num/Den): " . ($num / $den) . "\n";
