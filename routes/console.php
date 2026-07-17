<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── ChainGuard Automated Data Refresh Schedule ─────────────────
//
// Cara menjalankan scheduler di XAMPP (Windows):
//   php artisan schedule:work   (foreground, untuk development)
//
// Cara menjalankan di server Linux (crontab):
//   * * * * * cd /path/to/chainguard && php artisan schedule:run >> /dev/null 2>&1

// 1. Kurs mata uang — setiap 6 jam (ExchangeRate free tier: 1500 req/bln)
Schedule::command('chainguard:fetch-currency')
    ->everySixHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// 2. Data cuaca — setiap 1 jam (Open-Meteo gratis, tidak ada limit)
Schedule::command('chainguard:fetch-weather')
    ->hourly()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// 3. Berita — setiap 3 jam (GNews free tier: 100 req/hari)
Schedule::command('chainguard:fetch-news')
    ->everyThreeHours()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));

// 4. Kalkulasi risk score — setiap 1 jam, 30 menit setelah cuaca
//    (dijalankan setelah cuaca selesai agar pakai data terbaru)
Schedule::command('chainguard:calculate-risk')
    ->hourlyAt(30)
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));
