<?php

namespace App\Services;

use App\Models\Monitoring;
use App\Models\MonitoringAlarm;
use App\Models\MonitoringParameter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * MonitoringAlarmService — Phase 12
 *
 * Bertanggung jawab atas seluruh siklus hidup alarm monitoring:
 *  1. Evaluasi data monitoring baru terhadap threshold parameter.
 *  2. Penciptaan alarm aktif baru jika pelanggaran terdeteksi.
 *  3. Update alarm aktif yang masih berlanjut (last_abnormal_value, last_abnormal_at).
 *  4. Penyelesaian (resolve) alarm aktif saat nilai kembali normal.
 *  5. Pencegahan duplikasi alarm aktif melalui DB transaction.
 *
 * Batasan Phase 12:
 *  - Tidak ada relay/aktuator control.
 *  - Tidak ada WebSocket/MQTT/push notification real-time.
 *  - Tidak ada email/WhatsApp/Telegram.
 *  - Resolve otomatis ketika monitoring baru menunjukkan nilai kembali normal.
 */
class MonitoringAlarmService
{
    public const ALARM_ALLOWED_PARAMETERS = [
        'temperature',
        'humidity',
        'soil_moisture',
        'ph',
    ];

    /**
     * Cek apakah suatu parameter berhak menghasilkan alarm aktif (Phase 13).
     */
    public static function isAlarmEligible(string $field): bool
    {
        return in_array($field, self::ALARM_ALLOWED_PARAMETERS, true);
    }

    /**
     * Mapping dari field Monitoring ke parameter_key di monitoring_parameters.parameter_key.
     * Urutan: [monitoring_field => parameter_key_in_db]
     *
     * Jika monitoring_parameters tidak memiliki parameter_key terisi,
     * service akan fallback ke lookup berdasarkan parameter_name.
     *
     * @var array<string, string>
     */
    protected const FIELD_TO_PARAM_NAME_FALLBACK = [
        'temperature'          => 'Suhu Udara',
        'humidity'             => 'Kelembapan Udara',
        'soil_moisture'        => 'Kelembapan Tanah',
        'ph'                   => 'pH Nutrisi/Media',
        'ec'                   => 'Konduktivitas Listrik (EC)',
        'light_intensity'      => 'Intensitas Cahaya',
        'nutrient_temperature' => 'Suhu Nutrisi',
        'tank_level'           => 'Level Tandon Nutrisi',
        'plant_height'         => 'Tinggi Tanaman',
        'plant_density'        => 'Kerapatan Kanopi',
    ];

    /**
     * Parameter yang secara default dikategorikan severity=critical
     * jika melanggar arah tertentu, bahkan tanpa critical_min/max_value diset.
     *
     * Semantik nilai: 'below_minimum' | 'above_maximum' | 'both'
     *
     * @var array<string, string>
     */
    protected const DEFAULT_CRITICAL = [];

