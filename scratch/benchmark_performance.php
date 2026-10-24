<?php

require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Monitoring;
use App\Models\Plant;
use App\Models\User;
use App\Services\Reports\MonitoringReportService;
use App\Services\Reports\MonitoringReportExportService;
use Illuminate\Support\Facades\DB;

$admin = User::where('role', 'admin')->first();
auth()->login($admin);

$monitoringCount = Monitoring::count();
$plantCount = Plant::count();

echo "=== PERFORMANCE BENCHMARK SUITE (PHASE 16.1) ===\n";
echo "Dataset Size: {$plantCount} Plants, {$monitoringCount} Monitoring Records\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n\n";

// 1. Dashboard Controller Execution Time & Query Count
DB::enableQueryLog();
$startMem = memory_get_usage();
$t0 = microtime(true);

$dashboardController = app(\App\Http\Controllers\DashboardController::class);
$request = new \Illuminate\Http\Request();
$response = $dashboardController->index($request);

$t1 = microtime(true);
$dashboardQueries = count(DB::getQueryLog());
DB::disableQueryLog();
$dashboardDurationMs = ($t1 - $t0) * 1000;
$dashboardPeakMemMb = (memory_get_peak_usage() - $startMem) / (1024 * 1024);

echo "1. Dashboard Index:\n";
echo "   Duration: " . number_format($dashboardDurationMs, 2) . " ms\n";
echo "   Queries: {$dashboardQueries} queries\n";
echo "   Peak Memory Delta: " . number_format($dashboardPeakMemMb, 2) . " MB\n\n";

// 2. Monitoring Index Execution Time
DB::flushQueryLog();
DB::enableQueryLog();
$startMem = memory_get_usage();
$t0 = microtime(true);

$monitoringController = app(\App\Http\Controllers\MonitoringController::class);
$request = new \Illuminate\Http\Request(['page' => 1]);
$response = $monitoringController->index($request);

$t1 = microtime(true);
$monitoringQueries = count(DB::getQueryLog());
DB::disableQueryLog();
$monitoringDurationMs = ($t1 - $t0) * 1000;

echo "2. Monitoring List (Page 1):\n";
echo "   Duration: " . number_format($monitoringDurationMs, 2) . " ms\n";
echo "   Queries: {$monitoringQueries} queries\n\n";

// 3. Report Service Aggregation & Query
DB::flushQueryLog();
DB::enableQueryLog();
$t0 = microtime(true);

$reportService = app(MonitoringReportService::class);
$reportData = $reportService->getReportData([], 15);

$t1 = microtime(true);
$reportQueries = count(DB::getQueryLog());
DB::disableQueryLog();
$reportDurationMs = ($t1 - $t0) * 1000;

echo "3. Report Data Aggregation (8 Sensors + Alarms + Fuzzy):\n";
echo "   Duration: " . number_format($reportDurationMs, 2) . " ms\n";
echo "   Queries: {$reportQueries} queries\n\n";

// 4. CSV Export Generation Time
$t0 = microtime(true);
$exportService = app(MonitoringReportExportService::class);
$csvResponse = $exportService->exportCsv([]);

ob_start();
$csvResponse->sendContent();
$csvOutput = ob_get_clean();
$t1 = microtime(true);
$csvDurationMs = ($t1 - $t0) * 1000;
$csvSizeKb = strlen($csvOutput) / 1024;

echo "4. CSV Export Streaming:\n";
echo "   Duration: " . number_format($csvDurationMs, 2) . " ms\n";
echo "   Output Size: " . number_format($csvSizeKb, 2) . " KB\n\n";

// 5. PDF Generation Time (500 records)
$t0 = microtime(true);
$pdfResponse = $exportService->exportPdf([]);
$pdfContent = $pdfResponse->getContent();
$t1 = microtime(true);
$pdfDurationMs = ($t1 - $t0) * 1000;
$pdfSizeKb = strlen($pdfContent) / 1024;

echo "5. PDF Generation (DomPDF A4 Landscape with 500 records):\n";
echo "   Duration: " . number_format($pdfDurationMs, 2) . " ms\n";
echo "   Output Size: " . number_format($pdfSizeKb, 2) . " KB\n\n";

echo "=== BENCHMARK COMPLETED SUCCESSFULLY ===\n";
