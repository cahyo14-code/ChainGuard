<?php

namespace App\Console\Commands;

use App\Services\ExchangeRateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchCurrencyRates extends Command
{
    protected $signature   = 'chainguard:fetch-currency';
    protected $description = 'Ambil kurs mata uang terbaru dari ExchangeRate API';

    public function handle(ExchangeRateService $exchangeRateService): int
    {
        $this->info('💱  Memulai fetch kurs mata uang...');
        $start = now();

        $result  = $exchangeRateService->fetchAndStoreRates();
        $elapsed = now()->diffInSeconds($start);

        if ($result) {
            $this->info("✅ Kurs berhasil diperbarui dalam {$elapsed}s");
            Log::info('chainguard:fetch-currency selesai');
            return self::SUCCESS;
        }

        $this->error('❌ Fetch kurs gagal. Cek log untuk detail.');
        return self::FAILURE;
    }
}
