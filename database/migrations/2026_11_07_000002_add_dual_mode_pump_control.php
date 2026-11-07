<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('actuators', function (Blueprint $table) {
            $table->string('control_mode', 10)->default('MANUAL');
            $table->foreignId('control_plant_id')->nullable()->constrained('plants')->nullOnDelete();
            $table->decimal('auto_on_threshold', 5, 2)->nullable();
            $table->decimal('auto_off_threshold', 5, 2)->nullable();
            // Software test limits, not approved irrigation settings.
            $table->unsignedInteger('max_runtime_seconds')->default(120);
            $table->unsignedInteger('min_rest_seconds')->default(60);
            $table->unsignedInteger('sensor_max_age_seconds')->default(120);
            $table->timestamp('pump_started_at')->nullable();
            $table->timestamp('pump_stopped_at')->nullable();
            $table->timestamp('auto_armed_at')->nullable();
            $table->timestamp('last_evaluated_at')->nullable();
            $table->timestamp('last_scheduler_at')->nullable();
            $table->unsignedBigInteger('auto_after_monitoring_id')->default(0);
            $table->string('control_reason')->nullable();
            $table->unsignedBigInteger('control_version')->default(0);
        });
        Schema::table('actuator_command_logs', function (Blueprint $table) {
            $table->string('previous_mode', 10)->nullable();
            $table->string('resulting_mode', 10)->nullable();
            $table->string('reason_code')->nullable();
            $table->unsignedBigInteger('monitoring_id')->nullable();
            $table->json('control_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        // Forward-only: retain control configuration and all audit history.
    }
};
