<?php

namespace App\Services;

use App\Models\Country;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CountryService
{
    public function fetchAndStoreAllCountries()
    {
        try {

           $response = Http::timeout(60)->get('https://restcountries.com/v3.1/all');
            if (!$response->successful()) {
                Log::error('RestCountries API gagal: ' . $response->status());
                return false;
            }

            $countries = $response->json();

            foreach ($countries as $data) {
                if (empty($data['cca2'])) {
                    continue;
                }

                $currencyCode = null;
                $currencyName = null;
                
                if (!empty($data['currencies']) && is_array($data['currencies'])) {

                    $currencyCode = array_key_first($data['currencies']);
                    

                    $currencyData = $data['currencies'][$currencyCode];
                    if (isset($currencyData['name'])) {
                        $currencyName = $currencyData['name'];
                    }
                }

                $population = null;
                if (isset($data['population'])) {
                    $population = (int) $data['population'];
                }

                Country::updateOrCreate(
                    ['code' => strtoupper($data['cca2'])],
                    [
                        'name'          => $data['name']['common'] ?? null,
                        'capital'       => isset($data['capital'][0]) ? $data['capital'][0] : null,
                        'currency_code' => $currencyCode,
                        'currency_name' => $currencyName, 
                        'flag_url'      => $data['flags']['png'] ?? ($data['flags']['svg'] ?? null),
                        'population'    => $population,   
                        'region'        => $data['region'] ?? null,
                        'subregion'     => $data['subregion'] ?? null,
                        'latitude'      => $data['latlng'][0] ?? null,
                        'longitude'     => $data['latlng'][1] ?? null,
                    ]
                );
            }

            $this->updateRegions();

            return true;

        } catch (\Exception $e) {
            Log::error('CountryService error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateRegions()
    {
        $regions = [
            // Southeast Asia
            'ID' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'MY' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'SG' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'TH' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'PH' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'VN' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'MM' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'KH' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'LA' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'BN' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            'TL' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'],
            
            // East Asia
            'CN' => ['region' => 'Asia', 'subregion' => 'East Asia'],
            'JP' => ['region' => 'Asia', 'subregion' => 'East Asia'],
            'KR' => ['region' => 'Asia', 'subregion' => 'East Asia'],
            'KP' => ['region' => 'Asia', 'subregion' => 'East Asia'],
            'MN' => ['region' => 'Asia', 'subregion' => 'East Asia'],
            'TW' => ['region' => 'Asia', 'subregion' => 'East Asia'],
            'HK' => ['region' => 'Asia', 'subregion' => 'East Asia'],
            'MO' => ['region' => 'Asia', 'subregion' => 'East Asia'],
            
            // South Asia
            'IN' => ['region' => 'Asia', 'subregion' => 'South Asia'],
            'PK' => ['region' => 'Asia', 'subregion' => 'South Asia'],
            'BD' => ['region' => 'Asia', 'subregion' => 'South Asia'],
            'LK' => ['region' => 'Asia', 'subregion' => 'South Asia'],
            'NP' => ['region' => 'Asia', 'subregion' => 'South Asia'],
            'AF' => ['region' => 'Asia', 'subregion' => 'South Asia'],
            'MV' => ['region' => 'Asia', 'subregion' => 'South Asia'],
            'BT' => ['region' => 'Asia', 'subregion' => 'South Asia'],
            
            // Western Asia
            'IR' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'IQ' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'SA' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'AE' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'TR' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'IL' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'JO' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'KW' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'QA' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'BH' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'OM' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'YE' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'SY' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'LB' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'PS' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'AM' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'AZ' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'GE' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            'CY' => ['region' => 'Asia', 'subregion' => 'Western Asia'],
            
            // Central Asia
            'KZ' => ['region' => 'Asia', 'subregion' => 'Central Asia'],
            'UZ' => ['region' => 'Asia', 'subregion' => 'Central Asia'],
            'TM' => ['region' => 'Asia', 'subregion' => 'Central Asia'],
            'KG' => ['region' => 'Asia', 'subregion' => 'Central Asia'],
            'TJ' => ['region' => 'Asia', 'subregion' => 'Central Asia'],

            // Western Europe
            'DE' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'FR' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'GB' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'NL' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'BE' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'CH' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'AT' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'LU' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'IE' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'MC' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'LI' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            'AD' => ['region' => 'Europe', 'subregion' => 'Western Europe'],
            
            // Southern Europe
            'PT' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'ES' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'IT' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'GR' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'HR' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'RS' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'SI' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'BA' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'ME' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'MK' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'AL' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'MT' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'SM' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            'VA' => ['region' => 'Europe', 'subregion' => 'Southern Europe'],
            
            // Eastern Europe
            'PL' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            'RU' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            'UA' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            'RO' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            'CZ' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            'HU' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            'SK' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            'BY' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            'BG' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            'MD' => ['region' => 'Europe', 'subregion' => 'Eastern Europe'],
            
            // Northern Europe
            'SE' => ['region' => 'Europe', 'subregion' => 'Northern Europe'],
            'NO' => ['region' => 'Europe', 'subregion' => 'Northern Europe'],
            'DK' => ['region' => 'Europe', 'subregion' => 'Northern Europe'],
            'FI' => ['region' => 'Europe', 'subregion' => 'Northern Europe'],
            'IS' => ['region' => 'Europe', 'subregion' => 'Northern Europe'],
            'EE' => ['region' => 'Europe', 'subregion' => 'Northern Europe'],
            'LV' => ['region' => 'Europe', 'subregion' => 'Northern Europe'],
            'LT' => ['region' => 'Europe', 'subregion' => 'Northern Europe'],

            // Northern America
            'US' => ['region' => 'Americas', 'subregion' => 'Northern America'],
            'CA' => ['region' => 'Americas', 'subregion' => 'Northern America'],
            'GL' => ['region' => 'Americas', 'subregion' => 'Northern America'],
            'PM' => ['region' => 'Americas', 'subregion' => 'Northern America'],
            
            // Central America
            'MX' => ['region' => 'Americas', 'subregion' => 'Central America'],
            'GT' => ['region' => 'Americas', 'subregion' => 'Central America'],
            'BZ' => ['region' => 'Americas', 'subregion' => 'Central America'],
            'HN' => ['region' => 'Americas', 'subregion' => 'Central America'],
            'SV' => ['region' => 'Americas', 'subregion' => 'Central America'],
            'NI' => ['region' => 'Americas', 'subregion' => 'Central America'],
            'CR' => ['region' => 'Americas', 'subregion' => 'Central America'],
            'PA' => ['region' => 'Americas', 'subregion' => 'Central America'],
            
            // South America
            'BR' => ['region' => 'Americas', 'subregion' => 'South America'],
            'AR' => ['region' => 'Americas', 'subregion' => 'South America'],
            'CL' => ['region' => 'Americas', 'subregion' => 'South America'],
            'CO' => ['region' => 'Americas', 'subregion' => 'South America'],
            'PE' => ['region' => 'Americas', 'subregion' => 'South America'],
            'VE' => ['region' => 'Americas', 'subregion' => 'South America'],
            'EC' => ['region' => 'Americas', 'subregion' => 'South America'],
            'BO' => ['region' => 'Americas', 'subregion' => 'South America'],
            'PY' => ['region' => 'Americas', 'subregion' => 'South America'],
            'UY' => ['region' => 'Americas', 'subregion' => 'South America'],
            'GY' => ['region' => 'Americas', 'subregion' => 'South America'],
            'SR' => ['region' => 'Americas', 'subregion' => 'South America'],
            'GF' => ['region' => 'Americas', 'subregion' => 'South America'],
            'FK' => ['region' => 'Americas', 'subregion' => 'South America'],
            
            // Caribbean
            'CU' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'DO' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'HT' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'JM' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'TT' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'BB' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'LC' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'VC' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'GD' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'AG' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'DM' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'KN' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'BS' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'PR' => ['region' => 'Americas', 'subregion' => 'Caribbean'],
            'TC' => ['region' => 'Americas', 'subregion' => 'Caribbean'],

            // Western Africa
            'NG' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'GH' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'SN' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'CI' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'ML' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'BF' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'NE' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'GN' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'SL' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'LR' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'MR' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'GM' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'GW' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'CV' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'TG' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'BJ' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            'SH' => ['region' => 'Africa', 'subregion' => 'Western Africa'],
            
            // Eastern Africa
            'ET' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'KE' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'TZ' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'UG' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'MZ' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'MG' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'SO' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'RW' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'BI' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'DJ' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'ER' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'KM' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'SC' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'MU' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            'MW' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'],
            
            // Southern Africa
            'ZA' => ['region' => 'Africa', 'subregion' => 'Southern Africa'],
            'ZW' => ['region' => 'Africa', 'subregion' => 'Southern Africa'],
            'ZM' => ['region' => 'Africa', 'subregion' => 'Southern Africa'],
            'BW' => ['region' => 'Africa', 'subregion' => 'Southern Africa'],
            'NA' => ['region' => 'Africa', 'subregion' => 'Southern Africa'],
            'LS' => ['region' => 'Africa', 'subregion' => 'Southern Africa'],
            'SZ' => ['region' => 'Africa', 'subregion' => 'Southern Africa'],
            
            // Northern Africa
            'EG' => ['region' => 'Africa', 'subregion' => 'Northern Africa'],
            'DZ' => ['region' => 'Africa', 'subregion' => 'Northern Africa'],
            'MA' => ['region' => 'Africa', 'subregion' => 'Northern Africa'],
            'TN' => ['region' => 'Africa', 'subregion' => 'Northern Africa'],
            'LY' => ['region' => 'Africa', 'subregion' => 'Northern Africa'],
            'SD' => ['region' => 'Africa', 'subregion' => 'Northern Africa'],
            'SS' => ['region' => 'Africa', 'subregion' => 'Northern Africa'],
            'EH' => ['region' => 'Africa', 'subregion' => 'Northern Africa'],
            
            // Middle Africa
            'CD' => ['region' => 'Africa', 'subregion' => 'Middle Africa'],
            'CM' => ['region' => 'Africa', 'subregion' => 'Middle Africa'],
            'AO' => ['region' => 'Africa', 'subregion' => 'Middle Africa'],
            'CF' => ['region' => 'Africa', 'subregion' => 'Middle Africa'],
            'CG' => ['region' => 'Africa', 'subregion' => 'Middle Africa'],
            'GA' => ['region' => 'Africa', 'subregion' => 'Middle Africa'],
            'GQ' => ['region' => 'Africa', 'subregion' => 'Middle Africa'],
            'ST' => ['region' => 'Africa', 'subregion' => 'Middle Africa'],
            'TD' => ['region' => 'Africa', 'subregion' => 'Middle Africa'],

            // Oceania
            'AU' => ['region' => 'Oceania', 'subregion' => 'Australia and New Zealand'],
            'NZ' => ['region' => 'Oceania', 'subregion' => 'Australia and New Zealand'],
            'PG' => ['region' => 'Oceania', 'subregion' => 'Melanesia'],
            'FJ' => ['region' => 'Oceania', 'subregion' => 'Melanesia'],
            'SB' => ['region' => 'Oceania', 'subregion' => 'Melanesia'],
            'VU' => ['region' => 'Oceania', 'subregion' => 'Melanesia'],
            'NC' => ['region' => 'Oceania', 'subregion' => 'Melanesia'],
            'WS' => ['region' => 'Oceania', 'subregion' => 'Polynesia'],
            'TO' => ['region' => 'Oceania', 'subregion' => 'Polynesia'],
            'TV' => ['region' => 'Oceania', 'subregion' => 'Polynesia'],
            'CK' => ['region' => 'Oceania', 'subregion' => 'Polynesia'],
            'PF' => ['region' => 'Oceania', 'subregion' => 'Polynesia'],
            'FM' => ['region' => 'Oceania', 'subregion' => 'Micronesia'],
            'PW' => ['region' => 'Oceania', 'subregion' => 'Micronesia'],
            'MH' => ['region' => 'Oceania', 'subregion' => 'Micronesia'],
            'KI' => ['region' => 'Oceania', 'subregion' => 'Micronesia'],
            'NR' => ['region' => 'Oceania', 'subregion' => 'Micronesia'],
            'GU' => ['region' => 'Oceania', 'subregion' => 'Micronesia'],

            // Penambal 39 Wilayah Khusus
            'AW' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'BM' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'VG' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'KY' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'CW' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'GP' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'MQ' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'MS' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'BL' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'MF' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'SX' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'AI' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'BQ' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'BV' => ['region' => 'Americas', 'subregion' => 'South America'], 
            'GS' => ['region' => 'Americas', 'subregion' => 'South America'], 
            'UM' => ['region' => 'Americas', 'subregion' => 'Northern America'], 
            'VI' => ['region' => 'Americas', 'subregion' => 'Caribbean'], 
            'AX' => ['region' => 'Europe', 'subregion' => 'Northern Europe'], 
            'FO' => ['region' => 'Europe', 'subregion' => 'Northern Europe'], 
            'GI' => ['region' => 'Europe', 'subregion' => 'Southern Europe'], 
            'GG' => ['region' => 'Europe', 'subregion' => 'Northern Europe'], 
            'JE' => ['region' => 'Europe', 'subregion' => 'Northern Europe'], 
            'IM' => ['region' => 'Europe', 'subregion' => 'Northern Europe'], 
            'SJ' => ['region' => 'Europe', 'subregion' => 'Northern Europe'], 
            'XK' => ['region' => 'Europe', 'subregion' => 'Southern Europe'], 
            'RE' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'], 
            'YT' => ['region' => 'Africa', 'subregion' => 'Eastern Africa'], 
            'TF' => ['region' => 'Africa', 'subregion' => 'Southern Africa'], 
            'AS' => ['region' => 'Oceania', 'subregion' => 'Polynesia'], 
            'MP' => ['region' => 'Oceania', 'subregion' => 'Micronesia'], 
            'TK' => ['region' => 'Oceania', 'subregion' => 'Polynesia'], 
            'WF' => ['region' => 'Oceania', 'subregion' => 'Polynesia'], 
            'NU' => ['region' => 'Oceania', 'subregion' => 'Polynesia'], 
            'PN' => ['region' => 'Oceania', 'subregion' => 'Polynesia'], 
            'HM' => ['region' => 'Oceania', 'subregion' => 'Melanesia'], 
            'CC' => ['region' => 'Oceania', 'subregion' => 'Melanesia'], 
            'NF' => ['region' => 'Oceania', 'subregion' => 'Melanesia'], 
            'IO' => ['region' => 'Asia', 'subregion' => 'Southern Asia'], 
            'CX' => ['region' => 'Asia', 'subregion' => 'Southeast Asia'], 
            'AQ' => ['region' => 'Antarctic', 'subregion' => 'Antarctic'], 
        ];

        foreach ($regions as $code => $data) {
            Country::where('code', $code)->update([
                'region'    => $data['region'],
                'subregion' => $data['subregion'],
            ]);
        }

        return true;
    }
}