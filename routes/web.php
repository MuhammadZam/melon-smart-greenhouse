<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Sistem Monitoring Tanaman Azolla
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

// Rute untuk Pengguna yang Sudah Login (Authenticated)
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Menu Navigasi Terstruktur (Section Anchors ke Dashboard Phase 4)
    Route::get('/monitorings', fn() => redirect('/dashboard#recent-table'))->name('monitorings.index');
    Route::get('/plants', fn() => redirect('/dashboard#plant-summary'))->name('plants.index');
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
});
