<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\MonitoringAlarm;
use App\Models\MonitoringParameter;
use App\Models\Plant;
use App\Services\MonitoringAlarmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard utama monitoring Melon dengan visualisasi tingkat lanjut dan analitik aktual dari database.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Validasi input filter
        $request->validate([
            'plant_id'  => ['nullable', 'string', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== 'all' && !Plant::where('id', $value)->exists()) {
                    $fail('Tanaman yang dipilih tidak valid.');
                }
            }],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
            'range'     => ['nullable', 'string', 'in:today,7d,30d,all'],
        ]);

        $selectedPlantId = $request->query('plant_id', 'all');
        $dateFrom        = $request->query('date_from');
        $dateTo          = $request->query('date_to');
        $selectedRange   = $request->query('range', 'all');

        // 2. Evaluasi Quick Range jika dipilih pengguna
        if ($selectedRange === 'today') {
            $dateFrom = now()->startOfDay()->format('Y-m-d');
            $dateTo   = now()->endOfDay()->format('Y-m-d');
        } elseif ($selectedRange === '7d') {
            $dateFrom = now()->subDays(6)->startOfDay()->format('Y-m-d');
            $dateTo   = now()->endOfDay()->format('Y-m-d');
        } elseif ($selectedRange === '30d') {
            $dateFrom = now()->subDays(29)->startOfDay()->format('Y-m-d');
            $dateTo   = now()->endOfDay()->format('Y-m-d');
        }

        // 3. Query dasar monitoring dengan filter tanaman dan rentang tanggal
        $monitoringQuery = Monitoring::with('plant')
            ->when($selectedPlantId !== 'all' && is_numeric($selectedPlantId), function ($q) use ($selectedPlantId) {
                return $q->where('plant_id', $selectedPlantId);
            })
            ->when($dateFrom, function ($q) use ($dateFrom) {
                return $q->whereDate('monitored_at', '>=', $dateFrom);
            })
            ->when($dateTo, function ($q) use ($dateTo) {
                return $q->whereDate('monitored_at', '<=', $dateTo);
            });

        // 4. Master Data & Summary Cards Global
        $plants           = Plant::orderBy('name')->get();
        $totalPlants      = Plant::count();
        $activePlantsCount = Plant::where('status', 'active')->count();
        $totalMonitorings = Monitoring::count();
        $todayMonitoringCount = Monitoring::whereDate('monitored_at', now()->toDateString())->count();

        // Data pengamatan terakhir
        $latestMonitoring = Monitoring::with('plant')
            ->latest('monitored_at')
            ->first();

        // Distribusi sumber data untuk Section A
        $sourceDistribution = Monitoring::selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        // Phase 16: Section G — Status Integrasi Google Sheets & Kesegaran Telemetri
        $googleSheetsConfig = app(\App\Services\GoogleSheets\GoogleSheetsSyncService::class)->getOrCreateDefaultConfig();
        $latestSyncLog = \App\Models\GoogleSheetsSyncLog::latest('created_at')->first();
        $latestTelemetryMonitoring = Monitoring::where('source', 'google_sheets')->latest('monitored_at')->first();

        // 5. Agregasi Statistik Parameter (AVG, MIN, MAX dari database aktual)
        $aggregations = (clone $monitoringQuery)->selectRaw('
            AVG(temperature) as avg_temp,
            MIN(temperature) as min_temp,
            MAX(temperature) as max_temp,
            AVG(nutrient_temperature) as avg_nutrient_temp,
            MIN(nutrient_temperature) as min_nutrient_temp,
            MAX(nutrient_temperature) as max_nutrient_temp,
            AVG(ph) as avg_ph,
            MIN(ph) as min_ph,
            MAX(ph) as max_ph,
            AVG(ec) as avg_ec,
            MIN(ec) as min_ec,
            MAX(ec) as max_ec,
            AVG(humidity) as avg_humidity,
            MIN(humidity) as min_humidity,
            MAX(humidity) as max_humidity,
            AVG(soil_moisture) as avg_soil_moisture,
            MIN(soil_moisture) as min_soil_moisture,
            MAX(soil_moisture) as max_soil_moisture,
            AVG(light_intensity) as avg_light,
            MIN(light_intensity) as min_light,
            MAX(light_intensity) as max_light,
            AVG(tank_level) as avg_tank_level,
            MIN(tank_level) as min_tank_level,
            MAX(tank_level) as max_tank_level,
            AVG(plant_height) as avg_height,
            MIN(plant_height) as min_height,
            MAX(plant_height) as max_height,
            AVG(plant_density) as avg_density,
            MIN(plant_density) as min_density,
            MAX(plant_density) as max_density
        ')->first();

        // 6. Query data untuk grafik tren Chart.js (Data aktual dari database, diurutkan kronologis)
        $chartDataQuery = (clone $monitoringQuery)
            ->orderBy('monitored_at', 'asc')
            ->take(30)
            ->get();

        $chartLabels        = $chartDataQuery->map(fn($m) => $m->monitored_at->format('d M H:i'))->values()->all();
        $chartTemps         = $chartDataQuery->pluck('temperature')->values()->all();
        $chartNutrientTemps = $chartDataQuery->pluck('nutrient_temperature')->values()->all();
        $chartPh            = $chartDataQuery->pluck('ph')->values()->all();
        $chartEc            = $chartDataQuery->pluck('ec')->values()->all();
        $chartHumidities    = $chartDataQuery->pluck('humidity')->values()->all();
        $chartSoilMoistures = $chartDataQuery->pluck('soil_moisture')->values()->all();
        $chartLights        = $chartDataQuery->pluck('light_intensity')->values()->all();
        $chartTankLevels    = $chartDataQuery->pluck('tank_level')->values()->all();
        $chartHeights       = $chartDataQuery->pluck('plant_height')->values()->all();
        $chartDensities     = $chartDataQuery->pluck('plant_density')->values()->all();

        // 7. Visualisasi Distribusi: Kondisi Fisik Tanaman (Doughnut Chart)
        $rawConditionCounts = (clone $monitoringQuery)
            ->selectRaw('plant_condition, count(*) as count')
            ->groupBy('plant_condition')
            ->pluck('count', 'plant_condition')
            ->toArray();

        $conditionLabels = array_keys($rawConditionCounts);
        $conditionCounts = array_values($rawConditionCounts);

        // 8. Visualisasi Frekuensi: Jumlah Monitoring per Tanaman (Bar Chart)
        $plantsWithFrequency = Plant::withCount('monitorings')->orderBy('name')->get();
        $frequencyLabels     = $plantsWithFrequency->pluck('name')->values()->all();
        $frequencyCounts     = $plantsWithFrequency->pluck('monitorings_count')->values()->all();

        // 9. Visualisasi Status Budidaya Tanaman (Doughnut Chart dari tabel plants)
        $rawStatusCounts = Plant::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $plantStatusCounts = [
            'active'    => $rawStatusCounts['active'] ?? 0,
            'harvested' => $rawStatusCounts['harvested'] ?? 0,
            'inactive'  => $rawStatusCounts['inactive'] ?? 0,
        ];

        $statusLabels = ['Aktif', 'Sudah Dipanen', 'Tidak Aktif'];
        $statusCounts = [
            $plantStatusCounts['active'],
            $plantStatusCounts['harvested'],
            $plantStatusCounts['inactive'],
        ];

        // 10. Nilai Ambang Batas Terkonfigurasi (Configured Monitoring Thresholds)
        $parameters = MonitoringParameter::all();
        $paramKeyed = $parameters->keyBy('parameter_name');

        $thresholds = [
            'temperature' => [
                'min'  => (float) ($paramKeyed['Suhu Udara']->minimum_value ?? 20.0),
                'max'  => (float) ($paramKeyed['Suhu Udara']->maximum_value ?? 32.0),
                'unit' => '°C',
            ],
            'ph' => [
                'min'  => (float) ($paramKeyed['pH Nutrisi/Media']->minimum_value ?? 5.8),
                'max'  => (float) ($paramKeyed['pH Nutrisi/Media']->maximum_value ?? 7.2),
                'unit' => 'pH',
            ],
            'humidity' => [
                'min'  => (float) ($paramKeyed['Kelembapan Udara']->minimum_value ?? 60.0),
                'max'  => (float) ($paramKeyed['Kelembapan Udara']->maximum_value ?? 85.0),
                'unit' => '%',
            ],
            'height' => [
                'min'  => (float) ($paramKeyed['Tinggi Tanaman']->minimum_value ?? 10.0),
                'max'  => (float) ($paramKeyed['Tinggi Tanaman']->maximum_value ?? 150.0),
                'unit' => 'cm',
            ],
            'density' => [
                'min'  => (float) ($paramKeyed['Kerapatan Kanopi']->minimum_value ?? 40.0),
                'max'  => (float) ($paramKeyed['Kerapatan Kanopi']->maximum_value ?? 90.0),
                'unit' => '%',
            ],
            'soil_moisture' => [
                'min'  => (float) ($paramKeyed['Kelembapan Tanah']->minimum_value ?? 60.0),
                'max'  => (float) ($paramKeyed['Kelembapan Tanah']->maximum_value ?? 80.0),
                'unit' => '%',
            ],
            'ec' => [
                'min'  => (float) ($paramKeyed['Konduktivitas Listrik (EC)']->minimum_value ?? 1.8),
                'max'  => (float) ($paramKeyed['Konduktivitas Listrik (EC)']->maximum_value ?? 2.8),
                'unit' => 'mS/cm',
            ],
            'light_intensity' => [
                'min'  => (float) ($paramKeyed['Intensitas Cahaya']->minimum_value ?? 15000.0),
                'max'  => (float) ($paramKeyed['Intensitas Cahaya']->maximum_value ?? 50000.0),
                'unit' => 'lux',
            ],
            'nutrient_temperature' => [
                'min'  => (float) ($paramKeyed['Suhu Nutrisi']->minimum_value ?? 20.0),
                'max'  => (float) ($paramKeyed['Suhu Nutrisi']->maximum_value ?? 26.0),
                'unit' => '°C',
            ],
            'tank_level' => [
                'min'  => (float) ($paramKeyed['Level Tandon Nutrisi']->minimum_value ?? 40.0),
                'max'  => (float) ($paramKeyed['Level Tandon Nutrisi']->maximum_value ?? 95.0),
                'unit' => '%',
            ],
        ];

        // 11. Tabel Monitoring Terkini (Maksimal 8 rekaman terakhir dari query filter)
        $recentMonitorings = (clone $monitoringQuery)
            ->latest('monitored_at')
            ->take(8)
            ->get();

        $filteredCount = (clone $monitoringQuery)->count();

        // 12. Phase 12: Ringkasan Alarm untuk Dashboard
        $alarmService  = app(MonitoringAlarmService::class);
        $alarmSummary  = $alarmService->getDashboardSummary();
        $activeAlarms  = $alarmSummary['active'];
        $criticalAlarms = $alarmSummary['critical'];
        $resolvedToday  = $alarmSummary['resolvedToday'];
        $latestAlarms   = $alarmSummary['latest'];

        // 13. Phase 13: Ringkasan Aktuator (Simulasi) untuk Dashboard
        $actuatorService = app(\App\Services\ActuatorSimulationService::class);
        $actuatorSummary = $actuatorService->getDashboardSummary();

        // 14. Phase 15: Ringkasan Analisis Kondisi Greenhouse (Fuzzy Logic Mamdani)
        $latestFuzzyQuery = \App\Models\FuzzyInferenceResult::with(['plant', 'monitoring'])
            ->when($selectedPlantId !== 'all' && is_numeric($selectedPlantId), function ($q) use ($selectedPlantId) {
                return $q->where('plant_id', $selectedPlantId);
            })
            ->latest('inferred_at')
            ->latest('id');

        $latestFuzzy = $latestFuzzyQuery->first();

        $recentFuzzyScores = (clone $latestFuzzyQuery)
            ->whereNotNull('fuzzy_score')
            ->take(10)
            ->get()
            ->reverse()
            ->values();

        return view('dashboard.index', compact(
            'user',
            'plants',
            'selectedPlantId',
            'dateFrom',
            'dateTo',
            'selectedRange',
            'totalPlants',
            'totalMonitorings',
            'todayMonitoringCount',
            'filteredCount',
            'latestMonitoring',
            'aggregations',
            'plantStatusCounts',
            'recentMonitorings',
            'parameters',
            'thresholds',
            'chartLabels',
            'chartTemps',
            'chartNutrientTemps',
            'chartPh',
            'chartEc',
            'chartHumidities',
            'chartSoilMoistures',
            'chartLights',
            'chartTankLevels',
            'chartHeights',
            'chartDensities',
            'conditionLabels',
            'conditionCounts',
            'frequencyLabels',
            'frequencyCounts',
            'statusLabels',
            'statusCounts',
            'activeAlarms',
            'criticalAlarms',
            'resolvedToday',
            'latestAlarms',
            'actuatorSummary',
            'latestFuzzy',
            'recentFuzzyScores',
            'activePlantsCount',
            'sourceDistribution',
            'googleSheetsConfig',
            'latestSyncLog',
            'latestTelemetryMonitoring',
        ));
    }
}
