<?php

namespace App\Services\GoogleSheets;

use App\Exceptions\GoogleSheetsException;
use App\Models\GoogleSheetsSyncConfig;
use App\Models\GoogleSheetsSyncLog;
use App\Models\IotDevice;
use App\Services\MonitoringAlarmService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GoogleSheetsSyncService
{
    protected GoogleSheetsTelemetryService $telemetryService;
    protected MonitoringAlarmService $alarmService;

    public function __construct(
        GoogleSheetsTelemetryService $telemetryService,
        MonitoringAlarmService $alarmService
    ) {
        $this->telemetryService = $telemetryService;
        $this->alarmService = $alarmService;
    }

    public function getTelemetryService(): GoogleSheetsTelemetryService
    {
        return $this->telemetryService;
    }

    /**
     * Dapatkan konfigurasi aktif atau inisialisasi default.
     */
    public function getOrCreateDefaultConfig(): GoogleSheetsSyncConfig
    {
        $config = GoogleSheetsSyncConfig::first();

        if (!$config) {
            $config = GoogleSheetsSyncConfig::create([
                'name'                     => 'Greenhouse Melon Primary Telemetry',
                'spreadsheet_id'           => config('services.google_sheets.spreadsheet_id'),
                'sheet_name'               => config('services.google_sheets.sheet_name', 'Telemetry'),
                'data_range'               => 'A2:O',
                'is_active'                => true,
                'polling_interval_minutes' => config('services.google_sheets.polling_interval', 5),
                'auth_mode'                => config('services.google_sheets.mode', 'mock'),
                'credentials_path'         => config('services.google_sheets.credentials_path'),
                'last_synced_row'          => 1,
                'last_status'              => GoogleSheetsSyncConfig::STATUS_IDLE,
            ]);
        }

        return $config;
    }

    /**
     * Eksekusi proses sinkronisasi inkremental dari Google Sheets ke database.
     *
     * @param GoogleSheetsSyncConfig|null $config
     * @param string $triggeredBy ('cli', 'schedule', 'manual_admin')
     * @param int $batchLimit Maksimum baris per batch pembacaan
     * @return GoogleSheetsSyncLog
     */
    public function sync(?GoogleSheetsSyncConfig $config = null, string $triggeredBy = 'cli', int $batchLimit = 100): GoogleSheetsSyncLog
    {
        $config = $config ?? $this->getOrCreateDefaultConfig();

        // 1. Scheduler Lock untuk mencegah sinkronisasi tumpang tindih
        $lockKey = "google_sheets_sync_lock_{$config->id}";
        $lock = Cache::lock($lockKey, 60);

        if (!$lock->get()) {
            Log::warning("[GoogleSheetsSyncService] Sinkronisasi dilewati karena proses sebelumnya masih berjalan (Lock aktif).");
            return GoogleSheetsSyncLog::create([
                'config_id'        => $config->id,
                'mode'             => $config->auth_mode,
                'status'           => GoogleSheetsSyncConfig::STATUS_FAILED,
                'records_imported' => 0,
                'records_skipped'  => 0,
                'records_failed'   => 0,
                'started_at'       => now(),
                'finished_at'      => now(),
                'error_details'    => 'Sinkronisasi dilewati: scheduler lock aktif pada server.',
                'triggered_by'     => $triggeredBy,
            ]);
        }

        $startedAt = now();
        $cursorBefore = (string) $config->last_synced_row;
        $highestProcessedRow = $config->last_synced_row;

        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $errorMessages = [];

        try {
            // 2. Hitung rentang inkremental (High-water mark cursor)
            $startRow = $config->last_synced_row >= 2 ? ($config->last_synced_row + 1) : 2;
            $endRow = $startRow + $batchLimit - 1;
            $range = "{$config->sheet_name}!A{$startRow}:O{$endRow}";

            $spreadsheetId = $config->spreadsheet_id ?: 'mock_spreadsheet_id';

            // 3. Ambil baris dari connector
            $rows = $this->telemetryService->fetchRows($spreadsheetId, $range);

            if (!empty($rows)) {
                foreach ($rows as $idx => $rawRow) {
                    $currentRowNumber = $startRow + $idx;

                    // Parse & validasi
                    $parsed = $this->telemetryService->parseAndValidateRow($rawRow, $currentRowNumber);

                    if (!$parsed['valid']) {
                        $failed++;
                        $errorMessages[] = $parsed['error'];
                        // Tetap update kursor baris agar baris error tidak terus-menerus memblokir antrean
                        $highestProcessedRow = max($highestProcessedRow, $currentRowNumber);
                        continue;
                    }

                    $data = $parsed['data'];
                    // The shared ledger scopes identity by source, device, plant and event.
                    // It prevents races without suppressing a different device/source event.
                    $externalPayload = null;
                    if (isset($data['fuzzy_score'])) {
                        $externalPayload = [
                            'score' => (float) $data['fuzzy_score'],
                            'condition_category' => $data['fuzzy_label'] ?? null,
                            'conclusion' => 'Data inferensi fuzzy eksternal disinkronkan dari Google Sheets telemetri.',
                        ];
                    }
                    $result = app(\App\Services\MonitoringIngestionService::class)->ingest($data, null, $externalPayload);
                    if (!empty($data['device_id'])) {
                        $device = IotDevice::where('device_id', $data['device_id'])->first();
                        if ($device) $device->recordHeartbeat('google_sheets_sync');
                    }
                    if ($result['created']) $imported++; else $skipped++;

                    $highestProcessedRow = max($highestProcessedRow, $currentRowNumber);
                }
            }

            // 6. Evaluasi Status Akhir Sinkronisasi
            if ($failed > 0 && $imported > 0) {
                $status = GoogleSheetsSyncConfig::STATUS_PARTIAL;
            } elseif ($failed > 0 && $imported === 0 && $skipped === 0) {
                $status = GoogleSheetsSyncConfig::STATUS_FAILED;
            } else {
                $status = GoogleSheetsSyncConfig::STATUS_SUCCESS;
            }

            $lastError = !empty($errorMessages) ? implode("\n", array_slice($errorMessages, 0, 10)) : null;

            // 7. Perbarui Konfigurasi Kursor
            $config->update([
                'last_synced_at'          => now(),
                'last_synced_row'         => $highestProcessedRow,
                'last_status'             => $status,
                'last_error'              => $lastError,
                'last_successful_sync_at' => ($status === GoogleSheetsSyncConfig::STATUS_SUCCESS || $imported > 0) ? now() : $config->last_successful_sync_at,
            ]);

            // 8. Tulis Log Audit Sinkronisasi
            $log = GoogleSheetsSyncLog::create([
                'config_id'        => $config->id,
                'mode'             => $config->auth_mode,
                'status'           => $status,
                'records_imported' => $imported,
                'records_skipped'  => $skipped,
                'records_failed'   => $failed,
                'started_at'       => $startedAt,
                'finished_at'      => now(),
                'error_details'    => $lastError,
                'cursor_before'    => $cursorBefore,
                'cursor_after'     => (string) $highestProcessedRow,
                'triggered_by'     => $triggeredBy,
            ]);

            return $log;

        } catch (\Throwable $e) {
            $status = GoogleSheetsSyncConfig::STATUS_FAILED;
            $errorMessage = $e->getMessage();

            $config->update([
                'last_synced_at' => now(),
                'last_status'    => $status,
                'last_error'     => $errorMessage,
            ]);

            $log = GoogleSheetsSyncLog::create([
                'config_id'        => $config->id,
                'mode'             => $config->auth_mode,
                'status'           => $status,
                'records_imported' => $imported,
                'records_skipped'  => $skipped,
                'records_failed'   => $failed + 1,
                'started_at'       => $startedAt,
                'finished_at'      => now(),
                'error_details'    => $errorMessage,
                'cursor_before'    => $cursorBefore,
                'cursor_after'     => (string) $highestProcessedRow,
                'triggered_by'     => $triggeredBy,
            ]);

            Log::error("[GoogleSheetsSyncService] Gagal sinkronisasi Google Sheets: " . $errorMessage, [
                'config_id' => $config->id,
                'exception' => $e,
            ]);

            return $log;

        } finally {
            $lock->release();
        }
    }
}
