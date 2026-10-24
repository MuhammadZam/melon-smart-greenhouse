<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Do not index/rewrite historical identifiers: existing duplicates remain intact.
        Schema::create('monitoring_ingestion_keys', function (Blueprint $table) {
            $table->char('identity_hash', 64)->primary();
            $table->char('payload_hash', 64);
            // Preserve subsecond event identity even when legacy monitorings use seconds.
            $table->string('observed_at', 26);
            $table->foreignId('monitoring_id')->nullable()->constrained('monitorings')->nullOnDelete();
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        // Only the ledger is removed. Monitoring, alarms and fuzzy history remain.
        Schema::dropIfExists('monitoring_ingestion_keys');
    }
};
