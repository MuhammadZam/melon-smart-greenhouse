<?php

namespace App\Services\GoogleSheets;

use App\Contracts\GoogleSheetsClientInterface;
use App\Models\Plant;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class GoogleSheetsTelemetryService
{
    protected GoogleSheetsClientInterface $client;

    public const EXPECTED_HEADERS = [
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

        // 2. Validasi field wajib keberadaan
        $deviceId = trim((string) ($row['device_id'] ?? ''));
        if (empty($deviceId)) {
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

        $monitoredAt = $this->parseTimestamp($rawTimestamp);
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
        $plantId = $this->resolvePlantId($plantCode);
        if (!$plantId) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: tidak ada tanaman melon aktif yang ditemukan di database.",
                'source_identifier' => null,
            ];
        }

        // 5. Validasi 4 Parameter Sensor Wajib
        $temp = $this->parseNumeric($row['temperature'] ?? null);
        if ($temp === null || $temp < 0.0 || $temp > 60.0) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: temperature wajib numerik 0.00-60.00.",
                'source_identifier' => null,
            ];
        }

        $hum = $this->parseNumeric($row['humidity'] ?? null);
        if ($hum === null || $hum < 0.0 || $hum > 100.0) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: humidity wajib numerik 0.00-100.00.",
                'source_identifier' => null,
            ];
        }

        $soil = $this->parseNumeric($row['soil_moisture'] ?? null);
        if ($soil === null || $soil < 0.0 || $soil > 100.0) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: soil_moisture wajib numerik 0.00-100.00.",
                'source_identifier' => null,
            ];
        }

        $ph = $this->parseNumeric($row['ph'] ?? null);
        if ($ph === null || $ph < 0.0 || $ph > 14.0) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: ph wajib numerik 0.00-14.00.",
                'source_identifier' => null,
            ];
        }

        // 6. Validasi Parameter Opsional (EC, Light, Nutrient Temp, Tank Level)
        $ec = $this->parseNumeric($row['ec'] ?? null);
        if ($ec !== null && ($ec < 0.0 || $ec > 10.0)) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: ec berada di luar rentang valid 0.00-10.00.",
                'source_identifier' => null,
            ];
        }

        $light = $this->parseNumeric($row['light_intensity'] ?? null);
        if ($light !== null && ($light < 0.0 || $light > 100000.0)) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: light_intensity berada di luar rentang valid 0.00-100000.00.",
                'source_identifier' => null,
            ];
        }

        $nutrientTemp = $this->parseNumeric($row['nutrient_temp'] ?? null);
        if ($nutrientTemp !== null && ($nutrientTemp < 0.0 || $nutrientTemp > 60.0)) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: nutrient_temp berada di luar rentang valid 0.00-60.00.",
                'source_identifier' => null,
            ];
        }

        $tankLevel = $this->parseNumeric($row['tank_level'] ?? null);
        if ($tankLevel !== null && ($tankLevel < 0.0 || $tankLevel > 100.0)) {
            return [
                'valid' => false,
                'data' => null,
                'error' => "Baris #{$rowNumber}: tank_level berada di luar rentang valid 0.00-100.00.",
                'source_identifier' => null,
            ];
        }

        // 7. Status Aktuator Fisik yang Dilaporkan (Opsional, diisolasi dari simulation_state)
        $reportedPump = $this->parseStateString($row['pump_status'] ?? null);
        $reportedFan = $this->parseStateString($row['fan_status'] ?? null);

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
        $formattedTimestamp = $monitoredAt->format($monitoredAt->micro === 0 ? 'Y-m-d H:i:s' : 'Y-m-d H:i:s.u');
        $sourceIdentifier = hash('sha256', "{$deviceId}|{$formattedTimestamp}|{$plantId}");

        $validatedData = [
            'plant_id'             => $plantId,
            'device_id'            => $deviceId,
            'monitored_at'         => $formattedTimestamp,
            'temperature'          => $temp,
            'humidity'             => $hum,
            'soil_moisture'        => $soil,
            'ph'                   => $ph,
            'ec'                   => $ec,
            'light_intensity'      => $light,
            'nutrient_temperature' => $nutrientTemp,
            'tank_level'           => $tankLevel,
            'reported_pump_state'  => $reportedPump,
            'reported_fan_state'   => $reportedFan,
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

        foreach (self::EXPECTED_HEADERS as $idx => $key) {
            $result[$key] = $indexed[$idx] ?? null;
        }

        return $result;
    }

    /**
     * Parse string angka yang mungkin memuat koma desimal atau spasi.
     */
    protected function parseNumeric(mixed $value): ?float
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

        return round((float) $normalized, 2);
    }

    /**
     * Parsing berbagai variasi format timestamp.
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
    protected function resolvePlantId(?string $plantCode): ?int
    {
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
