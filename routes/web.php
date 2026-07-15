<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\WeatherController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\PortController;
use App\Http\Controllers\RiskController;
use App\Http\Controllers\WatchlistController;
use App\Http\Controllers\AdminController;

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

    // Watchlist
    Route::get('/watchlist', [WatchlistController::class, 'index'])->name('watchlist.index');
    Route::post('/watchlist/{country}', [WatchlistController::class, 'store'])->name('watchlist.store');
    Route::delete('/watchlist/{country}', [WatchlistController::class, 'destroy'])->name('watchlist.destroy');

    // Admin
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.index');
});

// Redirect root ke dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});