<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\RiskScore;
use App\Models\WeatherData;
use App\Models\CurrencyRate;
use App\Models\EconomicIndicator;
use App\Models\NewsCache;
use App\Models\RiskHistory;
use App\Models\CurrencyHistory;
use Illuminate\Http\Request;

class CompareApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /api/compare?codes=ID,DE,US
     * Data lengkap untuk perbandingan 2–4 negara
     */
    public function compare(Request $request)
    {
        $codes = array_filter(array_map('trim', explode(',', $request->get('codes', ''))));

        if (count($codes) < 2) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Minimal 2 kode negara diperlukan (contoh: ?codes=ID,DE)',
            ], 422);
        }

        // batasi maks 4 negara
        $codes = array_slice($codes, 0, 4);

        $result = [];

        foreach ($codes as $code) {
            $country = Country::where('code', strtoupper($code))->first();
            if (!$country) continue;

            $risk      = RiskScore::where('country_id', $country->id)->first();
            $weather   = WeatherData::where('country_id', $country->id)->latest()->first();
            $currency  = CurrencyRate::where('country_id', $country->id)->first();
            $economic  = EconomicIndicator::where('country_id', $country->id)
                            ->orderBy('year', 'desc')->first();

            // sentimen berita
            $totalNews    = NewsCache::where('country_id', $country->id)->count();
            $negativeNews = NewsCache::where('country_id', $country->id)
                            ->where('sentiment', 'Negative')->count();
            $positiveNews = NewsCache::where('country_id', $country->id)
                            ->where('sentiment', 'Positive')->count();

            // trend risk 14 hari
            $riskTrend = RiskHistory::where('country_id', $country->id)
                ->orderBy('recorded_date', 'asc')
                ->where('recorded_date', '>=', now()->subDays(14)->toDateString())
                ->get();

            // trend kurs 14 hari
            $currencyTrend = CurrencyHistory::where('country_id', $country->id)
                ->orderBy('rate_date', 'asc')
                ->where('rate_date', '>=', now()->subDays(14)->toDateString())
                ->get();

            $result[] = [
                // identitas
                'code'         => $country->code,
                'name'         => $country->name,
                'flag_url'     => $country->flag_url,
                'capital'      => $country->capital,
                'region'       => $country->region,
                'population'   => $country->population,
                'currency_code'=> $country->currency_code,

                // risk score
                'risk' => [
                    'total'      => (float) ($risk?->total_risk ?? 0),
                    'level'      => $risk?->risk_level ?? 'N/A',
                    'weather'    => (float) ($risk?->weather_risk ?? 0),
                    'inflation'  => (float) ($risk?->inflation_risk ?? 0),
                    'news'       => (float) ($risk?->news_risk ?? 0),
                    'currency'   => (float) ($risk?->currency_risk ?? 0),
                ],

                // ekonomi
                'economy' => [
                    'year'       => $economic?->year,
                    'gdp'        => $economic?->gdp ? round($economic->gdp / 1e9, 2) : null, // miliar USD
                    'inflation'  => $economic?->inflation ? round($economic->inflation, 2) : null,
                    'exports'    => $economic?->exports ? round($economic->exports / 1e9, 2) : null,
                    'imports'    => $economic?->imports ? round($economic->imports / 1e9, 2) : null,
                ],

                // cuaca
                'weather' => [
                    'condition'  => $weather?->weather_condition ?? 'N/A',
                    'temperature'=> $weather?->temperature,
                    'rainfall'   => $weather?->rainfall,
                    'wind_speed' => $weather?->wind_speed,
                    'storm_risk' => (bool) ($weather?->storm_risk ?? false),
                    'risk_level' => $weather?->risk_level ?? 'N/A',
                ],

                // kurs
                'currency' => [
                    'code' => $currency?->target_currency ?? $country->currency_code,
                    'rate' => $currency?->rate ? round($currency->rate, 4) : null,
                ],

                // berita
                'news' => [
                    'total'    => $totalNews,
                    'positive' => $positiveNews,
                    'negative' => $negativeNews,
                    'neutral'  => $totalNews - $positiveNews - $negativeNews,
                ],

                // trend data untuk chart
                'risk_trend' => [
                    'labels' => $riskTrend->pluck('recorded_date')->map(fn($d) => $d->format('d M'))->values(),
                    'values' => $riskTrend->pluck('total_risk')->map(fn($v) => (float)$v)->values(),
                ],
                'currency_trend' => [
                    'labels' => $currencyTrend->pluck('rate_date')->map(fn($d) => $d->format('d M'))->values(),
                    'values' => $currencyTrend->pluck('rate')->map(fn($v) => (float)$v)->values(),
                ],
            ];
        }

        return response()->json([
            'status' => 'success',
            'count'  => count($result),
            'data'   => $result,
        ]);
    }
}
