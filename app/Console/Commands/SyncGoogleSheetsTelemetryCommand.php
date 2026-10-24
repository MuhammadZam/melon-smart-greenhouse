<?php

namespace App\Console\Commands;

use App\Models\GoogleSheetsSyncConfig;
use App\Services\GoogleSheets\GoogleSheetsSyncService;
use Illuminate\Console\Command;

class SyncGoogleSheetsTelemetryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telemetry:sync-google-sheets
                            {--batch=100 : Maximum rows to synchronize in this execution}
                            {--force : Force sync even if config is marked inactive}
                            {--reset-cursor : Reset last synced row cursor back to 1}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize telemetry readings from Google Sheets into the melon monitoring database';

    /**
     * Execute the console command.
     */
    public function handle(GoogleSheetsSyncService $syncService): int
    {
        $this->info('========================================================');
        $this->info('  GOOGLE SHEETS TELEMETRY SYNCHRONIZATION (PHASE 14)    ');
        $this->info('========================================================');

        $config = $syncService->getOrCreateDefaultConfig();

        if (!$config->is_active && !$this->option('force')) {
            $this->warn('Konfigurasi Google Sheets sedang non-aktif. Gunakan --force untuk tetap menjalankan.');
            return self::FAILURE;
        }

        if ($this->option('reset-cursor')) {
            $config->update(['last_synced_row' => 1]);
            $this->warn('Kursor sinkronisasi berhasil di-reset kembali ke baris 1 (A2:O).');
        }

        $batchLimit = (int) $this->option('batch');
        $this->line("Mode Autentikasi : <comment>{$config->auth_mode}</comment>");
        $this->line("Spreadsheet ID    : <comment>" . ($config->spreadsheet_id ?: '(Mock Default)') . "</comment>");
        $this->line("Sheet Name Range  : <comment>{$config->sheet_name}!{$config->data_range}</comment>");
        $this->line("Kursor Sebelum    : <comment>Row #{$config->last_synced_row}</comment>");
        $this->line("Batch Limit       : <comment>{$batchLimit} rows</comment>");
        $this->newLine();

        $this->info('Menghubungkan ke Google Sheets Connector...');
        $log = $syncService->sync($config, 'cli', $batchLimit);

        $this->table(
            ['Metrik', 'Nilai'],
            [
                ['Status Sinkronisasi', $log->status],
                ['Record Diimpor', $log->records_imported],
                ['Record Dilewati (Duplikat)', $log->records_skipped],
                ['Record Gagal (Invalid)', $log->records_failed],
                ['Kursor Sebelum', $log->cursor_before ?? '-'],
                ['Kursor Sesudah', $log->cursor_after ?? '-'],
                ['Waktu Eksekusi', ($log->durationInSeconds() ?? 0) . ' detik'],
            ]
        );

        if ($log->status === GoogleSheetsSyncConfig::STATUS_SUCCESS) {
            $this->info('Sinkronisasi selesai dengan sukses.');
            return self::SUCCESS;
        }

        if ($log->status === GoogleSheetsSyncConfig::STATUS_PARTIAL) {
            $this->warn('Sinkronisasi selesai sebagian (beberapa baris tidak valid).');
            if ($log->error_details) {
                $this->line('<error>Detail Error:</error>');
                $this->line($log->error_details);
            }
            return self::SUCCESS;
        }

        $this->error('Sinkronisasi gagal!');
        if ($log->error_details) {
            $this->line('<error>' . $log->error_details . '</error>');
        }

        return self::FAILURE;
    }
}
