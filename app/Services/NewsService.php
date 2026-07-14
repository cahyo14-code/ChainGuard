<?php

namespace App\Services;

use App\Models\Country;
use App\Models\NewsCache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsService
{
    protected $baseUrl = 'https://gnews.io/api/v4';
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = env('GNEWS_API_KEY');
    }

    public function fetchAndStoreNews(Country $country)
    {
        try {
            $query = "{$country->name} economy OR trade OR logistics OR shipping";

            $response = Http::timeout(30)->get("{$this->baseUrl}/search", [
                'q'        => $query,
                'lang'     => 'en',
                'max'      => 5,
                'apikey'   => $this->apiKey,
            ]);

            if (!$response->successful()) {
                Log::error("GNews API gagal untuk {$country->name}: " . $response->status());
                return false;
            }

            $data = $response->json();

            if (empty($data['articles'])) {
                return false;
            }

            foreach ($data['articles'] as $article) {
                NewsCache::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'url'        => $article['url'] ?? null,
                    ],
                    [
                        'title'        => $article['title'] ?? null,
                        'description'  => $article['description'] ?? null,
                        'source'       => $article['source']['name'] ?? null,
                        'category'     => 'economy',
                        'published_at' => isset($article['publishedAt']) ? date('Y-m-d H:i:s', strtotime($article['publishedAt'])) : null,
                        'fetched_at'   => now(),
                    ]
                );
            }

            return true;

        } catch (\Exception $e) {
            Log::error("NewsService error untuk {$country->name}: " . $e->getMessage());
            return false;
        }
    }

    public function fetchForMajorCountries()
    {
        // ambil berita untuk negara-negara utama dulu
        $codes = ['US', 'CN', 'DE', 'JP', 'GB', 'FR', 'IN', 'BR', 'AU', 'ID',
                  'KR', 'CA', 'IT', 'MX', 'RU', 'SA', 'TR', 'NL', 'CH', 'AR'];

        $countries = Country::whereIn('code', $codes)->get();
        $success = 0;
        $failed  = 0;

        foreach ($countries as $country) {
            $result = $this->fetchAndStoreNews($country);
            $result ? $success++ : $failed++;
            usleep(500000); // jeda 500ms karena GNews free tier lebih ketat
        }

        Log::info("NewsService selesai: {$success} berhasil, {$failed} gagal");
        return ['success' => $success, 'failed' => $failed];
    }
}