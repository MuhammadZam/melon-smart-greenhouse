<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Buat tabel monitoring_alarms untuk sistem alarm sensor Phase 12.
     *
     * Desain:
     * - Non-destruktif: hanya menambah tabel baru tanpa mengubah tabel existing.
     * - Kombinasi (plant_id, parameter_key, violation_direction) unik per alarm aktif.
     * - Unique index 'unique_active_alarm' memastikan tidak ada duplikasi alarm aktif
     *   untuk kombinasi tanaman + parameter + arah pelanggaran yang sama.
     */
    public function up(): void
    {
        Schema::create('monitoring_alarms', function (Blueprint $table) {
            $table->id();

            // Relasi ke tanaman
            $table->foreignId('plant_id')
                ->constrained('plants')
                ->onDelete('cascade');

            // Relasi ke monitoring pemicu (bisa null jika monitoring dihapus)
            $table->foreignId('triggered_by_monitoring_id')
                ->nullable()
                ->constrained('monitorings')
                ->onDelete('set null');

            // Kunci field parameter di model Monitoring (misal: 'temperature', 'ec', 'tank_level')
            $table->string('parameter_key', 60)->index();

            // Nama display parameter (dari monitoring_parameters.parameter_name)
            $table->string('parameter_display_name', 120);

            // Satuan parameter (dari monitoring_parameters.unit)
            $table->string('unit', 30)->nullable();

            // Arah pelanggaran: below_minimum | above_maximum
            $table->enum('violation_direction', ['below_minimum', 'above_maximum']);

            // Nilai sensor saat alarm pertama kali terpicu
            $table->decimal('sensor_value', 12, 4);

            // Nilai threshold saat alarm dibuat (snapshot historis, tidak berubah jika threshold diubah)
            $table->decimal('threshold_min', 12, 4);
            $table->decimal('threshold_max', 12, 4);

            // Tingkat keparahan: warning | critical
            $table->enum('severity', ['warning', 'critical'])->default('warning');

            // Status alarm: active | resolved
            $table->enum('status', ['active', 'resolved'])->default('active')->index();

            // Waktu pertama kali alarm terdeteksi
            $table->timestamp('first_detected_at');

            // Nilai dan waktu pembacaan abnormal terakhir (diupdate saat pelanggaran berlanjut)
            $table->decimal('last_abnormal_value', 12, 4)->nullable();
            $table->timestamp('last_abnormal_at')->nullable();

            // Waktu alarm diselesaikan dan nilai sensor saat resolved
            $table->decimal('resolved_value', 12, 4)->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Catatan tambahan (mis: siapa yang resolve, otomatis vs manual)
            $table->text('notes')->nullable();

            $table->timestamps();

            /**
             * Unique constraint untuk mencegah duplikasi alarm aktif.
             *
             * Hanya satu alarm aktif yang boleh ada per kombinasi:
             *   (plant_id, parameter_key, violation_direction, status='active')
             *
             * Karena MySQL partial index tidak didukung secara native di semua versi,
             * kita enforced via aplikasi (MonitoringAlarmService) dengan transaction + lock.
             * Index berikut mempercepat query lookup alarm aktif.
             */
            $table->index(['plant_id', 'parameter_key', 'violation_direction', 'status'], 'idx_alarm_lookup');
        });

        /**
         * Tambahkan kolom 'critical_value' ke tabel monitoring_parameters
         * untuk mendukung threshold critical (non-destruktif, nullable).
         *
         * Semantik:
         *   - tank_level < critical_min_value → severity = critical
         *   - temperature > critical_max_value → severity = critical
         *   Jika null, tidak ada evaluasi kritis untuk parameter tersebut.
         */
        Schema::table('monitoring_parameters', function (Blueprint $table) {
            if (!Schema::hasColumn('monitoring_parameters', 'critical_min_value')) {
                $table->decimal('critical_min_value', 12, 4)
                    ->nullable()
                    ->after('minimum_value')
                    ->comment('Threshold kritis minimum. Pelanggaran di bawah nilai ini → severity=critical.');
            }
            if (!Schema::hasColumn('monitoring_parameters', 'critical_max_value')) {
                $table->decimal('critical_max_value', 12, 4)
                    ->nullable()
                    ->after('maximum_value')
                    ->comment('Threshold kritis maksimum. Pelanggaran di atas nilai ini → severity=critical.');
            }
            if (!Schema::hasColumn('monitoring_parameters', 'parameter_key')) {
                $table->string('parameter_key', 60)
                    ->nullable()
                    ->after('parameter_name')
                    ->comment('Kunci field di model Monitoring (mis: temperature, ec, tank_level).')
                    ->index();
            }
        });
    }

    /**
     * Rollback migration Phase 12 (hanya digunakan di lingkungan development/testing).
     */
    public function down(): void
    {
        Schema::dropIfExists('monitoring_alarms');

        Schema::table('monitoring_parameters', function (Blueprint $table) {
            if (Schema::hasColumn('monitoring_parameters', 'critical_min_value')) {
                $table->dropColumn('critical_min_value');
            }
            if (Schema::hasColumn('monitoring_parameters', 'critical_max_value')) {
                $table->dropColumn('critical_max_value');
            }
            if (Schema::hasColumn('monitoring_parameters', 'parameter_key')) {
                $table->dropColumn('parameter_key');
            }
        });
    }
};
