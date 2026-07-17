<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Port;
use App\Models\Country;
use Illuminate\Http\Request;

class PortApiController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * GET /api/ports
     * Daftar pelabuhan dengan koordinat (untuk Leaflet marker)
     */
    public function index(Request $request)
    {
        $query = Port::with('country')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_active', true);

        if ($request->filled('country_code')) {
            $query->whereHas('country', function ($q) use ($request) {
                $q->where('code', strtoupper($request->country_code));
            });
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('city', 'like', '%' . $request->search . '%');
            });
        }

        $ports = $query->orderBy('name')->get()->map(function ($port) {
            return [
                'id'           => $port->id,
                'name'         => $port->name,
                'code'         => $port->code,
                'city'         => $port->city,
                'type'         => $port->type,
                'latitude'     => (float) $port->latitude,
                'longitude'    => (float) $port->longitude,
                'country_name' => $port->country?->name,
                'country_code' => $port->country?->code,
                'flag_url'     => $port->country?->flag_url,
            ];
        });

        return response()->json([
            'status' => 'success',
            'count'  => $ports->count(),
            'data'   => $ports,
        ]);
    }
}
