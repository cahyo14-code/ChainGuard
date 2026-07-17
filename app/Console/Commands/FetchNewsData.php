<?php

namespace App\Console\Commands;

use App\Services\NewsService;
use App\Services\SentimentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchNewsData extends Command
{
    protected $signature   = 'chainguard:fetch-news
                                {--all      : Fetch berita untuk semua negara (lebih lambat)}
                                {--country= : Kode negara spesifik (contoh: ID)}
                                {--analyze  : Jalankan analisis sentimen setelah fetch}';

    protected $description = 'Ambil berita terbaru dari GNews API dan analisis sentimen';

    public function handle(NewsService $newsService, SentimentService $sentimentService): int
    {
        $this->info('📰  Memulai fetch berita...');
        $start = now();

        if ($countryCode = $this->option('country')) {
            $country = \App\Models\Country::where('code', strtoupper($countryCode))->first();
            if (!$country) {
                $this->error("Negara '{$countryCode}' tidak ditemukan.");
                return self::FAILURE;
            }
            $result = $newsService->fetchAndStoreNews($country);
            $this->info($result ? "✅ Berita {$country->name} berhasil diperbarui." : "❌ Gagal.");
        } else {
            // default: 20 negara utama
            $result  = $newsService->fetchForMajorCountries();
            $elapsed = now()->diffInSeconds($start);
            $this->info("✅ Selesai dalam {$elapsed}s");
            $this->table(
                ['Status', 'Jumlah'],
                [
                    ['Berhasil', $result['success']],
                    ['Gagal',    $result['failed']],
                ]
            );
        }

        // Analisis sentimen berita yang belum dianalisis
        $this->info('🧠  Menganalisis sentimen berita...');
        $analyzed = $sentimentService->analyzeAllNews();
        $this->info("✅ {$analyzed} berita dianalisis.");

        Log::info('chainguard:fetch-news selesai');
        return self::SUCCESS;
    }
}
