<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CurrencyRate;
use App\Models\CurrencyHistory;
use Illuminate\Http\Request;

class CurrencyApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /api/currency
     * Semua kurs terkini dengan opsional filter region
     */
    public function index(Request $request)
    {
        $query = CurrencyRate::with('country')
            ->orderBy('rate', 'asc');

        // filter by region
        if ($request->filled('region')) {
            $query->whereHas('country', function ($q) use ($request) {
                $q->where('region', $request->region);
            });
        }

        $rates = $query->get()->map(function ($rate) {
            return [
                'country_id'      => $rate->country_id,
                'country_name'    => $rate->country?->name,
                'country_code'    => $rate->country?->code,
                'flag_url'        => $rate->country?->flag_url,
                'base_currency'   => $rate->base_currency,
                'target_currency' => $rate->target_currency,
                'rate'            => (float) $rate->rate,
                'fetched_at'      => $rate->fetched_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'count'  => $rates->count(),
            'data'   => $rates,
        ]);
    }

    /**
     * GET /api/currency/{code}
     * Riwayat kurs untuk 1 negara (untuk Chart.js line chart)
     */
    public function history(string $code, Request $request)
    {
        $country = Country::where('code', strtoupper($code))->firstOrFail();

        $days = (int) $request->get('days', 30);
        $days = min(max($days, 7), 90); // batasi 7–90 hari

        $histories = CurrencyHistory::where('country_id', $country->id)
            ->orderBy('rate_date', 'asc')
            ->when($days, function ($q) use ($days) {
                $q->where('rate_date', '>=', now()->subDays($days)->toDateString());
            })
            ->get();

        // data untuk Chart.js
        $labels = $histories->pluck('rate_date')->map(fn($d) => $d->format('d M'));
        $rates  = $histories->pluck('rate')->map(fn($r) => (float) $r);

        // hitung perubahan
        $currentRate = $rates->last() ?? 0;
        $oldestRate  = $rates->first() ?? 0;
        $changePct   = $oldestRate > 0
            ? round((($currentRate - $oldestRate) / $oldestRate) * 100, 2)
            : 0;

        return response()->json([
            'status'          => 'success',
            'country'         => $country->name,
            'currency'        => $histories->first()?->target_currency ?? '-',
            'current_rate'    => $currentRate,
            'change_pct'      => $changePct,
            'direction'       => $changePct >= 0 ? 'up' : 'down',
            'chart' => [
                'labels' => $labels->values(),
                'rates'  => $rates->values(),
            ],
        ]);
    }

    /**
     * GET /api/currency/compare
     * Bandingkan kurs beberapa negara sekaligus
     * Query param: codes=ID,US,DE (pisah koma)
     */
    public function compare(Request $request)
    {
        $codes = array_filter(array_map('trim', explode(',', $request->get('codes', ''))));

        if (empty($codes)) {
            return response()->json(['status' => 'error', 'message' => 'Parameter codes diperlukan'], 422);
        }

        $result = [];

        foreach ($codes as $code) {
            $country = Country::where('code', strtoupper($code))->first();
            if (!$country) continue;

            $histories = CurrencyHistory::where('country_id', $country->id)
                ->orderBy('rate_date', 'asc')
                ->where('rate_date', '>=', now()->subDays(30)->toDateString())
                ->get();

            $result[] = [
                'country'  => $country->name,
                'code'     => $country->code,
                'currency' => $histories->first()?->target_currency ?? '-',
                'labels'   => $histories->pluck('rate_date')->map(fn($d) => $d->format('d M'))->values(),
                'rates'    => $histories->pluck('rate')->map(fn($r) => (float) $r)->values(),
            ];
        }

        return response()->json([
            'status' => 'success',
            'data'   => $result,
        ]);
    }
}
