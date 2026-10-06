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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Monitoring::class, MonitoringPolicy::class);
        Gate::policy(Plant::class, PlantPolicy::class);
    }
}
