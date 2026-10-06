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
     * Menampilkan dashboard utama monitoring Melon dengan data aktual dari database.
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Ambil daftar tanaman untuk filter dropdown
        $plants = Plant::orderBy('name')->get();
        $selectedPlantId = $request->query('plant_id', 'all');

        // 2. Summary Card: Total Tanaman & Total Monitoring
        $totalPlants      = Plant::count();
        $totalMonitorings = Monitoring::count();

        // 3. Summary Card: Data Pengamatan Terakhir
        $latestMonitoring = Monitoring::with('plant')
            ->latest('monitored_at')
            ->first();

        // 4. Summary Card: Status Tanaman (Active, Harvested, Inactive)
        $rawStatusCounts = Plant::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $plantStatusCounts = [
            'active'    => $rawStatusCounts['active'] ?? 0,
            'harvested' => $rawStatusCounts['harvested'] ?? 0,
            'inactive'  => $rawStatusCounts['inactive'] ?? 0,
        ];

        // 5. Query data untuk grafik Chart.js (Data aktual dari MySQL)
        $chartDataQuery = Monitoring::with('plant')
            ->when($selectedPlantId !== 'all' && is_numeric($selectedPlantId), function ($q) use ($selectedPlantId) {
                return $q->where('plant_id', $selectedPlantId);
            })
            ->orderBy('monitored_at', 'asc')
            ->take(15)
            ->get();

        $chartLabels     = $chartDataQuery->map(fn($m) => $m->monitored_at->format('d M H:i'))->values()->all();
        $chartTemps      = $chartDataQuery->pluck('temperature')->values()->all();
        $chartPh         = $chartDataQuery->pluck('ph')->values()->all();
        $chartHumidities = $chartDataQuery->pluck('humidity')->values()->all();

        // 6. Tabel Monitoring Terbaru (5-10 data terakhir, terurut monitored_at DESC)
        $recentMonitorings = Monitoring::with('plant')
            ->when($selectedPlantId !== 'all' && is_numeric($selectedPlantId), function ($q) use ($selectedPlantId) {
                return $q->where('plant_id', $selectedPlantId);
            })
            ->latest('monitored_at')
            ->take(8)
            ->get();

        // 7. Parameter Thresholds
        $parameters = MonitoringParameter::all();

        return view('dashboard.index', compact(
            'user',
            'plants',
            'selectedPlantId',
            'totalPlants',
            'totalMonitorings',
            'latestMonitoring',
            'plantStatusCounts',
            'recentMonitorings',
            'parameters',
            'chartLabels',
            'chartTemps',
            'chartPh',
            'chartHumidities'
        ));
    }
}
