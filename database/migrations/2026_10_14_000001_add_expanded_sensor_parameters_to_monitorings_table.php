<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 11: Sensor Parameter Expansion (IoT Integration Readiness)
     * Menambahkan 5 parameter sensor baru dengan sifat nullable untuk kompatibilitas data lama
     * dan payload ESP32 parsial.
     */
    public function up(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            // 1. Suhu Larutan Nutrisi / Tandon (°C)
            $table->decimal('nutrient_temperature', 5, 2)->nullable()->after('temperature');

            // 2. Konduktivitas Listrik Nutrisi (EC) (mS/cm)
            $table->decimal('ec', 5, 2)->nullable()->after('ph');

            // 3. Kelembapan Media Tanam / Tanah (%)
            $table->decimal('soil_moisture', 5, 2)->nullable()->after('humidity');

            // 4. Intensitas Cahaya (lux)
            $table->decimal('light_intensity', 8, 2)->nullable()->after('soil_moisture');

            // 5. Level Ketinggian Air Tandon Nutrisi (%)
            $table->decimal('tank_level', 5, 2)->nullable()->after('light_intensity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monitorings', function (Blueprint $table) {
            $table->dropColumn([
                'nutrient_temperature',
                'ec',
                'soil_moisture',
                'light_intensity',
                'tank_level',
            ]);
        });
    }
};
