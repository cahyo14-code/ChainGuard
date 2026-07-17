<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\RiskScore;
use App\Models\RiskHistory;
use Illuminate\Http\Request;

class RiskApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /api/risk
     * Semua risk score terkini, opsional filter level & region
     */
    public function index(Request $request)
    {
        $query = RiskScore::with('country')
            ->orderBy('total_risk', 'desc');

        // filter by level: High | Medium | Low
        if ($request->filled('level')) {
            $query->where('risk_level', ucfirst(strtolower($request->level)));
        }

        // filter by region
        if ($request->filled('region')) {
            $query->whereHas('country', function ($q) use ($request) {
                $q->where('region', $request->region);
            });
        }

        $scores = $query->get()->map(function ($score) {
            return [
                'country_id'             => $score->country_id,
                'country_name'           => $score->country?->name,
                'country_code'           => $score->country?->code,
                'flag_url'               => $score->country?->flag_url,
                'weather_risk'           => (float) $score->weather_risk,
                'inflation_risk'         => (float) $score->inflation_risk,
                'news_risk'              => (float) $score->news_risk,
                'currency_risk'          => (float) $score->currency_risk,
                'total_risk'             => (float) $score->total_risk,
                'risk_level'             => $score->risk_level,
                'weather_description'    => $score->weather_description,
                'inflation_description'  => $score->inflation_description,
                'news_description'       => $score->news_description,
                'currency_description'   => $score->currency_description,
                'calculated_at'          => $score->calculated_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'count'  => $scores->count(),
            'data'   => $scores,
        ]);
    }

    /**
     * GET /api/risk/{code}
     * Risk score detail + riwayat trend untuk 1 negara
     */
    public function show(string $code)
    {
        $country = Country::where('code', strtoupper($code))->firstOrFail();

        $score = RiskScore::where('country_id', $country->id)->first();

        if (!$score) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Risk score untuk negara ini belum tersedia',
            ], 404);
        }

        // riwayat 30 hari untuk trend chart
        $histories = RiskHistory::where('country_id', $country->id)
            ->orderBy('recorded_date', 'asc')
            ->where('recorded_date', '>=', now()->subDays(30)->toDateString())
            ->get();

        return response()->json([
            'status'  => 'success',
            'country' => $country->name,
            'code'    => $country->code,
            'score'   => [
                'weather_risk'           => (float) $score->weather_risk,
                'inflation_risk'         => (float) $score->inflation_risk,
                'news_risk'              => (float) $score->news_risk,
                'currency_risk'          => (float) $score->currency_risk,
                'total_risk'             => (float) $score->total_risk,
                'risk_level'             => $score->risk_level,
                'weather_description'    => $score->weather_description,
                'inflation_description'  => $score->inflation_description,
                'news_description'       => $score->news_description,
                'currency_description'   => $score->currency_description,
                'calculated_at'          => $score->calculated_at?->toDateTimeString(),
            ],
            'trend' => [
                'labels'       => $histories->pluck('recorded_date')->map(fn($d) => $d->format('d M'))->values(),
                'total_risk'   => $histories->pluck('total_risk')->map(fn($v) => (float) $v)->values(),
                'weather_risk' => $histories->pluck('weather_risk')->map(fn($v) => (float) $v)->values(),
                'news_risk'    => $histories->pluck('news_risk')->map(fn($v) => (float) $v)->values(),
            ],
        ]);
    }

    /**
     * GET /api/risk/distribution
     * Jumlah negara per level risiko (untuk doughnut chart)
     */
    public function distribution()
    {
        $high   = RiskScore::where('risk_level', 'High')->count();
        $medium = RiskScore::where('risk_level', 'Medium')->count();
        $low    = RiskScore::where('risk_level', 'Low')->count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'labels' => ['High Risk', 'Medium Risk', 'Low Risk'],
                'values' => [$high, $medium, $low],
                'colors' => ['#dc3545', '#ffc107', '#28a745'],
            ],
        ]);
    }

    /**
     * GET /api/risk/top
     * Top N negara risiko tertinggi (default 10)
     */
    public function top(Request $request)
    {
        $limit = (int) $request->get('limit', 10);
        $limit = min(max($limit, 5), 25);

        $scores = RiskScore::with('country')
            ->orderBy('total_risk', 'desc')
            ->take($limit)
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'labels'       => $scores->map(fn($s) => $s->country?->name ?? '-')->values(),
                'total_risk'   => $scores->pluck('total_risk')->map(fn($v) => (float) $v)->values(),
                'weather_risk' => $scores->pluck('weather_risk')->map(fn($v) => (float) $v)->values(),
                'inflation_risk' => $scores->pluck('inflation_risk')->map(fn($v) => (float) $v)->values(),
                'news_risk'    => $scores->pluck('news_risk')->map(fn($v) => (float) $v)->values(),
                'currency_risk'=> $scores->pluck('currency_risk')->map(fn($v) => (float) $v)->values(),
                'levels'       => $scores->pluck('risk_level')->values(),
            ],
        ]);
    }
}
