<?php

namespace App\Http\Controllers;

use App\Models\WeatherData;
use App\Models\Country;

class WeatherController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // statistik ringkasan
        $totalMonitored = WeatherData::count();
        $highRisk       = WeatherData::where('risk_level', 'High')->count();
        $mediumRisk     = WeatherData::where('risk_level', 'Medium')->count();
        $lowRisk        = WeatherData::where('risk_level', 'Low')->count();
        $stormCount     = WeatherData::where('storm_risk', true)->count();

        // semua data cuaca dengan koordinat negara (untuk Leaflet)
        $weatherData = WeatherData::with('country')
            ->whereHas('country', function ($q) {
                $q->whereNotNull('latitude')->whereNotNull('longitude');
            })
            ->get()
            ->map(function ($w) {
                return [
                    'country_id'        => $w->country_id,
                    'country_name'      => $w->country?->name,
                    'country_code'      => $w->country?->code,
                    'flag_url'          => $w->country?->flag_url,
                    'lat'               => (float) $w->country?->latitude,
                    'lng'               => (float) $w->country?->longitude,
                    'temperature'       => $w->temperature,
                    'rainfall'          => $w->rainfall,
                    'wind_speed'        => $w->wind_speed,
                    'storm_risk'        => (bool) $w->storm_risk,
                    'weather_condition' => $w->weather_condition,
                    'risk_level'        => $w->risk_level,
                    'fetched_at'        => $w->fetched_at?->diffForHumans(),
                ];
            });

        // cuaca ekstrem untuk tabel di bawah peta
        $extremeWeather = WeatherData::with('country')
            ->where('risk_level', 'High')
            ->orderByDesc('wind_speed')
            ->take(15)
            ->get();

        // distribusi kondisi cuaca (untuk chart)
        $conditionStats = WeatherData::selectRaw('weather_condition, COUNT(*) as count')
            ->whereNotNull('weather_condition')
            ->groupBy('weather_condition')
            ->orderByDesc('count')
            ->get();

        return view('weather.index', compact(
            'totalMonitored',
            'highRisk',
            'mediumRisk',
            'lowRisk',
            'stormCount',
            'weatherData',
            'extremeWeather',
            'conditionStats'
        ));
    }
}
