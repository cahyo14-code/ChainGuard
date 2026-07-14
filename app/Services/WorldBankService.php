<?php

namespace App\Services;

use App\Models\Country;
use App\Models\EconomicIndicator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorldBankService
{
    protected $baseUrl = 'https://api.worldbank.org/v2';

    // indikator World Bank
    protected $indicators = [
        'gdp'       => 'NY.GDP.MKTP.CD',  // GDP current USD
        'inflation' => 'FP.CPI.TOTL.ZG',  // Inflasi %
        'population'=> 'SP.POP.TOTL',      // Populasi
        'exports'   => 'NE.EXP.GNFS.CD',  // Ekspor USD
        'imports'   => 'NE.IMP.GNFS.CD',  // Impor USD
    ];

    public function fetchAndStoreByCountry(Country $country)
    {
        try {
            $data = [];

            foreach ($this->indicators as $key => $indicator) {
                $response = Http::timeout(30)->get(
                    "{$this->baseUrl}/country/{$country->code}/indicator/{$indicator}",
                    [
                        'format'    => 'json',
                        'mrv'       => 5, // 5 tahun terakhir
                        'per_page'  => 5,
                    ]
                );

                if (!$response->successful()) continue;

                $json = $response->json();

                if (!isset($json[1]) || !is_array($json[1])) continue;

                foreach ($json[1] as $record) {
                    if (empty($record['value'])) continue;

                    $year = (int) $record['date'];
                    $value = (float) $record['value'];

                    $data[$year][$key] = $value;
                }

                // jeda antar request
                usleep(200000);
            }

            if (empty($data)) return false;

            // simpan ke tabel economic_indicators
            foreach ($data as $year => $values) {
                EconomicIndicator::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'year'       => $year,
                    ],
                    [
                        'gdp'        => $values['gdp'] ?? null,
                        'inflation'  => $values['inflation'] ?? null,
                        'population' => $values['population'] ?? null,
                        'exports'    => $values['exports'] ?? null,
                        'imports'    => $values['imports'] ?? null,
                    ]
                );
            }

            // update populasi terbaru di tabel countries
            $latestYear = max(array_keys($data));
            if (!empty($data[$latestYear]['population'])) {
                $country->update([
                    'population' => (int) $data[$latestYear]['population']
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error("WorldBankService error untuk {$country->name}: " . $e->getMessage());
            return false;
        }
    }

    public function fetchAllCountries()
    {
        $countries = Country::whereNotNull('code')->get();
        $success = 0;
        $failed  = 0;

        foreach ($countries as $country) {
            $result = $this->fetchAndStoreByCountry($country);
            $result ? $success++ : $failed++;
        }

        Log::info("WorldBankService selesai: {$success} berhasil, {$failed} gagal");
        return ['success' => $success, 'failed' => $failed];
    }
}