<?php

use App\Http\Controllers\ActuatorController;
use App\Http\Controllers\AlarmController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\PlantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Monitoring Tanaman Melon
|--------------------------------------------------------------------------
*/

// Halaman Beranda Publik
Route::get('/', function () {
    return view('welcome');
})->name('home');

// Rute untuk Tamu (Guest) - Belum Login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

// Same policy as internal API reads; device tokens cannot read notification data.
Route::get('/api/notifications/status', [AlarmController::class, 'notificationStatus'])
    ->middleware(\App\Http\Middleware\AuthenticateInternalApi::class)
    ->name('notifications.status');

// Rute untuk Pengguna yang Sudah Login (Authenticated)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Phase 5: Monitoring CRUD & Authorization
    Route::resource('monitorings', MonitoringController::class);

    // Phase 6: Plant CRUD & Authorization
    Route::resource('plants', PlantController::class);

    // Menu Navigasi Terstruktur (Section Anchors)
    Route::get('/charts', fn() => redirect('/dashboard#charts'))->name('charts.index');
    Route::get('/history', fn() => redirect('/dashboard#recent-table'))->name('history.index');

    // Rute Khusus Administrator (Role Middleware Test & Navigation)
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/users', fn() => redirect('/dashboard#admin-section'))->name('users.index');
        Route::get('/parameters', fn() => redirect('/dashboard#parameters-list'))->name('parameters.index');
        Route::get('/management-check', function () {
            return response()->json([
                'status'  => 'success',
                'message' => 'Otorisasi Role Admin Valid. Anda memiliki akses penuh ke area manajemen.',
                'user'    => auth()->user()->only(['id', 'name', 'email', 'role']),
            ]);
        })->name('check');
    });

    // Phase 12: Alarm & Notification Routes
    // Halaman riwayat alarm (semua user terautentikasi)
    Route::get('/alarms', [AlarmController::class, 'index'])->name('alarms.index');
    Route::get('/alarms/{alarm}', [AlarmController::class, 'show'])->name('alarms.show');

    // Endpoint polling JSON untuk badge notifikasi navbar

    // Manajemen threshold parameter (Admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/admin/thresholds', [AlarmController::class, 'thresholds'])->name('alarms.thresholds');
        Route::patch('/admin/thresholds/{parameter}', [AlarmController::class, 'updateThreshold'])->name('alarms.thresholds.update');
    });

    // Phase 13: Simulated Actuator Control Routes
    Route::get('/actuators', [ActuatorController::class, 'index'])->name('actuators.index');
    Route::get('/actuators/history', [ActuatorController::class, 'history'])->name('actuators.history');

    Route::middleware('role:admin')->group(function () {
        Route::post('/actuators/{actuator}/control', [ActuatorController::class, 'control'])->name('actuators.control');
    });

    // Phase 14: Google Sheets Telemetry Integration & Device Security
    Route::middleware('role:admin')->prefix('admin/integrations/google-sheets')->name('admin.integrations.google-sheets.')->group(function () {
        Route::get('/', [\App\Http\Controllers\GoogleSheetsIntegrationController::class, 'index'])->name('index');
        Route::post('/sync', [\App\Http\Controllers\GoogleSheetsIntegrationController::class, 'syncNow'])->name('sync');
        Route::post('/devices', [\App\Http\Controllers\GoogleSheetsIntegrationController::class, 'storeDevice'])->name('device.store');
        Route::post('/devices/{device}/revoke', [\App\Http\Controllers\GoogleSheetsIntegrationController::class, 'revokeDevice'])->name('device.revoke');
        Route::post('/devices/{device}/activate', [\App\Http\Controllers\GoogleSheetsIntegrationController::class, 'activateDevice'])->name('device.activate');
    });

    // Phase 15: Fuzzy Logic Inference Engine Routes
    // Riwayat Analisis Fuzzy (Admin & Pengamat)
    Route::get('/fuzzy/history', [\App\Http\Controllers\FuzzyHistoryController::class, 'index'])->name('fuzzy.history');
    Route::get('/fuzzy/history/{fuzzyInference}', [\App\Http\Controllers\FuzzyHistoryController::class, 'show'])->name('fuzzy.show');

    // Konfigurasi & Audit Aturan Fuzzy (Admin Only)
    Route::middleware('role:admin')->prefix('admin/fuzzy')->name('admin.fuzzy.')->group(function () {
        Route::get('/config', [\App\Http\Controllers\FuzzyConfigController::class, 'index'])->name('config');
        Route::post('/toggle', [\App\Http\Controllers\FuzzyConfigController::class, 'toggle'])->name('toggle');
    });

    // Phase 16: Laporan Monitoring Greenhouse & Ekspor (Admin & Pengamat)
    Route::prefix('reports/monitoring')->name('reports.monitoring.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MonitoringReportController::class, 'index'])->name('index');
        Route::get('/export/csv', [\App\Http\Controllers\MonitoringReportController::class, 'exportCsv'])->name('export.csv');
        Route::get('/export/pdf', [\App\Http\Controllers\MonitoringReportController::class, 'exportPdf'])->name('export.pdf');
    });
});
