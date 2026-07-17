<?php

namespace App\Http\Controllers;

use App\Models\Port;
use App\Models\Country;

class PortController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        // statistik ringkasan
        $totalPorts   = Port::count();
        $activePorts  = Port::where('is_active', true)->count();
        $totalCountries = Port::distinct('country_id')->count('country_id');

        // daftar negara yang punya pelabuhan (untuk dropdown filter)
        $countries = Country::whereHas('ports')
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'flag_url']);

        // semua port aktif dengan koordinat (untuk Leaflet — dikirim ke view sebagai JSON)
        $ports = Port::with('country')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('is_active', true)
            ->get()
            ->map(function ($port) {
                return [
                    'id'           => $port->id,
                    'name'         => $port->name,
                    'code'         => $port->code,
                    'city'         => $port->city,
                    'type'         => $port->type,
                    'lat'          => (float) $port->latitude,
                    'lng'          => (float) $port->longitude,
                    'country_name' => $port->country?->name,
                    'country_code' => $port->country?->code,
                    'flag_url'     => $port->country?->flag_url,
                ];
            });

        return view('ports.index', compact(
            'totalPorts',
            'activePorts',
            'totalCountries',
            'countries',
            'ports'
        ));
    }
}
