<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('actuators', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('type'); // 'irrigation_pump', 'exhaust_fan'
            $table->string('simulation_state')->default('OFF'); // 'ON', 'OFF'
            $table->string('mode')->default('simulation'); // 'simulation'
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('last_changed_at')->nullable();
            $table->foreignId('last_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('actuator_command_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actuator_id')->constrained('actuators')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('command'); // 'TURN_ON', 'TURN_OFF', 'TOGGLE'
            $table->string('previous_state');
            $table->string('resulting_state');
            $table->string('execution_status')->default('SUCCESS'); // 'SUCCESS', 'FAILED', 'REJECTED'
            $table->string('source')->default('web_simulation');
            $table->timestamp('executed_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['actuator_id', 'executed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('actuator_command_logs');
        Schema::dropIfExists('actuators');
    }
};
