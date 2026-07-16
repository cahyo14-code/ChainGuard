<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\RiskScore;
use App\Models\WeatherData;
use App\Models\CurrencyRate;
use App\Models\EconomicIndicator;
use App\Models\NewsCache;
use App\Models\Watchlist;

class CountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $countries = Country::with(['riskScore'])
            ->orderBy('name')
            ->paginate(20);

        $regions = Country::whereNotNull('region')
            ->distinct()
            ->pluck('region')
            ->sort();

        return view('countries.index', compact('countries', 'regions'));
    }

    public function show($code)
    {
        $country = Country::where('code', $code)->firstOrFail();

        $riskScore      = RiskScore::where('country_id', $country->id)->first();
        $weather        = WeatherData::where('country_id', $country->id)->latest()->first();
        $currencyRate   = CurrencyRate::where('country_id', $country->id)->first();
        $economicData   = EconomicIndicator::where('country_id', $country->id)
                            ->orderBy('year', 'desc')->take(5)->get();
        $news           = NewsCache::where('country_id', $country->id)
                            ->orderBy('fetched_at', 'desc')->take(5)->get();
        $isWatchlisted  = Watchlist::where('user_id', auth()->id())
                            ->where('country_id', $country->id)->exists();

        return view('countries.show', compact(
            'country', 'riskScore', 'weather',
            'currencyRate', 'economicData', 'news', 'isWatchlisted'
        ));
    }

    public function compare()
    {
        $countries = Country::orderBy('name')->get(['id', 'code', 'name']);
        return view('countries.compare', compact('countries'));
    }
}