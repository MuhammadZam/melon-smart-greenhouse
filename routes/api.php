<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MonitoringController;
use App\Http\Controllers\Api\PlantController;
use App\Http\Middleware\AuthenticateInternalApi;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| REST API Routes — Sistem Monitoring Tanaman Melon
|--------------------------------------------------------------------------
| Seluruh rute otomatis mendapatkan awalan URL '/api' dan middleware 'api'.
| Mendukung integrasi masa depan (IoT, sensor, mobile, external client).
*/

// 1. Endpoint Data Tanaman
Route::prefix('plants')->group(function () {
    Route::get('/', [PlantController::class, 'index'])->middleware(['web', AuthenticateInternalApi::class])->name('api.plants.index');
    Route::get('/{plant}', [PlantController::class, 'show'])->middleware(['web', AuthenticateInternalApi::class])->name('api.plants.show');
});

// 2. Endpoint Data Monitoring & Statistik Agregasi
Route::prefix('monitorings')->group(function () {
    Route::get('/statistics', [MonitoringController::class, 'statistics'])->middleware(['web', AuthenticateInternalApi::class])->name('api.monitorings.statistics');
    Route::get('/', [MonitoringController::class, 'index'])->middleware(['web', AuthenticateInternalApi::class])->name('api.monitorings.index');
    Route::post('/', [MonitoringController::class, 'store'])
        ->middleware(['device.auth', 'throttle:60,1'])
        ->name('api.monitorings.store');
    Route::get('/{monitoring}', [MonitoringController::class, 'show'])->middleware(['web', AuthenticateInternalApi::class])->name('api.monitorings.show');
});

// 3. Endpoint Ringkasan Dashboard
Route::prefix('dashboard')->group(function () {
    Route::get('/summary', [DashboardController::class, 'summary'])->middleware(['web', AuthenticateInternalApi::class])->name('api.dashboard.summary');
});
