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
        Schema::create('monitorings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained('plants')->cascadeOnDelete();
            $table->decimal('temperature', 5, 2);
            $table->decimal('ph', 4, 2);
            $table->decimal('humidity', 5, 2);
            $table->decimal('plant_height', 5, 2)->nullable();
            $table->decimal('plant_density', 5, 2)->nullable();
            $table->string('plant_condition');
            $table->string('plant_color');
            $table->text('notes')->nullable();
            $table->dateTime('monitored_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitorings');
    }
};
