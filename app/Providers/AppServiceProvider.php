<?php

namespace App\Providers;

use App\Models\Monitoring;
use App\Models\Plant;
use App\Policies\MonitoringPolicy;
use App\Policies\PlantPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\Pump\PumpTransport::class, \App\Services\Pump\SimulationPumpTransport::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Monitoring::created(function () {
            \Illuminate\Support\Facades\DB::afterCommit(function () {
                try {
                    app(\App\Services\Pump\PumpControlService::class)->tick('monitoring_committed');
                } catch (\Throwable $error) {
                    // Ingestion already committed: never replay or roll back its identity receipt.
                    \Illuminate\Support\Facades\Log::error('Pump evaluation after monitoring commit failed', ['error' => $error->getMessage()]);
                }
            });
        });
        Gate::policy(Monitoring::class, MonitoringPolicy::class);
        Gate::policy(Plant::class, PlantPolicy::class);
    }
}
