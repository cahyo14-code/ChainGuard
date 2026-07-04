<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CurrencyRate;
use App\Models\CurrencyHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExchangeRateService
{
    protected $baseUrl = 'https://v6.exchangerate-api.com/v6';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('EXCHANGE_RATE_API_KEY');
    }

    // ambil kurs terkini untuk semua negara
    public function fetchAndStoreRates()
    {
        try {
            $response = Http::timeout(30)->get("{$this->baseUrl}/{$this->apiKey}/latest/USD");

            if (!$response->successful()) {
                Log::error('ExchangeRate API gagal: ' . $response->status());
                return false;
            }

            $data = $response->json();

            if ($data['result'] !== 'success') {
                Log::error('ExchangeRate API error: ' . json_encode($data));
                return false;
            }

            $rates = $data['conversion_rates'];
            $today = now()->toDateString();

            // simpan kurs untuk setiap negara yang ada di database
            $countries = Country::whereNotNull('currency_code')->get();

            foreach ($countries as $country) {
                $currencyCode = strtoupper($country->currency_code);

                if (!isset($rates[$currencyCode])) {
                    continue;
                }

                $rate = $rates[$currencyCode];

                // update kurs terkini
                CurrencyRate::updateOrCreate(
                    ['country_id' => $country->id],
                    [
                        'base_currency'   => 'USD',
                        'target_currency' => $currencyCode,
                        'rate'            => $rate,
                        'fetched_at'      => now(),
                    ]
                );

                // simpan ke riwayat (hanya 1 kali per hari)
                CurrencyHistory::updateOrCreate(
                    [
                        'country_id'  => $country->id,
                        'rate_date'   => $today,
                    ],
                    [
                        'base_currency'   => 'USD',
                        'target_currency' => $currencyCode,
                        'rate'            => $rate,
                    ]
                );
            }

            return true;

        } catch (\Exception $e) {
            Log::error('ExchangeRateService error: ' . $e->getMessage());
            return false;
        }
    }

    // ambil kurs untuk 1 negara spesifik
    public function getRateForCountry(Country $country)
    {
        return CurrencyRate::where('country_id', $country->id)->first();
    }
}