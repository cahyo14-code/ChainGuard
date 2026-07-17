<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\RiskScore;
use Illuminate\Http\Request;

class CountryApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /api/countries
     * Daftar semua negara dengan risk score (untuk peta & tabel)
     */
    public function index(Request $request)
    {
        $query = Country::with(['riskScore'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        if ($request->filled('region')) {
            $query->where('region', $request->region);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $countries = $query->orderBy('name')->get()->map(function ($country) {
            return [
                'id'          => $country->id,
                'code'        => $country->code,
                'name'        => $country->name,
                'capital'     => $country->capital,
                'region'      => $country->region,
                'subregion'   => $country->subregion,
                'flag_url'    => $country->flag_url,
                'latitude'    => (float) $country->latitude,
                'longitude'   => (float) $country->longitude,
                'currency_code' => $country->currency_code,
                'risk_level'  => $country->riskScore?->risk_level ?? 'Unknown',
                'total_risk'  => (float) ($country->riskScore?->total_risk ?? 0),
            ];
        });

        return response()->json([
            'status' => 'success',
            'count'  => $countries->count(),
            'data'   => $countries,
        ]);
    }

    /**
     * GET /api/countries/{code}
     * Detail 1 negara
     */
    public function show(string $code)
    {
        $country = Country::with(['riskScore', 'currencyRates', 'weatherData'])
            ->where('code', strtoupper($code))
            ->firstOrFail();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'id'           => $country->id,
                'code'         => $country->code,
                'name'         => $country->name,
                'capital'      => $country->capital,
                'region'       => $country->region,
                'flag_url'     => $country->flag_url,
                'latitude'     => (float) $country->latitude,
                'longitude'    => (float) $country->longitude,
                'currency_code'=> $country->currency_code,
                'risk_level'   => $country->riskScore?->risk_level ?? 'Unknown',
                'total_risk'   => (float) ($country->riskScore?->total_risk ?? 0),
            ],
        ]);
    }
}
