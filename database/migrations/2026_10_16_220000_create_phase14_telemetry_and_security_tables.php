<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 14: Google Sheets Telemetry Integration & IoT Device Security
     */
    public function up(): void
    {
        // 1. Tabel Registrasi Perangkat IoT & Token Autentikasi
        Schema::create('iot_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique(); // e.g. 'ESP32-MELON-01', 'SIMULATOR-01'
            $table->string('name');
            $table->string('type')->default('esp32_hardware'); // 'esp32_hardware', 'python_simulator', 'google_sheets_gateway'
            $table->string('token_hash', 64)->index(); // SHA-256 hash of token
            $table->string('token_prefix', 12); // First 8-10 chars for UI identification
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_reason')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'token_hash']);
        });

        // 2. Tabel Konfigurasi Integrasi Google Sheets
        Schema::create('google_sheets_sync_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Greenhouse Melon Primary Telemetry');
            $table->string('spreadsheet_id')->nullable();
            $table->string('sheet_name')->default('Telemetry');
            $table->string('data_range')->default('A2:O');
            $table->boolean('is_active')->default(true);
            $table->integer('polling_interval_minutes')->default(5);
            $table->string('auth_mode')->default('mock'); // 'mock', 'service_account'
            $table->string('credentials_path')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_successful_sync_at')->nullable();
            $table->unsignedInteger('last_synced_row')->default(1);
            $table->string('last_status')->default('IDLE'); // 'IDLE', 'SUCCESS', 'PARTIAL', 'FAILED'
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        // 3. Tabel Log Sinkronisasi Google Sheets
        Schema::create('google_sheets_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('config_id')->nullable()->constrained('google_sheets_sync_configs')->nullOnDelete();
            $table->string('mode')->default('mock'); // 'mock', 'live'
            $table->string('status')->default('SUCCESS'); // 'SUCCESS', 'PARTIAL', 'FAILED'
            $table->unsignedInteger('records_imported')->default(0);
            $table->unsignedInteger('records_skipped')->default(0);
            $table->unsignedInteger('records_failed')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->text('error_details')->nullable();
            $table->string('cursor_before')->nullable();
            $table->string('cursor_after')->nullable();
            $table->string('triggered_by')->default('cli'); // 'cli', 'schedule', 'manual_admin'
            $table->timestamps();

            $table->index(['created_at', 'status']);
        });

        // 4. Perluasan Non-Destruktif Kolom Metadata pada Tabel `monitorings`
        Schema::table('monitorings', function (Blueprint $table) {
            $table->string('source')->default('api')->after('notes'); // 'api', 'web', 'google_sheets', 'simulator'
            $table->string('device_id')->nullable()->after('source'); // Asosiasi ID perangkat IoT
            $table->string('source_identifier')->nullable()->after('device_id')->index(); // Hash unik untuk idempotensi
            $table->string('reported_pump_state')->nullable()->after('source_identifier'); // Status aktual fisik dari telemetri
            $table->string('reported_fan_state')->nullable()->after('reported_pump_state'); // Status aktual fisik dari telemetri
            $table->decimal('fuzzy_score', 5, 2)->nullable()->after('reported_fan_state'); // Draf inferensi AI / Fuzzy
            $table->string('fuzzy_label')->nullable()->after('fuzzy_score'); // Draf label kondisi Fuzzy
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'device_id',
                'source_identifier',
                'reported_pump_state',
                'reported_fan_state',
                'fuzzy_score',
                'fuzzy_label',
            ]);
        });

        Schema::dropIfExists('google_sheets_sync_logs');
        Schema::dropIfExists('google_sheets_sync_configs');
        Schema::dropIfExists('iot_devices');
    }
};