    /**
     * Titik masuk utama: evaluasi data monitoring yang baru disimpan.
     *
     * Dipanggil setelah Monitoring::create() di controller API dan web.
     * Semua error ditangkap agar tidak menginterupsi alur penyimpanan data sensor.
     */
    public function evaluate(Monitoring $monitoring): void
    {
        try {
            $this->processMonitoring($monitoring);
        } catch (\Throwable $e) {
            Log::error('[MonitoringAlarmService] Evaluasi alarm gagal', [
                'monitoring_id' => $monitoring->id,
                'plant_id'      => $monitoring->plant_id,
                'error'         => $e->getMessage(),
                'trace'         => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Kebijakan Transisi Phase 13:
     * Menyelesaikan alarm aktif lama yang berada di luar 4 parameter yang diizinkan.
     * Histori alarm tidak dihapus, snapshot nilai tetap terjaga, dan catatan audit ditambahkan.
     *
     * @return int Jumlah alarm yang diselesaikan
     */
    public function reconcileLegacyAlarms(): int
    {
        $legacyAlarms = MonitoringAlarm::active()
            ->whereNotIn('parameter_key', self::ALARM_ALLOWED_PARAMETERS)
            ->get();

        $resolvedCount = 0;
        foreach ($legacyAlarms as $alarm) {
            $alarm->update([
                'status'         => 'resolved',
                'resolved_value' => $alarm->last_abnormal_value ?? $alarm->sensor_value,
                'resolved_at'    => now(),
                'notes'          => '[Phase 13 Transition Policy] Diselesaikan otomatis: parameter ini dialihkan menjadi mode monitoring visual saja (non-alarm). Histori dipertahankan.',
            ]);
            $resolvedCount++;
        }

        if ($resolvedCount > 0) {
            Log::info("[MonitoringAlarmService] {$resolvedCount} alarm lama non-eligible berhasil diselesaikan oleh kebijakan transisi Phase 13.");
        }

        return $resolvedCount;
    }

    /**
     * Proses inti evaluasi: iterasi semua field parameter yang memiliki threshold terdaftar.
     */
    protected function processMonitoring(Monitoring $monitoring): void
    {
        // Ambil semua konfigurasi threshold sekaligus, index by parameter_key
        $thresholds = $this->loadThresholds();

        foreach (self::FIELD_TO_PARAM_NAME_FALLBACK as $field => $displayNameFallback) {
            // [Phase 13 Rule]: Hanya 4 parameter yang diizinkan memicu alarm baru
            if (!self::isAlarmEligible($field)) {
                continue;
            }

            // Skip jika nilai sensor null (field opsional belum diisi)
            $sensorValue = $monitoring->{$field};
            if ($sensorValue === null) {
                continue;
            }

            // Cari konfigurasi threshold untuk field ini
            $config = $thresholds[$field] ?? null;

            // Jika tidak ada threshold terdaftar untuk parameter ini, skip
            if ($config === null) {
                continue;
            }

            $minVal = (float) $config->minimum_value;
            $maxVal = (float) $config->maximum_value;
            $sensorValue = (float) $sensorValue;

            // Tentukan arah pelanggaran (jika ada)
            $violation = null;
            if ($sensorValue < $minVal) {
                $violation = 'below_minimum';
            } elseif ($sensorValue > $maxVal) {
                $violation = 'above_maximum';
            }

            if ($violation !== null) {
                // Ada pelanggaran → buat atau update alarm aktif
                $this->handleViolation(
                    monitoring: $monitoring,
                    field: $field,
                    config: $config,
                    sensorValue: $sensorValue,
                    minVal: $minVal,
                    maxVal: $maxVal,
                    violation: $violation,
                    displayNameFallback: $displayNameFallback,
                );
            } else {
                // Nilai normal → resolve alarm aktif jika ada
                $this->resolveIfActive(
                    monitoring: $monitoring,
                    field: $field,
                    sensorValue: $sensorValue,
                );
            }
        }
    }

    /**
     * Tangani pelanggaran threshold: buat alarm baru atau update alarm aktif yang sudah ada.
     */
    protected function handleViolation(
        Monitoring $monitoring,
        string $field,
        object $config,
        float $sensorValue,
        float $minVal,
        float $maxVal,
        string $violation,
        string $displayNameFallback,
    ): void {
        DB::transaction(function () use (
            $monitoring, $field, $config, $sensorValue,
            $minVal, $maxVal, $violation, $displayNameFallback
        ) {
            // Cek apakah alarm aktif sudah ada untuk kombinasi ini
            $existing = MonitoringAlarm::where('plant_id', $monitoring->plant_id)
                ->where('parameter_key', $field)
                ->where('violation_direction', $violation)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            $severity = $this->determineSeverity($field, $violation, $sensorValue, $config);

            if ($existing) {
                // Update alarm aktif: perbarui nilai terakhir dan severity
                $existing->update([
                    'last_abnormal_value'        => $sensorValue,
                    'last_abnormal_at'           => $monitoring->monitored_at ?? now(),
                    'severity'                   => $severity,
                    'triggered_by_monitoring_id' => $monitoring->id,
                ]);

                Log::info('[MonitoringAlarmService] Alarm aktif diperbarui', [
                    'alarm_id'       => $existing->id,
                    'plant_id'       => $monitoring->plant_id,
                    'parameter_key'  => $field,
                    'sensor_value'   => $sensorValue,
                    'severity'       => $severity,
                ]);
            } else {
                // Buat alarm baru
                $alarm = MonitoringAlarm::create([
                    'plant_id'                   => $monitoring->plant_id,
                    'triggered_by_monitoring_id' => $monitoring->id,
                    'parameter_key'              => $field,
                    'parameter_display_name'     => $config->parameter_name ?? $displayNameFallback,
                    'unit'                       => $config->unit ?? null,
                    'violation_direction'        => $violation,
                    'sensor_value'               => $sensorValue,
                    'threshold_min'              => $minVal,
                    'threshold_max'              => $maxVal,
                    'severity'                   => $severity,
                    'status'                     => 'active',
                    'first_detected_at'          => $monitoring->monitored_at ?? now(),
                    'last_abnormal_value'        => $sensorValue,
                    'last_abnormal_at'           => $monitoring->monitored_at ?? now(),
                ]);

                Log::info('[MonitoringAlarmService] Alarm baru dibuat', [
                    'alarm_id'      => $alarm->id,
                    'plant_id'      => $monitoring->plant_id,
                    'parameter_key' => $field,
                    'sensor_value'  => $sensorValue,
                    'violation'     => $violation,
                    'severity'      => $severity,
                ]);
            }
        });
    }

    /**
     * Resolve alarm aktif jika nilai sensor kembali dalam rentang normal.
     */
    protected function resolveIfActive(Monitoring $monitoring, string $field, float $sensorValue): void
    {
        DB::transaction(function () use ($monitoring, $field, $sensorValue) {
            $activeAlarms = MonitoringAlarm::where('plant_id', $monitoring->plant_id)
                ->where('parameter_key', $field)
                ->where('status', 'active')
                ->lockForUpdate()
                ->get();

            foreach ($activeAlarms as $alarm) {
                $alarm->update([
                    'status'        => 'resolved',
                    'resolved_value' => $sensorValue,
                    'resolved_at'   => $monitoring->monitored_at ?? now(),
                    'notes'         => 'Diselesaikan otomatis: nilai sensor kembali normal.',
                ]);

                Log::info('[MonitoringAlarmService] Alarm diselesaikan otomatis', [
                    'alarm_id'      => $alarm->id,
                    'plant_id'      => $monitoring->plant_id,
                    'parameter_key' => $field,
                    'resolved_value' => $sensorValue,
                ]);
            }
        });
    }

    /**
     * Tentukan severity berdasarkan:
     * 1. Critical threshold khusus (critical_min_value / critical_max_value) jika dikonfigurasi.
     * 2. Default critical parameter (mis: tank_level below_minimum → always critical).
     * 3. Fallback ke 'warning'.
     */
    protected function determineSeverity(
        string $field,
        string $violation,
        float $sensorValue,
        object $config,
    ): string {
        // Cek critical threshold khusus dari monitoring_parameters
        if ($violation === 'below_minimum' && isset($config->critical_min_value) && $config->critical_min_value !== null) {
            if ($sensorValue < (float) $config->critical_min_value) {
                return 'critical';
            }
        }

        if ($violation === 'above_maximum' && isset($config->critical_max_value) && $config->critical_max_value !== null) {
            if ($sensorValue > (float) $config->critical_max_value) {
                return 'critical';
            }
        }

        // Default critical berdasarkan jenis parameter
        if (isset(self::DEFAULT_CRITICAL[$field])) {
            $criticalDirection = self::DEFAULT_CRITICAL[$field];
            if ($criticalDirection === 'both' || $criticalDirection === $violation) {
                return 'critical';
            }
        }

        return 'warning';
    }

    /**
     * Muat semua konfigurasi threshold dari monitoring_parameters, index by parameter_key field.
     *
     * Strategi lookup (berurutan):
     *  1. parameter_key kolom di tabel (diset setelah Phase 12 migration)
     *  2. Fallback: cocokkan via parameter_name menggunakan FIELD_TO_PARAM_NAME_FALLBACK
     *
     * @return array<string, object> [field_name => MonitoringParameter-like object]
     */
    protected function loadThresholds(): array
    {
        $parameters = MonitoringParameter::all();

        $indexed = [];

        // Buat reverse map: parameter_name → field
        $nameToField = array_flip(self::FIELD_TO_PARAM_NAME_FALLBACK);

        foreach ($parameters as $param) {
            // Prioritas 1: gunakan parameter_key jika tersedia
            if (!empty($param->parameter_key) && array_key_exists($param->parameter_key, self::FIELD_TO_PARAM_NAME_FALLBACK)) {
                $indexed[$param->parameter_key] = $param;
                continue;
            }

            // Prioritas 2: fallback matching via parameter_name
            if (isset($nameToField[$param->parameter_name])) {
                $field = $nameToField[$param->parameter_name];
                $indexed[$field] = $param;
            }
        }

        return $indexed;
    }

    /**
     * Ambil ringkasan alarm untuk dashboard.
     *
     * @return array{active: int, critical: int, resolved_today: int, latest: \Illuminate\Database\Eloquent\Collection}
     */
    public function getDashboardSummary(): array
    {
        $active       = MonitoringAlarm::active()->whereIn('parameter_key', self::ALARM_ALLOWED_PARAMETERS)->count();
        $critical     = MonitoringAlarm::active()->critical()->whereIn('parameter_key', self::ALARM_ALLOWED_PARAMETERS)->count();
        $resolvedToday = MonitoringAlarm::resolved()
            ->whereDate('resolved_at', today())
            ->count();

        $latest = MonitoringAlarm::with('plant')
            ->active()
            ->whereIn('parameter_key', self::ALARM_ALLOWED_PARAMETERS)
            ->orderByDesc('first_detected_at')
            ->limit(5)
            ->get();

        return compact('active', 'critical', 'resolvedToday', 'latest');
    }

    /**
     * Ambil jumlah alarm aktif untuk badge navbar (polling endpoint).
     */
    public function getActiveAlarmCount(): int
    {
        return MonitoringAlarm::active()->whereIn('parameter_key', self::ALARM_ALLOWED_PARAMETERS)->count();
    }
}
