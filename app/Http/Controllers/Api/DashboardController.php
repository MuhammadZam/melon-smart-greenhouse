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
            'latest_monitoring'           => $latestMonitoring ? new MonitoringResource($latestMonitoring) : null,
            'latest_temperature'          => $latestMonitoring && $latestMonitoring->temperature !== null ? (float) $latestMonitoring->temperature : null,
            'latest_nutrient_temperature' => $latestMonitoring && $latestMonitoring->nutrient_temperature !== null ? (float) $latestMonitoring->nutrient_temperature : null,
            'latest_ph'                   => $latestMonitoring && $latestMonitoring->ph !== null ? (float) $latestMonitoring->ph : null,
            'latest_ec'                   => $latestMonitoring && $latestMonitoring->ec !== null ? (float) $latestMonitoring->ec : null,
            'latest_humidity'             => $latestMonitoring && $latestMonitoring->humidity !== null ? (float) $latestMonitoring->humidity : null,
            'latest_soil_moisture'        => $latestMonitoring && $latestMonitoring->soil_moisture !== null ? (float) $latestMonitoring->soil_moisture : null,
            'latest_light_intensity'      => $latestMonitoring && $latestMonitoring->light_intensity !== null ? (float) $latestMonitoring->light_intensity : null,
            'latest_tank_level'           => $latestMonitoring && $latestMonitoring->tank_level !== null ? (float) $latestMonitoring->tank_level : null,
            'plant_condition_summary'     => $rawConditionCounts,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Ringkasan dashboard berhasil diambil.',
            'data'    => $data,
        ], 200);
    }
}
