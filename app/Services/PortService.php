<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Port;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PortService
{
    public function fetchAndStorePorts()
    {
        try {
            $response = Http::timeout(60)->get(
                'https://raw.githubusercontent.com/marchah/sea-ports/master/lib/ports.json'
            );

            if (!$response->successful()) {
                Log::error('Sea Ports API gagal: ' . $response->status());
                return false;
            }

            $ports = $response->json();

            if (!is_array($ports)) {
                Log::error('Sea Ports: format response tidak valid');
                return false;
            }

            $success = 0;
            $failed  = 0;

            foreach ($ports as $code => $port) {
                if (empty($port['name']) || empty($port['country'])) {
                    $failed++;
                    continue;
                }

                // cari negara berdasarkan nama negara
                $country = Country::where('name', $port['country'])->first();

                if (!$country) {
                    $failed++;
                    continue;
                }

                // koordinat format [longitude, latitude]
                $longitude = $port['coordinates'][0] ?? null;
                $latitude  = $port['coordinates'][1] ?? null;

                Port::updateOrCreate(
                    ['code' => $code],
                    [
                        'country_id' => $country->id,
                        'name'       => $port['name'],
                        'city'       => $port['city'] ?? null,
                        'latitude'   => $latitude,
                        'longitude'  => $longitude,
                        'type'       => 'Seaport',
                        'is_active'  => true,
                    ]
                );

                $success++;
            }

            Log::info("PortService selesai: {$success} berhasil, {$failed} gagal");
            return ['success' => $success, 'failed' => $failed];

        } catch (\Exception $e) {
            Log::error('PortService error: ' . $e->getMessage());
            return false;
        }
    }
}