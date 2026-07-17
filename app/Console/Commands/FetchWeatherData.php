<?php

namespace App\Console\Commands;

use App\Services\WeatherService;
use App\Models\WeatherData;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchWeatherData extends Command
{
    protected $signature   = 'chainguard:fetch-weather
                                {--country= : Kode negara spesifik (opsional, contoh: ID)}
                                {--force    : Paksa fetch ulang meski data masih fresh}';

    protected $description = 'Ambil data cuaca terbaru dari Open-Meteo API untuk semua negara';

    public function handle(WeatherService $weatherService): int
    {
        $this->info('🌦  Memulai fetch data cuaca...');
        $start = now();

        if ($countryCode = $this->option('country')) {
            // fetch 1 negara spesifik
            $country = \App\Models\Country::where('code', strtoupper($countryCode))->first();
            if (!$country) {
                $this->error("Negara '{$countryCode}' tidak ditemukan.");
                return self::FAILURE;
            }
            $result = $weatherService->fetchAndStoreWeather($country);
            $this->info($result ? "✅ {$country->name} berhasil diperbarui." : "❌ {$country->name} gagal.");
            return self::SUCCESS;
        }

        // fetch semua negara
        $result  = $weatherService->fetchAllCountries();
        $elapsed = now()->diffInSeconds($start);

        $this->info("✅ Selesai dalam {$elapsed}s");
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Berhasil', $result['success']],
                ['Gagal',    $result['failed']],
            ]
        );

        Log::info('chainguard:fetch-weather selesai', $result);
        return self::SUCCESS;
    }
}
