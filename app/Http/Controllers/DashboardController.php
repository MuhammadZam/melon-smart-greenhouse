<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\MonitoringParameter;
use App\Models\Plant;
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
        $totalMonitorings = Monitoring::count();
        $todayMonitoringCount = Monitoring::whereDate('monitored_at', now()->toDateString())->count();

        // Data pengamatan terakhir
        $latestMonitoring = Monitoring::with('plant')
            ->latest('monitored_at')
            ->first();

        // 5. Agregasi Statistik Parameter (AVG, MIN, MAX dari database aktual)
        $aggregations = (clone $monitoringQuery)->selectRaw('
            AVG(temperature) as avg_temp,
            MIN(temperature) as min_temp,
            MAX(temperature) as max_temp,
            AVG(ph) as avg_ph,
            MIN(ph) as min_ph,
            MAX(ph) as max_ph,
            AVG(humidity) as avg_humidity,
            MIN(humidity) as min_humidity,
            MAX(humidity) as max_humidity,
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

        $chartLabels     = $chartDataQuery->map(fn($m) => $m->monitored_at->format('d M H:i'))->values()->all();
        $chartTemps      = $chartDataQuery->pluck('temperature')->values()->all();
        $chartPh         = $chartDataQuery->pluck('ph')->values()->all();
        $chartHumidities = $chartDataQuery->pluck('humidity')->values()->all();
        $chartHeights    = $chartDataQuery->pluck('plant_height')->values()->all();
        $chartDensities  = $chartDataQuery->pluck('plant_density')->values()->all();

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
                'min' => (float) ($paramKeyed['Suhu Udara']->minimum_value ?? 20.0),
                'max' => (float) ($paramKeyed['Suhu Udara']->maximum_value ?? 32.0),
                'unit' => '°C',
            ],
            'ph' => [
                'min' => (float) ($paramKeyed['pH Nutrisi/Media']->minimum_value ?? 5.8),
                'max' => (float) ($paramKeyed['pH Nutrisi/Media']->maximum_value ?? 7.2),
                'unit' => 'pH',
            ],
            'humidity' => [
                'min' => (float) ($paramKeyed['Kelembapan Udara']->minimum_value ?? 60.0),
                'max' => (float) ($paramKeyed['Kelembapan Udara']->maximum_value ?? 85.0),
                'unit' => '%',
            ],
            'height' => [
                'min' => (float) ($paramKeyed['Tinggi Tanaman']->minimum_value ?? 10.0),
                'max' => (float) ($paramKeyed['Tinggi Tanaman']->maximum_value ?? 150.0),
                'unit' => 'cm',
            ],
            'density' => [
                'min' => (float) ($paramKeyed['Kerapatan Kanopi']->minimum_value ?? 40.0),
                'max' => (float) ($paramKeyed['Kerapatan Kanopi']->maximum_value ?? 90.0),
                'unit' => '%',
            ],
        ];

        // 11. Tabel Monitoring Terkini (Maksimal 8 rekaman terakhir dari query filter)
        $recentMonitorings = (clone $monitoringQuery)
            ->latest('monitored_at')
            ->take(8)
            ->get();

        $filteredCount = (clone $monitoringQuery)->count();

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
            'chartPh',
            'chartHumidities',
            'chartHeights',
            'chartDensities',
            'conditionLabels',
            'conditionCounts',
            'frequencyLabels',
            'frequencyCounts',
            'statusLabels',
            'statusCounts'
        ));
    }
}
