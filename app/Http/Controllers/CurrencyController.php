<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\CurrencyRate;
use App\Models\CurrencyHistory;

class CurrencyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // ambil semua kurs terkini beserta data negara
        $currencyRates = CurrencyRate::with('country')
            ->orderBy('rate', 'asc')
            ->paginate(30);

        // statistik ringkasan
        $totalCurrencies = CurrencyRate::count();

        // ambil 5 mata uang dengan volatilitas tertinggi (perubahan 7 hari)
        $volatileCountries = $this->getTopVolatile(5);

        // daftar negara untuk dropdown filter grafik
        $countries = Country::whereHas('currencyRates')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'currency_code']);

        return view('currency.index', compact(
            'currencyRates',
            'totalCurrencies',
            'volatileCountries',
            'countries'
        ));
    }

    private function getTopVolatile(int $limit): \Illuminate\Support\Collection
    {
        $rates = CurrencyRate::with('country')->get();
        $volatile = [];

        foreach ($rates as $rate) {
            $histories = CurrencyHistory::where('country_id', $rate->country_id)
                ->orderBy('rate_date', 'desc')
                ->take(7)
                ->get();

            if ($histories->count() < 2) {
                continue;
            }

            $latest  = $histories->first()->rate;
            $oldest  = $histories->last()->rate;
            $change  = $oldest > 0 ? (($latest - $oldest) / $oldest) * 100 : 0;

            $volatile[] = [
                'country'    => $rate->country,
                'currency'   => $rate->target_currency,
                'rate'       => $rate->rate,
                'change_pct' => round($change, 2),
                'direction'  => $change >= 0 ? 'up' : 'down',
            ];
        }

        // urutkan berdasarkan perubahan absolut tertinggi
        usort($volatile, fn($a, $b) => abs($b['change_pct']) <=> abs($a['change_pct']));

        return collect(array_slice($volatile, 0, $limit));
    }
}
