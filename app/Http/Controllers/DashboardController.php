<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\RiskScore;
use App\Models\WeatherData;
use App\Models\NewsCache;
use App\Models\Port;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // statistik utama
        $totalCountries = Country::count();
        $totalPorts     = Port::count();
        $totalNews      = NewsCache::count();

        // risk summary
        $highRisk   = RiskScore::where('risk_level', 'High')->count();
        $mediumRisk = RiskScore::where('risk_level', 'Medium')->count();
        $lowRisk    = RiskScore::where('risk_level', 'Low')->count();

        // top 10 negara risiko tertinggi
        $topRiskCountries = RiskScore::with('country')
            ->orderBy('total_risk', 'desc')
            ->take(10)
            ->get();

        // berita terbaru
        $latestNews = NewsCache::with('country')
            ->orderBy('fetched_at', 'desc')
            ->take(5)
            ->get();

        // cuaca ekstrem
        $extremeWeather = WeatherData::with('country')
            ->where('risk_level', 'High')
            ->take(5)
            ->get();

        return view('dashboard.index', compact(
            'totalCountries',
            'totalPorts',
            'totalNews',
            'highRisk',
            'mediumRisk',
            'lowRisk',
            'topRiskCountries',
            'latestNews',
            'extremeWeather'
        ));
    }
}