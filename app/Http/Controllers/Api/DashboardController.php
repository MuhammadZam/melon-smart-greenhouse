<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MonitoringResource;
use App\Models\Monitoring;
use App\Models\Plant;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Mengambil ringkasan metrik dashboard monitoring tanaman melon dari database.
     */
    public function summary(): JsonResponse
    {
        $totalPlants     = Plant::count();
        $activePlants    = Plant::where('status', 'active')->count();
        $harvestedPlants = Plant::where('status', 'harvested')->count();
        $inactivePlants  = Plant::where('status', 'inactive')->count();

        $totalMonitorings = Monitoring::count();
        $todayMonitorings = Monitoring::whereDate('monitored_at', now()->toDateString())->count();

        $latestMonitoring = Monitoring::with('plant')
            ->latest('monitored_at')
            ->first();

        $rawConditionCounts = Monitoring::selectRaw('plant_condition, count(*) as count')
            ->groupBy('plant_condition')
            ->pluck('count', 'plant_condition')
            ->toArray();

        $data = [
            'total_plants'            => $totalPlants,
            'active_plants'           => $activePlants,
            'harvested_plants'        => $harvestedPlants,
            'inactive_plants'         => $inactivePlants,
            'total_monitorings'       => $totalMonitorings,
            'today_monitorings'       => $todayMonitorings,
            'todays_monitorings'      => $todayMonitorings,
            'latest_monitoring'       => $latestMonitoring ? new MonitoringResource($latestMonitoring) : null,
            'latest_temperature'      => $latestMonitoring ? (float) $latestMonitoring->temperature : null,
            'latest_ph'               => $latestMonitoring ? (float) $latestMonitoring->ph : null,
            'latest_humidity'         => $latestMonitoring ? (float) $latestMonitoring->humidity : null,
            'plant_condition_summary' => $rawConditionCounts,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan dashboard berhasil diambil.',
            'data'    => $data,
        ], 200);
    }
}
