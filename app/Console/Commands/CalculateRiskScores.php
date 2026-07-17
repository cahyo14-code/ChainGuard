<?php

namespace App\Console\Commands;

use App\Services\RiskScoreService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CalculateRiskScores extends Command
{
    protected $signature   = 'chainguard:calculate-risk
                                {--country= : Kode negara spesifik (contoh: ID)}';

    protected $description = 'Hitung ulang risk score untuk semua negara berdasarkan data terbaru';

    public function handle(RiskScoreService $riskScoreService): int
    {
        $this->info('⚠️   Memulai kalkulasi risk score...');
        $start = now();

        if ($countryCode = $this->option('country')) {
            $country = \App\Models\Country::where('code', strtoupper($countryCode))->first();
            if (!$country) {
                $this->error("Negara '{$countryCode}' tidak ditemukan.");
                return self::FAILURE;
            }
            $result = $riskScoreService->calculateForCountry($country);
            $this->info($result ? "✅ Risk score {$country->name} diperbarui." : "❌ Gagal.");
            return self::SUCCESS;
        }

        $result  = $riskScoreService->calculateAllCountries();
        $elapsed = now()->diffInSeconds($start);

        $this->info("✅ Selesai dalam {$elapsed}s");
        $this->table(
            ['Status', 'Jumlah'],
            [
                ['Berhasil', $result['success']],
                ['Gagal',    $result['failed']],
            ]
        );

        Log::info('chainguard:calculate-risk selesai', $result);
        return self::SUCCESS;
    }
}
