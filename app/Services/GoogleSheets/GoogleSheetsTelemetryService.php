<?php

namespace App\Services\GoogleSheets;

use App\Contracts\GoogleSheetsClientInterface;
use App\Models\Plant;
use App\Services\MonitoringIngestionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class GoogleSheetsTelemetryService
{
    protected GoogleSheetsClientInterface $client;

    public const LEGACY_HEADERS = [
        'device_id',
        'timestamp',
        'plant_code',
        'temperature',
        'humidity',
        'soil_moisture',
        'ph',
        'ec',
        'light_intensity',
        'nutrient_temp',
        'tank_level',
        'pump_status',
        'fan_status',
        'fuzzy_score',
        'fuzzy_label',
    ];

    public const EXPECTED_HEADERS = ['device_id', 'timestamp', 'plant_code', 'temperature', 'soil_moisture', 'water_ph', 'pump_status', 'fuzzy_score', 'fuzzy_label', 'observation_id'];

    public function __construct(?GoogleSheetsClientInterface $client = null)
    {
        if ($client) {
            $this->client = $client;
        } else {
            $mode = config('services.google_sheets.mode', 'mock');
            if ($mode === 'service_account') {
                $this->client = App::make(GoogleSheetsApiClient::class);
            } else {
                $this->client = App::make(MockGoogleSheetsClient::class);
            }
        }
    }

    public function getClient(): GoogleSheetsClientInterface
    {
        return $this->client;
    }

    public function setClient(GoogleSheetsClientInterface $client): self
    {
        $this->client = $client;
        return $this;
    }

    /**
     * Ambil data mentah baris dari Google Sheets.
     */
    public function fetchRows(string $spreadsheetId, string $range): array
    {
        return $this->client->fetchRange($spreadsheetId, $range);
    }

    /**
     * Parse dan validasi 1 baris telemetri dari spreadsheet.
     * Mendukung format array asosiatif (berdasarkan header) maupun indeks numerik 0..14.
     *
     * @param array $rawRow
     * @param int $rowNumber
     * @return array{valid: bool, data: ?array, error: ?string, source_identifier: ?string}
     */
    public function parseAndValidateRow(array $rawRow, int $rowNumber = 0): array
    {
        // 1. Normalisasi baris ke array terasosiasi
        $row = $this->normalizeRow($rawRow);
        $observationId = $row['observation_id'] ?? '';
        if ($observationId !== '' && (!is_string($observationId) || !preg_match(MonitoringIngestionService::OBSERVATION_ID_PATTERN, $observationId))) {
            return ['valid' => false, 'data' => null, 'error' => "Baris #{$rowNumber}: observation_id tidak valid.", 'source_identifier' => null];
        }
        $shared = $observationId !== '';

        // 2. Validasi field wajib keberadaan
        $deviceId = trim((string) ($row['device_id'] ?? ''));
        if ($deviceId === '' || ($shared && strlen($deviceId) > 100)) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: device_id wajib diisi.",
                'source_identifier' => null,
            ];
        }

        // 3. Validasi & Parsing Timestamp
        $rawTimestamp = trim((string) ($row['timestamp'] ?? ''));
        if (empty($rawTimestamp)) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: timestamp wajib diisi.",
                'source_identifier' => null,
            ];
        }

        $monitoredAt = $shared ? $this->parseObservationTimestamp($rawTimestamp) : $this->parseTimestamp($rawTimestamp);
        if (!$monitoredAt) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: format timestamp tidak valid '{$rawTimestamp}'.",
                'source_identifier' => null,
            ];
        }

        // 4. Resolusi Plant
        $plantCode = trim((string) ($row['plant_code'] ?? ''));
        $plantId = $this->resolvePlantId($plantCode, $shared);
        if (!$plantId) {
            return [
                'valid' => false,
                'data' => null,
                'error' => $shared
                    ? "Baris #{$rowNumber}: plant_code harus berisi ID tanaman yang terdaftar."
                    : "Baris #{$rowNumber}: tidak ada tanaman melon aktif yang ditemukan di database.",
                'source_identifier' => null,
            ];
        }

        // 5. Validasi 3 Parameter Sensor Wajib
        $temp = $this->parseNumeric($row['temperature'] ?? null, $shared);
        if ($temp === null || $temp < 0.0 || $temp > 60.0) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: temperature wajib numerik 0.00-60.00.",
                'source_identifier' => null,
            ];
        }

        $soil = $this->parseNumeric($row['soil_moisture'] ?? null, $shared);
        if ($soil === null || $soil < 0.0 || $soil > 100.0) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: soil_moisture wajib numerik 0.00-100.00.",
                'source_identifier' => null,
            ];
        }

        if (isset($row['water_ph'], $row['ph']) && (!is_numeric($row['water_ph']) || !is_numeric($row['ph']) || (float) $row['water_ph'] !== (float) $row['ph'])) {
            return ['valid' => false, 'data' => null, 'error' => 'water_ph dan ph tidak sama.', 'source_identifier' => null];
        }
        $ph = $this->parseNumeric($row['water_ph'] ?? $row['ph'] ?? null, $shared);
        if ($ph === null || $ph < 0.0 || $ph > 14.0) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: ph wajib numerik 0.00-14.00.",
                'source_identifier' => null,
            ];
        }

        // 7. Status Aktuator Fisik yang Dilaporkan (Opsional, diisolasi dari simulation_state)
        $reportedPump = $this->parseStateString($row['pump_status'] ?? null);

        // 8. Draf Inferensi Fuzzy (Opsional)
        $rawScore = $row['fuzzy_score'] ?? null;
        try {
            // A blank optional spreadsheet cell means unavailable; malformed nonblank
            // values fail the row rather than silently replacing an external diagnosis.
            $fuzzyScore = \App\Services\FuzzyLogic\ExternalFuzzyScore::normalize(
                is_string($rawScore) && trim($rawScore) === '' ? null : $rawScore
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ['valid' => false, 'data' => null, 'error' => "Baris #{$rowNumber}: fuzzy_score harus numerik finite 0–100.", 'source_identifier' => null];
        }
        $fuzzyLabel = !empty($row['fuzzy_label']) ? trim((string) $row['fuzzy_label']) : null;
        if ($fuzzyScore !== null) {
            $fuzzyLabel = app(\App\Services\FuzzyLogic\MamdaniInferenceService::class)->resolveCategory($fuzzyScore);
        }

        // 9. Identifier Unik Idempotensi
        $formattedTimestamp = $shared
            ? $monitoredAt->format('Y-m-d\TH:i:s.uP')
            : $monitoredAt->format($monitoredAt->micro === 0 ? 'Y-m-d H:i:s' : 'Y-m-d H:i:s.u');
        $sourceIdentifier = hash('sha256', "{$deviceId}|{$formattedTimestamp}|{$plantId}");

        $validatedData = [
            'plant_id'             => $plantId,
            'device_id'            => $deviceId,
            'monitored_at'         => $formattedTimestamp,
            'temperature'          => $temp,
            'soil_moisture'        => $soil,
            'ph'                   => $ph,
            'reported_pump_state'  => $reportedPump,
            'fuzzy_score'          => $fuzzyScore,
            'fuzzy_label'          => $fuzzyLabel,
            'plant_height'         => 0.0,
            'plant_density'        => 0.0,
            'plant_condition'      => !empty($fuzzyLabel) ? $fuzzyLabel : 'optimal',
            'plant_color'          => 'Hijau',
            'source'               => 'google_sheets',
            'source_identifier'    => $sourceIdentifier,
            'notes'                => "Ingested via Google Sheets from {$deviceId}",
        ];

        return [
            'valid' => true,
            'observation_id' => $shared ? $observationId : null,
            'data' => $validatedData,
            'error' => null,
            'source_identifier' => $sourceIdentifier,
        ];
    }

    /**
     * Konversi raw row (asosiatif atau list) ke format seragam.
     */
    protected function normalizeRow(array $rawRow): array
    {
        // Jika sudah berupa asosiatif dengan key yang cocok
        if (isset($rawRow['device_id']) || isset($rawRow['temperature'])) {
            return $rawRow;
        }

        // Jika berupa array numerik (berdasarkan urutan kolom baku)
        $indexed = array_values($rawRow);
        $result = [];

        foreach ((config('services.google_sheets.telemetry_layout', 'legacy') === 'three_input' ? self::EXPECTED_HEADERS : self::LEGACY_HEADERS) as $idx => $key) {
            $result[$key] = $indexed[$idx] ?? null;
        }

        return $result;
    }

    /**
     * Parse string angka yang mungkin memuat koma desimal atau spasi.
     */
    protected function parseNumeric(mixed $value, bool $preservePrecision = false): ?float
    {
        if ($value === null) {
            return null;
        }

        $str = trim((string) $value);
        if ($str === '' || $str === '-' || strtolower($str) === 'null') {
            return null;
        }

        // Ganti koma dengan titik untuk representasi desimal standar
        $normalized = str_replace(',', '.', $str);

        if (!is_numeric($normalized)) {
            return null;
        }

        return $preservePrecision ? (float) $normalized : round((float) $normalized, 2);
    }

    private function parseObservationTimestamp(string $value): ?Carbon
    {
        if (!preg_match(MonitoringIngestionService::OBSERVATION_TIME_PATTERN, $value)) {
            return null;
        }

        $parsed = date_parse($value);
        return $parsed['warning_count'] === 0 && $parsed['error_count'] === 0
            ? Carbon::parse($value)->utc()
            : null;
    }

    /**
     * Parsing berbagai variasi format timestamp lama.
     */
    protected function parseTimestamp(string $raw): ?Carbon
    {
        $raw = trim($raw);
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:i:sP',
            'Y-m-d\TH:i:s.uP',
            'd/m/Y H:i:s',
            'd-m-Y H:i:s',
            'Y/m/d H:i:s',
            'Y-m-d',
        ];

        foreach ($formats as $fmt) {
            try {
                $parsed = Carbon::createFromFormat($fmt, $raw, 'Asia/Jakarta');
                if ($parsed !== false) {
                    return $parsed->setTimezone('Asia/Jakarta');
                }
            } catch (\Throwable $e) {
                // Lanjut coba format berikutnya
            }
        }

        // Fallback generic Carbon parser
        try {
            return Carbon::parse($raw, 'Asia/Jakarta')->setTimezone('Asia/Jakarta');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Resolusi kode tanaman ke plant_id database.
     */
    protected function resolvePlantId(?string $plantCode, bool $shared = false): ?int
    {
        if ($shared) {
            return ctype_digit($plantCode ?? '') ? Plant::find((int) $plantCode)?->id : null;
        }

        if (!empty($plantCode)) {
            $plant = Plant::where('name', $plantCode)
                ->orWhere('location', $plantCode)
                ->first();

            if ($plant) {
                return $plant->id;
            }

            // Jika berupa integer ID langsung
            if (is_numeric($plantCode)) {
                $plantById = Plant::find((int) $plantCode);
                if ($plantById) {
                    return $plantById->id;
                }
            }
        }

        // Fallback: Tanaman aktif pertama, atau tanaman pertama di database
        $defaultPlant = Plant::where('status', 'active')->first() ?? Plant::first();
        return $defaultPlant?->id;
    }

    protected function parseStateString(mixed $val): ?string
    {
        if ($val === null) {
            return null;
        }

        $str = strtoupper(trim((string) $val));
        if ($str === 'ON' || $str === '1' || $str === 'TRUE') {
            return 'ON';
        }
        if ($str === 'OFF' || $str === '0' || $str === 'FALSE') {
            return 'OFF';
        }

        return null;
    }
}
