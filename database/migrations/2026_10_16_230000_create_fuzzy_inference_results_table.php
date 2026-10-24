<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Phase 15: Fuzzy Logic Inference Engine & Smart Greenhouse Condition Analysis
     */
    public function up(): void
    {
        Schema::create('fuzzy_inference_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monitoring_id')->nullable()->constrained('monitorings')->nullOnDelete();
            $table->foreignId('plant_id')->constrained('plants')->cascadeOnDelete();
            $table->string('inference_source')->default('laravel_engine'); // 'laravel_engine', 'external_esp32', 'google_sheets_payload', 'manual_recalc'
            $table->string('inference_status')->default('AVAILABLE'); // 'AVAILABLE', 'INSUFFICIENT_DATA', 'INSUFFICIENT_RULE_COVERAGE', 'ERROR', 'STALE'
            $table->decimal('fuzzy_score', 5, 2)->nullable();
            $table->string('fuzzy_category', 50)->nullable(); // 'BAIK', 'PERLU_PERHATIAN', 'BURUK'
            $table->text('conclusion')->nullable();
            $table->text('recommendation')->nullable();
            $table->string('rule_version', 50)->default('v1.0-provisional');
            $table->json('input_snapshot')->nullable();
            $table->json('membership_snapshot')->nullable();
            $table->json('rule_activation_snapshot')->nullable();
            $table->timestamp('inferred_at')->useCurrent();
            $table->timestamps();

            // Indeks komposit untuk pencarian performan & integritas data
            $table->index(['plant_id', 'inferred_at']);
            $table->index(['inference_status', 'inferred_at']);
            $table->unique(['monitoring_id', 'rule_version', 'inference_source'], 'uniq_monitoring_rule_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fuzzy_inference_results');
    }
};
