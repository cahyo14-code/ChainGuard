<?php

namespace App\Services;

use App\Models\Country;
use App\Models\WeatherData;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WeatherService
{
    protected $baseUrl = 'https://api.open-meteo.com/v1/forecast';

    public function fetchAndStoreWeather(Country $country)
    {
        try {
            // skip kalau tidak ada koordinat
            if (!$country->latitude || !$country->longitude) {
                return false;
            }

            $response = Http::timeout(30)->get($this->baseUrl, [
                'latitude'       => $country->latitude,
                'longitude'      => $country->longitude,
                'current'        => 'temperature_2m,precipitation,wind_speed_10m,weather_code',
                'forecast_days'  => 1,
            ]);

            if (!$response->successful()) {
                Log::error("Open-Meteo gagal untuk {$country->name}: " . $response->status());
                return false;
            }

            $data     = $response->json();
            $current  = $data['current'] ?? [];

            $temperature  = $current['temperature_2m'] ?? null;
            $rainfall     = $current['precipitation'] ?? null;
            $windSpeed    = $current['wind_speed_10m'] ?? null;
            $weatherCode  = $current['weather_code'] ?? 0;

            // tentukan kondisi cuaca dari weather code
            $weatherCondition = $this->getWeatherCondition($weatherCode);

            // tentukan storm risk
            $stormRisk = in_array($weatherCode, [95, 96, 99]); // kode badai

            // hitung risk level cuaca
            $riskLevel = $this->calculateWeatherRisk($rainfall, $windSpeed, $stormRisk);

            WeatherData::updateOrCreate(
                ['country_id' => $country->id],
                [
                    'temperature'       => $temperature,
                    'rainfall'          => $rainfall,
                    'wind_speed'        => $windSpeed,
                    'storm_risk'        => $stormRisk,
                    'weather_condition' => $weatherCondition,
                    'risk_level'        => $riskLevel,
                    'fetched_at'        => now(),
                ]
            );

            return true;

        } catch (\Exception $e) {
            Log::error("WeatherService error untuk {$country->name}: " . $e->getMessage());
            return false;
        }
    }

    // ambil cuaca untuk semua negara
    public function fetchAllCountries()
    {
        $countries = Country::whereNotNull('latitude')
                            ->whereNotNull('longitude')
                            ->get();

        $success = 0;
        $failed  = 0;

        foreach ($countries as $country) {
            $result = $this->fetchAndStoreWeather($country);
            $result ? $success++ : $failed++;

            // jeda 100ms supaya tidak spam API
            usleep(100000);
        }

        Log::info("WeatherService selesai: {$success} berhasil, {$failed} gagal");
        return ['success' => $success, 'failed' => $failed];
    }

    private function getWeatherCondition(int $code): string
    {
        if ($code === 0)                          return 'Clear';
        if (in_array($code, [1, 2, 3]))           return 'Cloudy';
        if (in_array($code, [45, 48]))            return 'Foggy';
        if (in_array($code, [51, 53, 55]))        return 'Drizzle';
        if (in_array($code, [61, 63, 65]))        return 'Rainy';
        if (in_array($code, [71, 73, 75, 77]))    return 'Snowy';
        if (in_array($code, [80, 81, 82]))        return 'Shower';
        if (in_array($code, [95, 96, 99]))        return 'Stormy';
        return 'Unknown';
    }

    private function calculateWeatherRisk($rainfall, $windSpeed, $stormRisk): string
    {
        if ($stormRisk)           return 'High';
        if ($rainfall > 50)       return 'High';
        if ($windSpeed > 60)      return 'High';
        if ($rainfall > 20)       return 'Medium';
        if ($windSpeed > 30)      return 'Medium';
        return 'Low';
    }
}