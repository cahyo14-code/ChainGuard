<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\RiskScore;
use App\Models\RiskHistory;
use App\Models\EconomicIndicator;
use App\Models\WeatherData;
use App\Models\CurrencyRate;
use App\Models\NewsCache;

class RiskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // semua negara yang sudah punya risk score, urutkan dari tertinggi
        $riskScores = RiskScore::with('country')
            ->orderBy('total_risk', 'desc')
            ->paginate(25);

        // statistik distribusi
        $highCount   = RiskScore::where('risk_level', 'High')->count();
        $mediumCount = RiskScore::where('risk_level', 'Medium')->count();
        $lowCount    = RiskScore::where('risk_level', 'Low')->count();
        $totalScored = RiskScore::count();

        // rata-rata setiap komponen risiko (untuk chart radar/bar overview)
        $avgWeather   = round(RiskScore::avg('weather_risk'), 1);
        $avgInflation = round(RiskScore::avg('inflation_risk'), 1);
        $avgNews      = round(RiskScore::avg('news_risk'), 1);
        $avgCurrency  = round(RiskScore::avg('currency_risk'), 1);

        // top 10 untuk chart bar di halaman utama
        $top10 = RiskScore::with('country')
            ->orderBy('total_risk', 'desc')
            ->take(10)
            ->get();

        return view('risk.index', compact(
            'riskScores',
            'highCount',
            'mediumCount',
            'lowCount',
            'totalScored',
            'avgWeather',
            'avgInflation',
            'avgNews',
            'avgCurrency',
            'top10'
        ));
    }

    public function show(string $code)
    {
        $country = Country::where('code', strtoupper($code))->firstOrFail();

        // risk score terkini
        $riskScore = RiskScore::where('country_id', $country->id)->first();

        // riwayat risiko 30 hari terakhir untuk trend chart
        $riskHistory = RiskHistory::where('country_id', $country->id)
            ->orderBy('recorded_date', 'asc')
            ->take(30)
            ->get();

        // data pendukung
        $weather     = \App\Models\WeatherData::where('country_id', $country->id)->latest()->first();
        $economic    = EconomicIndicator::where('country_id', $country->id)
                        ->orderBy('year', 'desc')->first();
        $currency    = CurrencyRate::where('country_id', $country->id)->first();
        $newsItems   = NewsCache::where('country_id', $country->id)
                        ->orderBy('fetched_at', 'desc')->take(5)->get();

        // sentimen berita untuk pie chart
        $positiveNews = NewsCache::where('country_id', $country->id)
                        ->where('sentiment', 'Positive')->count();
        $negativeNews = NewsCache::where('country_id', $country->id)
                        ->where('sentiment', 'Negative')->count();
        $neutralNews  = NewsCache::where('country_id', $country->id)
                        ->where('sentiment', 'Neutral')->count();

        // negara sebelum & sesudah (untuk navigasi)
        $prevCountry = RiskScore::with('country')
            ->where('total_risk', '>', $riskScore?->total_risk ?? 0)
            ->orderBy('total_risk', 'asc')
            ->first()?->country;

        $nextCountry = RiskScore::with('country')
            ->where('total_risk', '<', $riskScore?->total_risk ?? 0)
            ->orderBy('total_risk', 'desc')
            ->first()?->country;

        return view('risk.show', compact(
            'country',
            'riskScore',
            'riskHistory',
            'weather',
            'economic',
            'currency',
            'newsItems',
            'positiveNews',
            'negativeNews',
            'neutralNews',
            'prevCountry',
            'nextCountry'
        ));
    }
}
