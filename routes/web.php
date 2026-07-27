<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RouteSimulatorController;
use App\Http\Controllers\Api\CountryApiController;
use App\Http\Controllers\Api\CurrencyApiController;
use App\Http\Controllers\Api\RiskApiController;
use App\Http\Controllers\Api\NewsApiController;
use App\Http\Controllers\Api\PortApiController;
use App\Http\Controllers\Api\CompareApiController;

// Route rahasia untuk migrasi & seed database Clever Cloud otomatis (tanpa perlu login)
Route::get('/run-migrate-force', function () {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        return '<div style="font-family:sans-serif; text-align:center; padding:50px;">
                    <h1 style="color:#28a745;">✅ SUKSES! Database & Tabel di Clever Cloud Berhasil Dibuat!</h1>
                    <p style="font-size:18px;">Semua tabel dan data awal sudah aktif di cloud.</p>
                    <a href="/login" style="background:#0d6efd; color:white; padding:12px 24px; border-radius:8px; text-decoration:none; font-weight:bold;">👉 Klik di sini untuk Halaman Login</a>
                </div>';
    } catch (\Throwable $e) {
        return '<div style="font-family:sans-serif; text-align:center; padding:50px; color:#dc3545;">
                    <h1>❌ Gagal Migrasi Database</h1>
                    <p>Error: ' . htmlspecialchars($e->getMessage()) . '</p>
                </div>';
    }
});

// Auth routes
Auth::routes();

// Protected routes (harus login)
Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/home', [DashboardController::class, 'index'])->name('home');

    // Countries
    Route::get('/countries', [CountryController::class, 'index'])->name('countries.index');
    Route::get('/countries/compare', [CountryController::class, 'compare'])->name('countries.compare');
    Route::get('/countries/{code}', [CountryController::class, 'show'])->name('countries.show');

    // Risk
    Route::get('/risk', [RiskController::class, 'index'])->name('risk.index');
    Route::get('/risk/{code}', [RiskController::class, 'show'])->name('risk.show');

    // Weather
    Route::get('/weather', [WeatherController::class, 'index'])->name('weather.index');

    // Currency
    Route::get('/currency', [CurrencyController::class, 'index'])->name('currency.index');

    // News
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');

    // Ports
    Route::get('/ports', [PortController::class, 'index'])->name('ports.index');

    // Route Simulator
    Route::get('/route-simulator', [RouteSimulatorController::class, 'index'])->name('simulator.index');
    Route::get('/route-simulator/history', [RouteSimulatorController::class, 'history'])->name('simulator.history');
    Route::delete('/route-simulator/{shipment}', [RouteSimulatorController::class, 'destroy'])->name('simulator.destroy');
    Route::post('/api/route-simulator/calculate', [RouteSimulatorController::class, 'calculate'])->name('api.simulator.calculate');
    Route::get('/api/route-simulator/ports/{countryId}', [RouteSimulatorController::class, 'portsByCountry'])->name('api.simulator.ports');
    Route::post('/api/route-simulator/{shipment}/complete', [RouteSimulatorController::class, 'complete'])->name('api.simulator.complete');
    Route::get('/api/route-simulator/{shipment}', [RouteSimulatorController::class, 'show'])->name('api.simulator.show');

    // Watchlist
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::post('/watchlist/{country}', [WatchlistController::class, 'store'])->name('watchlist.store');
    Route::delete('/watchlist/{country}', [WatchlistController::class, 'destroy'])->name('watchlist.destroy');

    // Admin (Harus role Admin)
    Route::middleware(['admin'])->group(function () {
        Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
        Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
        Route::post('/admin/articles', [AdminController::class, 'storeArticle'])->name('admin.articles.store');
        Route::delete('/admin/articles/{article}', [AdminController::class, 'destroyArticle'])->name('admin.articles.destroy');
        Route::patch('/admin/articles/{article}/status', [AdminController::class, 'updateArticleStatus'])->name('admin.articles.status');
        Route::get('/admin/data-status', [AdminController::class, 'dataStatus'])->name('admin.data.status');
        Route::post('/admin/refresh', [AdminController::class, 'refresh'])->name('admin.refresh');
    });

    // =========================================================
    // REST API Routes (JSON — dipakai oleh AJAX & Chart.js)
    // =========================================================
    Route::prefix('api')->name('api.')->group(function () {

        // Countries API
        Route::get('/countries', [CountryApiController::class, 'index'])->name('countries.index');
        Route::get('/countries/{code}', [CountryApiController::class, 'show'])->name('countries.show');

        // Currency API
        Route::get('/currency', [CurrencyApiController::class, 'index'])->name('currency.index');
        Route::get('/currency/compare', [CurrencyApiController::class, 'compare'])->name('currency.compare');
        Route::get('/currency/{code}', [CurrencyApiController::class, 'history'])->name('currency.history');

        // Risk API
        Route::get('/risk', [RiskApiController::class, 'index'])->name('risk.index');
        Route::get('/risk/distribution', [RiskApiController::class, 'distribution'])->name('risk.distribution');
        Route::get('/risk/top', [RiskApiController::class, 'top'])->name('risk.top');
        Route::get('/risk/{code}', [RiskApiController::class, 'show'])->name('risk.show');

        // News API
        Route::get('/news', [NewsApiController::class, 'index'])->name('news.index');

        // Ports API
        Route::get('/ports', [PortApiController::class, 'index'])->name('ports.index');

        // Compare API
        Route::get('/compare', [CompareApiController::class, 'compare'])->name('compare');
    });
});

// Redirect root ke dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});