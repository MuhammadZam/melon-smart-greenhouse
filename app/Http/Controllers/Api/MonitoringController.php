<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMonitoringApiRequest;
use App\Http\Resources\MonitoringResource;
use App\Models\Monitoring;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MonitoringController extends Controller
{
    /**
     * Mengambil daftar data monitoring dengan opsi filter tanaman, kondisi, dan tanggal.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plant_id'  => ['nullable', 'integer', 'exists:plants,id'],
            'condition' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
            'search'    => ['nullable', 'string', 'max:100'],
        ]);

        $monitorings = Monitoring::with('plant')
            ->filter($validated)
            ->orderByDesc('monitored_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data monitoring berhasil diambil.',
            'data'    => MonitoringResource::collection($monitorings),
        ], 200);
    }

    /**
     * Menyimpan rekaman data monitoring baru dari sensor IoT / simulator data.
     */
    public function store(StoreMonitoringApiRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $plantDensity = $validated['canopy_density'] ?? $validated['plant_density'] ?? 0;
        $condition    = $validated['condition'] ?? $validated['plant_condition'] ?? 'optimal';
        $leafColor    = $validated['leaf_color'] ?? $validated['plant_color'] ?? 'Hijau';

        $deviceId = $request->input('device_id') ?? $request->header('X-Device-Id');
        if ($request->attributes->has('iot_device')) {
            $deviceId = $request->attributes->get('iot_device')->device_id;
        }

        $device = $request->attributes->get('iot_device');
        $attributes = [
            'plant_id'             => $validated['plant_id'],
            'monitored_at'         => $validated['monitored_at'],
            'temperature'          => (float) $validated['temperature'],
            'nutrient_temperature' => isset($validated['nutrient_temperature']) && $validated['nutrient_temperature'] !== null ? (float) $validated['nutrient_temperature'] : null,
            'ph'                   => (float) $validated['ph'],
            'ec'                   => isset($validated['ec']) && $validated['ec'] !== null ? (float) $validated['ec'] : null,
            'humidity'             => (float) $validated['humidity'],
            'soil_moisture'        => isset($validated['soil_moisture']) && $validated['soil_moisture'] !== null ? (float) $validated['soil_moisture'] : null,
            'light_intensity'      => isset($validated['light_intensity']) && $validated['light_intensity'] !== null ? (float) $validated['light_intensity'] : null,
            'tank_level'           => isset($validated['tank_level']) && $validated['tank_level'] !== null ? (float) $validated['tank_level'] : null,
            'plant_height'         => (float) $validated['plant_height'],
            'plant_density'        => (float) $plantDensity,
            'plant_condition'      => $condition,
            'plant_color'          => $leafColor,
            'notes'                => $validated['notes'] ?? 'Simulated sensor data (IoT Prototype)',
            'source'               => $device->type === \App\Models\IotDevice::TYPE_PYTHON_SIMULATOR ? 'simulator' : 'api',
            'device_id'            => $deviceId,
        ];
        $external = null;
        if (isset($validated['fuzzy_score'])) {
            $score = $validated['fuzzy_score'];
            $category = app(\App\Services\FuzzyLogic\MamdaniInferenceService::class)->resolveCategory($score);
            $attributes['fuzzy_score'] = $score;
            $attributes['fuzzy_label'] = $category;
            $external = ['score' => $score, 'category' => $category];
        }
        $result = app(\App\Services\MonitoringIngestionService::class)->ingest(
            $attributes, $validated['idempotency_key'] ?? $validated['event_id'] ?? null, $external
        );
        $monitoring = $result['monitoring']->load('plant');

        Log::info('Data monitoring sensor berhasil di-ingest via REST API', [
            'monitoring_id' => $monitoring->id,
            'plant_id'      => $monitoring->plant_id,
            'monitored_at'  => $monitoring->monitored_at?->toDateTimeString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $result['created'] ? 'Data monitoring berhasil disimpan.' : 'Observasi sudah tersimpan; pengiriman ulang tidak membuat duplikasi.',
            'data'    => new MonitoringResource($monitoring),
            'meta'    => ['created' => $result['created'], 'replayed' => !$result['created']],
        ], $result['created'] ? 201 : 200);
    }

    /**
     * Mengambil detail satu rekaman observasi monitoring.
     */
    public function show(Monitoring $monitoring): JsonResponse
    {
        $monitoring->load('plant');

        return response()->json([
            'success' => true,
            'message' => 'Detail monitoring berhasil diambil.',
            'data'    => new MonitoringResource($monitoring),
        ], 200);
    }

    /**
     * Mengambil kalkulasi agregasi statistik parameter monitoring (AVG, MIN, MAX).
     */
    public function statistics(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plant_id'  => ['nullable', 'integer', 'exists:plants,id'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        $query = Monitoring::query()->filter($validated);

        $stats = $query->selectRaw('
            COUNT(*) as total_count,
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

        $data = [
            'total_monitorings'          => (int) ($stats->total_count ?? 0),
            'average_temperature'        => $stats->avg_temp !== null ? round((float) $stats->avg_temp, 2) : null,
            'minimum_temperature'        => $stats->min_temp !== null ? (float) $stats->min_temp : null,
            'maximum_temperature'        => $stats->max_temp !== null ? (float) $stats->max_temp : null,
            'average_ph'                 => $stats->avg_ph !== null ? round((float) $stats->avg_ph, 2) : null,
            'minimum_ph'                 => $stats->min_ph !== null ? (float) $stats->min_ph : null,
            'maximum_ph'                 => $stats->max_ph !== null ? (float) $stats->max_ph : null,
            'average_humidity'           => $stats->avg_humidity !== null ? round((float) $stats->avg_humidity, 2) : null,
            'minimum_humidity'           => $stats->min_humidity !== null ? (float) $stats->min_humidity : null,
            'maximum_humidity'           => $stats->max_humidity !== null ? (float) $stats->max_humidity : null,
            'average_plant_height'       => $stats->avg_height !== null ? round((float) $stats->avg_height, 2) : null,
            'minimum_plant_height'       => $stats->min_height !== null ? (float) $stats->min_height : null,
            'maximum_plant_height'       => $stats->max_height !== null ? (float) $stats->max_height : null,
            'average_canopy_density'     => $stats->avg_density !== null ? round((float) $stats->avg_density, 2) : null,
            'minimum_canopy_density'     => $stats->min_density !== null ? (float) $stats->min_density : null,
            'maximum_canopy_density'     => $stats->max_density !== null ? (float) $stats->max_density : null,
            'average_nutrient_temperature' => $stats->avg_nutrient_temp !== null ? round((float) $stats->avg_nutrient_temp, 2) : null,
            'minimum_nutrient_temperature' => $stats->min_nutrient_temp !== null ? (float) $stats->min_nutrient_temp : null,
            'maximum_nutrient_temperature' => $stats->max_nutrient_temp !== null ? (float) $stats->max_nutrient_temp : null,
            'average_ec'                 => $stats->avg_ec !== null ? round((float) $stats->avg_ec, 2) : null,
            'minimum_ec'                 => $stats->min_ec !== null ? (float) $stats->min_ec : null,
            'maximum_ec'                 => $stats->max_ec !== null ? (float) $stats->max_ec : null,
            'average_soil_moisture'      => $stats->avg_soil_moisture !== null ? round((float) $stats->avg_soil_moisture, 2) : null,
            'minimum_soil_moisture'      => $stats->min_soil_moisture !== null ? (float) $stats->min_soil_moisture : null,
            'maximum_soil_moisture'      => $stats->max_soil_moisture !== null ? (float) $stats->max_soil_moisture : null,
            'average_light_intensity'    => $stats->avg_light !== null ? round((float) $stats->avg_light, 2) : null,
            'minimum_light_intensity'    => $stats->min_light !== null ? (float) $stats->min_light : null,
            'maximum_light_intensity'    => $stats->max_light !== null ? (float) $stats->max_light : null,
            'average_tank_level'         => $stats->avg_tank_level !== null ? round((float) $stats->avg_tank_level, 2) : null,
            'minimum_tank_level'         => $stats->min_tank_level !== null ? (float) $stats->min_tank_level : null,
            'maximum_tank_level'         => $stats->max_tank_level !== null ? (float) $stats->max_tank_level : null,
            'temperature' => [
                'average' => $stats->avg_temp !== null ? round((float) $stats->avg_temp, 2) : null,
                'min'     => $stats->min_temp !== null ? (float) $stats->min_temp : null,
                'max'     => $stats->max_temp !== null ? (float) $stats->max_temp : null,
            ],
            'ph' => [
                'average' => $stats->avg_ph !== null ? round((float) $stats->avg_ph, 2) : null,
                'min'     => $stats->min_ph !== null ? (float) $stats->min_ph : null,
                'max'     => $stats->max_ph !== null ? (float) $stats->max_ph : null,
            ],
            'humidity' => [
                'average' => $stats->avg_humidity !== null ? round((float) $stats->avg_humidity, 2) : null,
                'min'     => $stats->min_humidity !== null ? (float) $stats->min_humidity : null,
                'max'     => $stats->max_humidity !== null ? (float) $stats->max_humidity : null,
            ],
            'plant_height' => [
                'average' => $stats->avg_height !== null ? round((float) $stats->avg_height, 2) : null,
                'min'     => $stats->min_height !== null ? (float) $stats->min_height : null,
                'max'     => $stats->max_height !== null ? (float) $stats->max_height : null,
            ],
            'canopy_density' => [
                'average' => $stats->avg_density !== null ? round((float) $stats->avg_density, 2) : null,
                'min'     => $stats->min_density !== null ? (float) $stats->min_density : null,
                'max'     => $stats->max_density !== null ? (float) $stats->max_density : null,
            ],
            'nutrient_temperature' => [
                'average' => $stats->avg_nutrient_temp !== null ? round((float) $stats->avg_nutrient_temp, 2) : null,
                'min'     => $stats->min_nutrient_temp !== null ? (float) $stats->min_nutrient_temp : null,
                'max'     => $stats->max_nutrient_temp !== null ? (float) $stats->max_nutrient_temp : null,
            ],
            'ec' => [
                'average' => $stats->avg_ec !== null ? round((float) $stats->avg_ec, 2) : null,
                'min'     => $stats->min_ec !== null ? (float) $stats->min_ec : null,
                'max'     => $stats->max_ec !== null ? (float) $stats->max_ec : null,
            ],
            'soil_moisture' => [
                'average' => $stats->avg_soil_moisture !== null ? round((float) $stats->avg_soil_moisture, 2) : null,
                'min'     => $stats->min_soil_moisture !== null ? (float) $stats->min_soil_moisture : null,
                'max'     => $stats->max_soil_moisture !== null ? (float) $stats->max_soil_moisture : null,
            ],
            'light_intensity' => [
                'average' => $stats->avg_light !== null ? round((float) $stats->avg_light, 2) : null,
                'min'     => $stats->min_light !== null ? (float) $stats->min_light : null,
                'max'     => $stats->max_light !== null ? (float) $stats->max_light : null,
            ],
            'tank_level' => [
                'average' => $stats->avg_tank_level !== null ? round((float) $stats->avg_tank_level, 2) : null,
                'min'     => $stats->min_tank_level !== null ? (float) $stats->min_tank_level : null,
                'max'     => $stats->max_tank_level !== null ? (float) $stats->max_tank_level : null,
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Statistik monitoring berhasil diambil.',
            'data'    => $data,
        ], 200);
    }
}
