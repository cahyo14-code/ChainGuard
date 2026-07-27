<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Port;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PortService
{
    /**
     * Alias manual untuk negara yang penulisan namanya beda antara
     * dataset "sea-ports" (marchah/sea-ports) dengan RestCountries API
     * (yang dipakai CountryService untuk isi kolom `countries.name`).
     *
     * Key   = nama persis seperti muncul di ports.json (field "country")
     * Value = nama persis seperti tersimpan di kolom countries.name (RestCountries "common" name)
     */
    private const COUNTRY_ALIASES = [
        'Korea, South'                 => 'South Korea',
        'Korea, North'                 => 'North Korea',
        'South Korea'                  => 'South Korea',
        'North Korea'                  => 'North Korea',
        'Ivory Coast'                  => "Côte d'Ivoire",
        'Cote dIvoire'                 => "Côte d'Ivoire",
        'Vietnam'                      => 'Vietnam',
        'Viet Nam'                     => 'Vietnam',
        'USA'                          => 'United States',
        'United States of America'     => 'United States',
        'UK'                           => 'United Kingdom',
        'Great Britain'                => 'United Kingdom',
        'Congo (Kinshasa)'             => 'DR Congo',
        'Congo, Dem. Rep.'             => 'DR Congo',
        'Democratic Republic of the Congo' => 'DR Congo',
        'Congo (Brazzaville)'          => 'Republic of the Congo',
        'Congo, Rep.'                  => 'Republic of the Congo',
        'Myanmar (Burma)'              => 'Myanmar',
        'Burma'                        => 'Myanmar',
        'Czech Republic'               => 'Czechia',
        'Macedonia'                    => 'North Macedonia',
        'Swaziland'                    => 'Eswatini',
        'Cape Verde'                   => 'Cabo Verde',
        'East Timor'                   => 'Timor-Leste',
        'Brunei Darussalam'            => 'Brunei',
        'Syrian Arab Republic'         => 'Syria',
        'Iran, Islamic Rep.'           => 'Iran',
        'Bolivia (Plurinational State of)' => 'Bolivia',
        'Venezuela, RB'                => 'Venezuela',
        'Venezuela (Bolivarian Republic of)' => 'Venezuela',
        'Tanzania, United Republic of' => 'Tanzania',
        'Moldova, Republic of'         => 'Moldova',
        'Russian Federation'           => 'Russia',
        'Laos'                         => 'Laos',
        "Lao People's Democratic Republic" => 'Laos',
        'UAE'                          => 'United Arab Emirates',
        'Palestine, State of'          => 'Palestine',
        'Slovak Republic'              => 'Slovakia',
        'Turkiye'                      => 'Turkey',
        'Türkiye'                      => 'Turkey',
        'Cabo Verde'                   => 'Cabo Verde',
    ];

    /** @var array<string,\App\Models\Country> Cache lookup by normalized name (dalam satu request) */
    private array $countryLookup = [];

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

            // ── Preload semua negara sekali saja (hindari query per-baris) ──
            $this->buildCountryLookup();

            $success       = 0;
            $failed        = 0;
            $unmatchedList = [];

            foreach ($ports as $code => $port) {
                if (empty($port['name']) || empty($port['country'])) {
                    $failed++;
                    continue;
                }

                $country = $this->resolveCountry($port['country']);

                if (!$country) {
                    $failed++;
                    $unmatchedList[$port['country']] = ($unmatchedList[$port['country']] ?? 0) + 1;
                    continue;
                }

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

            if (!empty($unmatchedList)) {
                // Log daftar nama negara yang gagal dicocokkan supaya bisa
                // ditambahkan ke COUNTRY_ALIASES kalau masih ada yang lolos.
                Log::warning('PortService: negara tidak cocok di database', $unmatchedList);
            }

            Log::info("PortService selesai: {$success} berhasil, {$failed} gagal");
            return ['success' => $success, 'failed' => $failed, 'unmatched' => $unmatchedList];

        } catch (\Exception $e) {
            Log::error('PortService error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ambil semua negara sekali, index-kan berdasarkan nama yang dinormalisasi
     * supaya pencocokan tidak sensitif kapitalisasi / spasi / tanda baca.
     */
    private function buildCountryLookup(): void
    {
        $this->countryLookup = [];

        foreach (Country::all(['id', 'name']) as $country) {
            $this->countryLookup[$this->normalize($country->name)] = $country;
        }
    }

    /**
     * Cari negara berdasarkan nama dari dataset pelabuhan, dengan urutan:
     * 1. Alias manual (exact key dari COUNTRY_ALIASES) → normalisasi → cocokkan
     * 2. Normalisasi langsung nama dataset → cocokkan
     */
    private function resolveCountry(string $datasetName): ?Country
    {
        // 1. Coba lewat alias manual dulu
        $aliasTarget = self::COUNTRY_ALIASES[$datasetName] ?? null;
        if ($aliasTarget) {
            $normalized = $this->normalize($aliasTarget);
            if (isset($this->countryLookup[$normalized])) {
                return $this->countryLookup[$normalized];
            }
        }

        // 2. Coba normalisasi nama dataset langsung
        $normalized = $this->normalize($datasetName);
        if (isset($this->countryLookup[$normalized])) {
            return $this->countryLookup[$normalized];
        }

        // 3. Coba lagi setelah membuang kata-kata umum seperti
        //    "Republic of", "Democratic Republic of", "Kingdom of", dst.
        $stripped = $this->stripCommonPrefixes($datasetName);
        if ($stripped !== $datasetName) {
            $normalized = $this->normalize($stripped);
            if (isset($this->countryLookup[$normalized])) {
                return $this->countryLookup[$normalized];
            }
        }

        return null;
    }

    /**
     * Normalisasi string: lowercase, buang aksen/diakritik, buang tanda baca,
     * rapikan spasi. Supaya "Côte d'Ivoire" vs "Cote d Ivoire" tetap cocok.
     */
    private function normalize(string $value): string
    {
        $value = trim($value);

        // Buang aksen/diakritik (é -> e, ô -> o, dst.)
        if (function_exists('iconv')) {
            $transliterated = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if ($transliterated !== false) {
                $value = $transliterated;
            }
        }

        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value); // buang tanda baca
        $value = preg_replace('/\s+/', ' ', $value);         // rapikan spasi
        $value = trim($value);

        return $value;
    }

    private function stripCommonPrefixes(string $value): string
    {
        $patterns = [
            '/^Democratic Republic of the\s+/i',
            '/^Islamic Republic of\s+/i',
            '/^Federal Republic of\s+/i',
            '/^Kingdom of\s+/i',
            '/^Republic of the\s+/i',
            '/^Republic of\s+/i',
            '/^State of\s+/i',
            '/^United Republic of\s+/i',
            '/^Plurinational State of\s+/i',
            '/^Bolivarian Republic of\s+/i',
        ];

        return trim(preg_replace($patterns, '', $value));
    }
}