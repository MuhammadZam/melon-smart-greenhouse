<?php

namespace App\Services\Reports;

use App\Models\FuzzyInferenceResult;
use App\Models\Monitoring;
use App\Models\MonitoringAlarm;
use App\Models\Plant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * MonitoringReportService — Phase 16
 *
 * Bertanggung jawab atas query terpadu data monitoring greenhouse,
 * perhitungan ringkasan statistik (AVG, MIN, MAX tanpa menghitung null sebagai nol),
 * agregasi alarm 4 parameter, dan ringkasan inferensi logika fuzzy Mamdani.
 */
class MonitoringReportService
{
    /**
     * Membangun base query monitoring dengan filter terstandarisasi.
     */
    public function buildQuery(array $filters): Builder
    {
        $query = Monitoring::query()->with(['plant', 'latestFuzzyInference']);

        // 1. Filter Tanaman
        $plantId = $filters['plant_id'] ?? 'all';
        if ($plantId !== 'all' && !empty($plantId) && is_numeric($plantId)) {
            $query->where('plant_id', (int) $plantId);
        }

        // 2. Filter Rentang Tanggal
        if (!empty($filters['date_from'])) {
            $query->whereDate('monitored_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('monitored_at', '<=', $filters['date_to']);
        }

        // 3. Filter Sumber Data
        $source = $filters['source'] ?? 'all';
        if ($source !== 'all' && !empty($source)) {
            $query->where('source', $source);
        }

        // 4. Filter Kondisi Tanaman
        $condition = $filters['condition'] ?? 'all';
        if ($condition !== 'all' && !empty($condition)) {
            $query->where('plant_condition', $condition);
        }

        // 5. Urutan Sorting
        $sortBy = $filters['sort_by'] ?? 'monitored_at';
        $allowedSorts = [
            'monitored_at',
            'temperature',
            'humidity',
            'soil_moisture',
            'ph',
            'ec',
            'light_intensity',
            'nutrient_temperature',
            'tank_level',
        ];
        if (!in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'monitored_at';
        }

        $sortDirection = strtolower($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDirection);

        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        return $query;
    }

    /**
     * Mengambil data terstruktur untuk halaman laporan web (dengan pagination).
     */
    public function getReportData(array $filters, int $perPage = 15): array
    {
        $baseQuery = $this->buildQuery($filters);

        // Ambil data monitoring terpaginasi
        $monitorings = (clone $baseQuery)->paginate($perPage)->withQueryString();

        // Hitung ringkasan statistik
        $statistics = $this->calculateStatistics($baseQuery, $filters);

        // Hitung ringkasan alarm pada periode yang sama
        $alarmSummary = $this->calculateAlarmSummary($filters);

        // Hitung ringkasan fuzzy logic pada periode yang sama
        $fuzzySummary = $this->calculateFuzzySummary($filters);

        // Master data untuk dropdown filter
        $plants = Plant::orderBy('name')->get();
        $sources = [
            'all'           => 'Semua Sumber Data',
            'web'           => 'Web Form (Manual)',
            'api'           => 'REST API Ingestion',
            'google_sheets' => 'Google Sheets Sync',
            'simulator'     => 'IoT Python Simulator',
        ];
        $conditions = Monitoring::distinct()
            ->whereNotNull('plant_condition')
            ->pluck('plant_condition')
            ->sort()
            ->values();

        // Info Tanaman Terpilih (jika spesifik)
        $selectedPlant = null;
        if (!empty($filters['plant_id']) && $filters['plant_id'] !== 'all' && is_numeric($filters['plant_id'])) {
            $selectedPlant = Plant::find((int) $filters['plant_id']);
        }

        return [
            'monitorings'    => $monitorings,
            'statistics'     => $statistics,
            'alarmSummary'   => $alarmSummary,
            'fuzzySummary'   => $fuzzySummary,
            'plants'         => $plants,
            'sources'        => $sources,
            'conditions'     => $conditions,
            'selectedPlant'  => $selectedPlant,
            'filters'        => $filters,
        ];
    }

    /**
     * Menghitung statistik 8 parameter sensor + 2 metrik vegetatif tanpa menganggap null sebagai nol.
     */
    public function calculateStatistics(Builder $query, array $filters): array
    {
        $statsRaw = (clone $query)->reorder()->selectRaw('
            COUNT(*) as total_count,
            MIN(monitored_at) as first_monitored_at,
            MAX(monitored_at) as last_monitored_at,

            AVG(temperature) as avg_temp,
            MIN(temperature) as min_temp,
            MAX(temperature) as max_temp,

            AVG(humidity) as avg_humidity,
            MIN(humidity) as min_humidity,
            MAX(humidity) as max_humidity,

            AVG(soil_moisture) as avg_soil_moisture,
            MIN(soil_moisture) as min_soil_moisture,
            MAX(soil_moisture) as max_soil_moisture,

            AVG(ph) as avg_ph,
            MIN(ph) as min_ph,
            MAX(ph) as max_ph,

            AVG(ec) as avg_ec,
            MIN(ec) as min_ec,
            MAX(ec) as max_ec,

            AVG(light_intensity) as avg_light,
            MIN(light_intensity) as min_light,
            MAX(light_intensity) as max_light,

            AVG(nutrient_temperature) as avg_nutrient_temp,
            MIN(nutrient_temperature) as min_nutrient_temp,
            MAX(nutrient_temperature) as max_nutrient_temp,

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

        $totalCount = (int) ($statsRaw->total_count ?? 0);

        // Sumber data breakdown
        $sourceDistribution = (clone $query)->reorder()
            ->selectRaw('source, count(*) as count')
            ->groupBy('source')
            ->pluck('count', 'source')
            ->toArray();

        // Keterangan Agregasi
        $aggregationScope = (!empty($filters['plant_id']) && $filters['plant_id'] !== 'all')
            ? 'Spesifik per tanaman yang dipilih.'
            : 'Gabungan akumulasi seluruh observasi tanaman melon yang memenuhi kriteria filter.';

        return [
            'total_count'        => $totalCount,
            'first_monitored_at' => $statsRaw->first_monitored_at ? \Carbon\Carbon::parse($statsRaw->first_monitored_at) : null,
            'last_monitored_at'  => $statsRaw->last_monitored_at ? \Carbon\Carbon::parse($statsRaw->last_monitored_at) : null,
            'source_distribution' => $sourceDistribution,
            'aggregation_scope'  => $aggregationScope,

            'temperature' => [
                'name' => 'Suhu Udara',
                'unit' => '°C',
                'avg'  => $statsRaw->avg_temp !== null ? round((float) $statsRaw->avg_temp, 2) : null,
                'min'  => $statsRaw->min_temp !== null ? round((float) $statsRaw->min_temp, 2) : null,
                'max'  => $statsRaw->max_temp !== null ? round((float) $statsRaw->max_temp, 2) : null,
            ],
            'humidity' => [
                'name' => 'Kelembapan Udara',
                'unit' => '%',
                'avg'  => $statsRaw->avg_humidity !== null ? round((float) $statsRaw->avg_humidity, 2) : null,
                'min'  => $statsRaw->min_humidity !== null ? round((float) $statsRaw->min_humidity, 2) : null,
                'max'  => $statsRaw->max_humidity !== null ? round((float) $statsRaw->max_humidity, 2) : null,
            ],
            'soil_moisture' => [
                'name' => 'Kelembapan Tanah',
                'unit' => '%',
                'avg'  => $statsRaw->avg_soil_moisture !== null ? round((float) $statsRaw->avg_soil_moisture, 2) : null,
                'min'  => $statsRaw->min_soil_moisture !== null ? round((float) $statsRaw->min_soil_moisture, 2) : null,
                'max'  => $statsRaw->max_soil_moisture !== null ? round((float) $statsRaw->max_soil_moisture, 2) : null,
            ],
            'ph' => [
                'name' => 'pH Nutrisi/Media',
                'unit' => 'pH',
                'avg'  => $statsRaw->avg_ph !== null ? round((float) $statsRaw->avg_ph, 2) : null,
                'min'  => $statsRaw->min_ph !== null ? round((float) $statsRaw->min_ph, 2) : null,
                'max'  => $statsRaw->max_ph !== null ? round((float) $statsRaw->max_ph, 2) : null,
            ],
            'ec' => [
                'name' => 'Konduktivitas Listrik (EC)',
                'unit' => 'mS/cm',
                'avg'  => $statsRaw->avg_ec !== null ? round((float) $statsRaw->avg_ec, 2) : null,
                'min'  => $statsRaw->min_ec !== null ? round((float) $statsRaw->min_ec, 2) : null,
                'max'  => $statsRaw->max_ec !== null ? round((float) $statsRaw->max_ec, 2) : null,
            ],
            'light_intensity' => [
                'name' => 'Intensitas Cahaya',
                'unit' => 'lux',
                'avg'  => $statsRaw->avg_light !== null ? round((float) $statsRaw->avg_light, 1) : null,
                'min'  => $statsRaw->min_light !== null ? round((float) $statsRaw->min_light, 1) : null,
                'max'  => $statsRaw->max_light !== null ? round((float) $statsRaw->max_light, 1) : null,
            ],
            'nutrient_temperature' => [
                'name' => 'Suhu Nutrisi',
                'unit' => '°C',
                'avg'  => $statsRaw->avg_nutrient_temp !== null ? round((float) $statsRaw->avg_nutrient_temp, 2) : null,
                'min'  => $statsRaw->min_nutrient_temp !== null ? round((float) $statsRaw->min_nutrient_temp, 2) : null,
                'max'  => $statsRaw->max_nutrient_temp !== null ? round((float) $statsRaw->max_nutrient_temp, 2) : null,
            ],
            'tank_level' => [
                'name' => 'Level Tandon Nutrisi',
                'unit' => '%',
                'avg'  => $statsRaw->avg_tank_level !== null ? round((float) $statsRaw->avg_tank_level, 1) : null,
                'min'  => $statsRaw->min_tank_level !== null ? round((float) $statsRaw->min_tank_level, 1) : null,
                'max'  => $statsRaw->max_tank_level !== null ? round((float) $statsRaw->max_tank_level, 1) : null,
            ],
            'plant_height' => [
                'name' => 'Tinggi Tanaman',
                'unit' => 'cm',
                'avg'  => $statsRaw->avg_height !== null ? round((float) $statsRaw->avg_height, 2) : null,
                'min'  => $statsRaw->min_height !== null ? round((float) $statsRaw->min_height, 2) : null,
                'max'  => $statsRaw->max_height !== null ? round((float) $statsRaw->max_height, 2) : null,
            ],
            'plant_density' => [
                'name' => 'Kerapatan Kanopi',
                'unit' => '%',
                'avg'  => $statsRaw->avg_density !== null ? round((float) $statsRaw->avg_density, 1) : null,
                'min'  => $statsRaw->min_density !== null ? round((float) $statsRaw->min_density, 1) : null,
                'max'  => $statsRaw->max_density !== null ? round((float) $statsRaw->max_density, 1) : null,
            ],
        ];
    }

    /**
     * Menghitung ringkasan insiden alarm (4 parameter) pada filter yang sama.
     */
    public function calculateAlarmSummary(array $filters): array
    {
        $alarmQuery = MonitoringAlarm::query();

        $plantId = $filters['plant_id'] ?? 'all';
        if ($plantId !== 'all' && !empty($plantId) && is_numeric($plantId)) {
            $alarmQuery->where('plant_id', (int) $plantId);
        }

        if (!empty($filters['date_from'])) {
            $alarmQuery->whereDate('first_detected_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $alarmQuery->whereDate('first_detected_at', '<=', $filters['date_to']);
        }

        $totalAlarms = (clone $alarmQuery)->count();
        $criticalAlarms = (clone $alarmQuery)->where('severity', 'critical')->count();
        $warningAlarms = (clone $alarmQuery)->where('severity', 'warning')->count();
        $activeAlarms = (clone $alarmQuery)->where('status', 'active')->count();
        $resolvedAlarms = (clone $alarmQuery)->where('status', 'resolved')->count();

        $byParameter = (clone $alarmQuery)
            ->selectRaw('parameter_key, parameter_display_name, count(*) as count')
            ->groupBy('parameter_key', 'parameter_display_name')
            ->get();

        return [
            'total'        => $totalAlarms,
            'critical'     => $criticalAlarms,
            'warning'      => $warningAlarms,
            'active'       => $activeAlarms,
            'resolved'     => $resolvedAlarms,
            'by_parameter' => $byParameter,
        ];
    }

    /**
     * Menghitung ringkasan analisis fuzzy logika Mamdani pada filter yang sama.
     */
    public function calculateFuzzySummary(array $filters): array
    {
        $fuzzyQuery = FuzzyInferenceResult::query();

        $plantId = $filters['plant_id'] ?? 'all';
        if ($plantId !== 'all' && !empty($plantId) && is_numeric($plantId)) {
            $fuzzyQuery->where('plant_id', (int) $plantId);
        }

        if (!empty($filters['date_from'])) {
            $fuzzyQuery->whereDate('inferred_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $fuzzyQuery->whereDate('inferred_at', '<=', $filters['date_to']);
        }

        $totalInferences = (clone $fuzzyQuery)->count();
        $avgScore = (clone $fuzzyQuery)->whereNotNull('fuzzy_score')->avg('fuzzy_score');

        $byCategory = (clone $fuzzyQuery)
            ->whereNotNull('fuzzy_category')
            ->selectRaw('fuzzy_category, count(*) as count')
            ->groupBy('fuzzy_category')
            ->pluck('count', 'fuzzy_category')
            ->toArray();

        return [
            'total'       => $totalInferences,
            'avg_score'   => $avgScore !== null ? round((float) $avgScore, 2) : null,
            'by_category' => [
                'BAIK'            => $byCategory['BAIK'] ?? 0,
                'PERLU_PERHATIAN' => $byCategory['PERLU_PERHATIAN'] ?? 0,
                'BURUK'           => $byCategory['BURUK'] ?? 0,
            ],
            'rule_version' => config('fuzzy.rule_version', 'PROVISIONAL_FOR_SOFTWARE_TESTING'),
        ];
    }
}
