<?php

namespace App\Services\FuzzyLogic;

use App\Models\FuzzyInferenceResult;
use App\Models\Monitoring;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class FuzzyPipelineService
{
    protected MamdaniInferenceService $inferenceService;
    protected FuzzyConclusionService $conclusionService;

    public function __construct(
        MamdaniInferenceService $inferenceService,
        FuzzyConclusionService $conclusionService
    ) {
        $this->inferenceService = $inferenceService;
        $this->conclusionService = $conclusionService;
    }

    /**
     * Jalankan pipeline inferensi fuzzy untuk sebuah data monitoring.
     *
     * @param Monitoring $monitoring
     * @param string $source 'laravel_engine'|'external_esp32'|'google_sheets_payload'|'manual_recalc'
     * @param array|null $externalPayload Data inferensi eksternal jika dikirim oleh IoT/Google Sheets
     * @return FuzzyInferenceResult|null
     */
    public function process(
        Monitoring $monitoring,
        string $source = 'laravel_engine',
        ?array $externalPayload = null
    ): ?FuzzyInferenceResult {
        // Invalid external input must not be converted into a plausible local inference.
        if ($externalPayload !== null) {
            $score = ExternalFuzzyScore::normalize($externalPayload['score'] ?? null);
            if ($score === null) return null;
            $externalPayload['score'] = $score;
        }
        // Cek apakah fitur fuzzy aktif di konfigurasi / runtime cache (kecuali data eksternal)
        $runtimeEnabled = \Illuminate\Support\Facades\Cache::get('fuzzy_inference_runtime_enabled', config('fuzzy.enabled', true));
        if (!$runtimeEnabled && $source === 'laravel_engine') {
            return null;
        }

        try {
            $ruleVersion = config('fuzzy.rule_version', 'v1.0-provisional');

            // 1. Tangani Data Inferensi Eksternal (Preservasi Tanpa Menimpa Diam-diam)
            if ($externalPayload !== null) {
                return $this->storeExternalInference($monitoring, $externalPayload, $source, $ruleVersion);
            }

            // 2. Validasi Kelengkapan dan Validitas Input Sensor
            $validation = $this->validateInputs($monitoring);
            $inputSnapshot = $validation['inputs'];

            if (!$validation['is_valid']) {
                return $this->storeInferenceResult(
                    monitoring: $monitoring,
                    source: $source,
                    status: 'INSUFFICIENT_DATA',
                    score: null,
                    category: null,
                    conclusion: 'Data sensor lingkungan tidak lengkap untuk evaluasi fuzzy logic (diperlukan suhu, kelembapan, kelembapan tanah, dan pH).',
                    recommendation: 'Pastikan seluruh sensor operasional aktif dan mengirimkan nilai dalam batas fisik yang valid.',
                    ruleVersion: $ruleVersion,
                    inputSnapshot: $inputSnapshot,
                    membershipSnapshot: null,
                    ruleActivationSnapshot: null
                );
            }

            // 3. Jalankan Eksekusi Mesin Inferensi Mamdani
            $inferenceOutput = $this->inferenceService->infer($inputSnapshot);

            $status = $inferenceOutput['status'];
            $score = $inferenceOutput['score'];
            $category = $inferenceOutput['category'];

            // 4. Deteksi Kondisi Data STALE (Kadaluwarsa berdasarkan monitored_at)
            $staleHours = (int) config('fuzzy.stale_threshold_hours', 24);
            $monitoredAt = $monitoring->monitored_at ?? now();
            if ($status === 'AVAILABLE' && Carbon::parse($monitoredAt)->diffInHours(now()) > $staleHours) {
                $status = 'STALE';
            }

            // 5. Sintesis Kesimpulan dan Rekomendasi
            $synthesis = $this->conclusionService->generate($inferenceOutput, $ruleVersion);

            // 6. Simpan Hasil Inferensi dengan Idempotensi (Non-destruktif)
            $result = $this->storeInferenceResult(
                monitoring: $monitoring,
                source: $source,
                status: $status,
                score: $score,
                category: $category,
                conclusion: $synthesis['conclusion'],
                recommendation: $synthesis['recommendation'],
                ruleVersion: $ruleVersion,
                inputSnapshot: $inputSnapshot,
                membershipSnapshot: $inferenceOutput['membership_snapshot'] ?? null,
                ruleActivationSnapshot: $inferenceOutput['rule_activation_snapshot'] ?? null
            );

            // 7. Update Kolom Kompatibilitas pada Tabel monitorings (Hanya jika bukan sinkronisasi raw telemetri google sheets)
            if ($source !== 'google_sheets_sync' && $monitoring->fuzzy_score === null) {
                $monitoring->updateQuietly([
                    'fuzzy_score' => $score,
                    'fuzzy_label' => $category,
                ]);
            }

            return $result;
        } catch (Throwable $e) {
            // Isolasi kegagalan: error kalkulasi fuzzy tidak boleh menggagalkan transaksi monitoring
            Log::error("[FuzzyPipelineService] Gagal menjalankan inferensi fuzzy untuk monitoring #{$monitoring->id}: " . $e->getMessage(), [
                'exception'     => $e,
                'monitoring_id' => $monitoring->id,
            ]);

            try {
                return $this->storeInferenceResult(
                    monitoring: $monitoring,
                    source: $source,
                    status: 'ERROR',
                    score: null,
                    category: null,
                    conclusion: 'Terjadi kegagalan komputasi saat mengeksekusi mesin inferensi fuzzy: ' . $e->getMessage(),
                    recommendation: 'Periksa log sistem dan konfigurasi fungsi keanggotaan.',
                    ruleVersion: config('fuzzy.rule_version', 'v1.0-provisional'),
                    inputSnapshot: [
                        'temperature'   => $monitoring->temperature,
                        'humidity'      => $monitoring->humidity,
                        'soil_moisture' => $monitoring->soil_moisture,
                        'ph'            => $monitoring->ph,
                    ],
                    membershipSnapshot: null,
                    ruleActivationSnapshot: null
                );
            } catch (Throwable) {
                return null;
            }
        }
    }

    /**
     * Validasi 4 parameter input utama untuk inferensi fuzzy.
     *
     * @return array{is_valid: bool, inputs: array<string, float|null>, error: ?string}
     */
    public function validateInputs(Monitoring $monitoring): array
    {
        $temp = $monitoring->temperature;
        $hum = $monitoring->humidity;
        $soil = $monitoring->soil_moisture;
        $ph = $monitoring->ph;

        $inputs = [
            'temperature'   => $temp !== null ? (float) $temp : null,
            'humidity'      => $hum !== null ? (float) $hum : null,
            'soil_moisture' => $soil !== null ? (float) $soil : null,
            'ph'            => $ph !== null ? (float) $ph : null,
        ];

        // 1. Periksa Null Values (Incomplete Data)
        if ($temp === null || $hum === null || $soil === null || $ph === null) {
            return ['is_valid' => false, 'inputs' => $inputs, 'error' => 'Parameter sensor belum lengkap.'];
        }

        // 2. Periksa Batas Fisik Validitas
        $tempMin = (float) config('fuzzy.inputs.temperature.min', 10.0);
        $tempMax = (float) config('fuzzy.inputs.temperature.max', 50.0);
        if ($temp < $tempMin || $temp > $tempMax) {
            return ['is_valid' => false, 'inputs' => $inputs, 'error' => "Suhu ({$temp}°C) di luar rentang valid [{$tempMin}, {$tempMax}]."];
        }

        if ($hum < 0.0 || $hum > 100.0) {
            return ['is_valid' => false, 'inputs' => $inputs, 'error' => "Kelembapan udara ({$hum}%) di luar rentang valid [0, 100]."];
        }

        if ($soil < 0.0 || $soil > 100.0) {
            return ['is_valid' => false, 'inputs' => $inputs, 'error' => "Kelembapan tanah ({$soil}%) di luar rentang valid [0, 100]."];
        }

        if ($ph < 0.0 || $ph > 14.0) {
            return ['is_valid' => false, 'inputs' => $inputs, 'error' => "pH ({$ph}) di luar rentang valid [0, 14]."];
        }

        return ['is_valid' => true, 'inputs' => $inputs, 'error' => null];
    }

    /**
     * Simpan hasil inferensi ke tabel fuzzy_inference_results (Idempotent per monitoring, rule_version, & source).
     */
    protected function storeInferenceResult(
        Monitoring $monitoring,
        string $source,
        string $status,
        ?float $score,
        ?string $category,
        ?string $conclusion,
        ?string $recommendation,
        string $ruleVersion,
        ?array $inputSnapshot,
        ?array $membershipSnapshot,
        ?array $ruleActivationSnapshot
    ): FuzzyInferenceResult {
        return FuzzyInferenceResult::updateOrCreate(
            [
                'monitoring_id'    => $monitoring->id,
                'rule_version'     => $ruleVersion,
                'inference_source' => $source,
            ],
            [
                'plant_id'                 => $monitoring->plant_id,
                'inference_status'         => $status,
                'fuzzy_score'              => $score,
                'fuzzy_category'           => $category,
                'conclusion'               => $conclusion,
                'recommendation'           => $recommendation,
                'input_snapshot'           => $inputSnapshot,
                'membership_snapshot'      => $membershipSnapshot,
                'rule_activation_snapshot' => $ruleActivationSnapshot,
                'inferred_at'              => now(),
            ]
        );
    }

    /**
     * Simpan inferensi eksternal yang dikirim oleh perangkat IoT / Google Sheets.
     */
    protected function storeExternalInference(
        Monitoring $monitoring,
        array $payload,
        string $source,
        string $ruleVersion
    ): FuzzyInferenceResult {
        $score = isset($payload['score']) ? (float) $payload['score'] : null;
        $category = $this->inferenceService->resolveCategory($score);
        $conclusion = $payload['conclusion'] ?? 'Inferensi eksternal dari firmware/spreadsheet.';
        $recommendation = $payload['recommendation'] ?? null;

        if (is_array($recommendation)) {
            $recommendation = json_encode($recommendation, JSON_UNESCAPED_UNICODE);
        }

        return FuzzyInferenceResult::firstOrCreate(
            [
                'monitoring_id'    => $monitoring->id,
                'rule_version'     => $ruleVersion,
                'inference_source' => $source,
            ],
            [
                'plant_id'                 => $monitoring->plant_id,
                'inference_status'         => 'AVAILABLE',
                'fuzzy_score'              => $score,
                'fuzzy_category'           => $category,
                'conclusion'               => $conclusion,
                'recommendation'           => $recommendation,
                'input_snapshot'           => $payload['input_vector'] ?? [
                    'temperature'   => $monitoring->temperature,
                    'humidity'      => $monitoring->humidity,
                    'soil_moisture' => $monitoring->soil_moisture,
                    'ph'            => $monitoring->ph,
                ],
                'membership_snapshot'      => null,
                'rule_activation_snapshot' => null,
                'inferred_at'              => now(),
            ]
        );
    }
}
