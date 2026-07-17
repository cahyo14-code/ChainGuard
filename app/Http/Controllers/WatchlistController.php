<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Models\Country;
use App\Models\RiskScore;
use App\Models\WeatherData;
use App\Models\CurrencyRate;
use Illuminate\Http\Request;

class WatchlistController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // ambil watchlist milik user yang login, beserta data terkait
        $watchlists = Watchlist::where('user_id', auth()->id())
            ->with(['country.riskScore', 'country.weatherData', 'country.currencyRates'])
            ->orderBy('created_at', 'desc')
            ->get();

        // enrich data untuk tampilan
        $watchlistData = $watchlists->map(function ($wl) {
            $country  = $wl->country;
            $risk     = $country?->riskScore;
            $weather  = $country?->weatherData?->first();
            $currency = $country?->currencyRates?->first();

            return [
                'watchlist_id' => $wl->id,
                'country'      => $country,
                'notes'        => $wl->notes,
                'added_at'     => $wl->created_at,
                'risk_level'   => $risk?->risk_level ?? 'N/A',
                'total_risk'   => $risk?->total_risk ?? '-',
                'weather_condition' => $weather?->weather_condition ?? '-',
                'weather_risk' => $weather?->risk_level ?? '-',
                'currency_code'=> $currency?->target_currency ?? $country?->currency_code ?? '-',
                'currency_rate'=> $currency?->rate ?? '-',
            ];
        });

        // semua negara (untuk modal tambah watchlist)
        $allCountries = Country::orderBy('name')
            ->get(['id', 'code', 'name', 'flag_url', 'currency_code']);

        // ID negara yang sudah di-watchlist (untuk disable tombol)
        $watchlistedIds = $watchlists->pluck('country_id')->toArray();

        return view('watchlist.index', compact(
            'watchlistData',
            'allCountries',
            'watchlistedIds'
        ));
    }

    public function store(Request $request, Country $country)
    {
        // cegah duplikat
        $exists = Watchlist::where('user_id', auth()->id())
            ->where('country_id', $country->id)
            ->exists();

        if ($exists) {
            return back()->with('info', "{$country->name} sudah ada di watchlist kamu.");
        }

        Watchlist::create([
            'user_id'    => auth()->id(),
            'country_id' => $country->id,
            'notes'      => $request->input('notes'),
        ]);

        return back()->with('success', "{$country->name} ditambahkan ke watchlist.");
    }

    public function destroy(Country $country)
    {
        Watchlist::where('user_id', auth()->id())
            ->where('country_id', $country->id)
            ->delete();

        return back()->with('success', "{$country->name} dihapus dari watchlist.");
    }
}
