<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CountryService
{
    protected $baseUrl = 'https://countriesnow.space/api/v0.1';

    public function fetchAndStoreAllCountries()
    {
        try {
            $response = Http::timeout(60)->get("{$this->baseUrl}/countries/info", [
                'returns' => 'name,iso2,capital,region,currency,flag,population'
            ]);

            if (!$response->successful()) {
                Log::error('CountryNow API gagal: ' . $response->status());
                return false;
            }

            $responseData = $response->json();

            if (!isset($responseData['data']) || !is_array($responseData['data'])) {
                Log::error('CountryNow API: format response tidak valid');
                return false;
            }

            $countries = $responseData['data'];

            foreach ($countries as $data) {
                if (empty($data['iso2']) || empty($data['name'])) {
                    continue;
                }

                Country::updateOrCreate(
                    ['code' => $data['iso2']],
                    [
                        'name'          => $data['name'],
                        'capital'       => $data['capital'] ?? null,
                        'region'        => $data['region'] ?? null,
                        'currency_code' => $data['currency'] ?? null,
                        'flag_url'      => $data['flag'] ?? null,
                        'population'    => $data['population'] ?? null,
                    ]
                );
            }

            return true;

        } catch (\Exception $e) {
            Log::error('CountryService error: ' . $e->getMessage());
            return false;
        }
    }
}