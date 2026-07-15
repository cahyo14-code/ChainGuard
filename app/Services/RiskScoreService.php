<?php

namespace App\Services;

use App\Models\Country;
use App\Models\RiskScore;
use App\Models\RiskHistory;
use App\Models\WeatherData;
use App\Models\EconomicIndicator;
use App\Models\NewsCache;
use App\Models\CurrencyRate;
use Illuminate\Support\Facades\Log;

class RiskScoreService
{
    // bobot sesuai spesifikasi
    const WEIGHT_WEATHER   = 0.30;
    const WEIGHT_INFLATION = 0.20;
    const WEIGHT_NEWS      = 0.40;
    const WEIGHT_CURRENCY  = 0.10;

    public function calculateForCountry(Country $country): bool
    {
        try {
            // 1. hitung weather risk
            $weatherResult   = $this->calculateWeatherRisk($country);
            // 2. hitung inflation risk
            $inflationResult = $this->calculateInflationRisk($country);
            // 3. hitung news risk
            $newsResult      = $this->calculateNewsRisk($country);
            // 4. hitung currency risk
            $currencyResult  = $this->calculateCurrencyRisk($country);

            // hitung total risk
            $totalRisk =
                ($weatherResult['score']   * self::WEIGHT_WEATHER) +
                ($inflationResult['score'] * self::WEIGHT_INFLATION) +
                ($newsResult['score']      * self::WEIGHT_NEWS) +
                ($currencyResult['score']  * self::WEIGHT_CURRENCY);

            // tentukan risk level
            $riskLevel = $this->getRiskLevel($totalRisk);

            // simpan ke risk_scores
            RiskScore::updateOrCreate(
                ['country_id' => $country->id],
                [
                    'weather_risk'          => $weatherResult['score'],
                    'inflation_risk'        => $inflationResult['score'],
                    'news_risk'             => $newsResult['score'],
                    'currency_risk'         => $currencyResult['score'],
                    'total_risk'            => round($totalRisk, 2),
                    'risk_level'            => $riskLevel,
                    'weather_description'   => $weatherResult['description'],
                    'inflation_description' => $inflationResult['description'],
                    'news_description'      => $newsResult['description'],
                    'currency_description'  => $currencyResult['description'],
                    'calculated_at'         => now(),
                ]
            );

            // simpan ke risk_histories (snapshot harian)
            RiskHistory::updateOrCreate(
                [
                    'country_id'    => $country->id,
                    'recorded_date' => now()->toDateString(),
                ],
                [
                    'weather_risk'   => $weatherResult['score'],
                    'inflation_risk' => $inflationResult['score'],
                    'news_risk'      => $newsResult['score'],
                    'currency_risk'  => $currencyResult['score'],
                    'total_risk'     => round($totalRisk, 2),
                    'risk_level'     => $riskLevel,
                ]
            );

            return true;

        } catch (\Exception $e) {
            Log::error("RiskScoreService error untuk {$country->name}: " . $e->getMessage());
            return false;
        }
    }

    private function calculateWeatherRisk(Country $country): array
    {
        $weather = WeatherData::where('country_id', $country->id)->latest()->first();

        if (!$weather) {
            return ['score' => 0, 'description' => 'Data cuaca tidak tersedia'];
        }

        $score = 0;
        $reasons = [];

        if ($weather->storm_risk) {
            $score += 80;
            $reasons[] = 'terdapat risiko badai';
        }

        if ($weather->rainfall > 50) {
            $score += 60;
            $reasons[] = "curah hujan tinggi ({$weather->rainfall}mm)";
        } elseif ($weather->rainfall > 20) {
            $score += 30;
            $reasons[] = "curah hujan sedang ({$weather->rainfall}mm)";
        }

        if ($weather->wind_speed > 60) {
            $score += 40;
            $reasons[] = "kecepatan angin tinggi ({$weather->wind_speed} km/h)";
        } elseif ($weather->wind_speed > 30) {
            $score += 20;
            $reasons[] = "kecepatan angin sedang ({$weather->wind_speed} km/h)";
        }

        $score = min($score, 100);

        $description = empty($reasons)
            ? "Kondisi cuaca normal, suhu {$weather->temperature}°C"
            : "Kondisi cuaca berisiko: " . implode(', ', $reasons);

        return ['score' => $score, 'description' => $description];
    }

    private function calculateInflationRisk(Country $country): array
    {
        $economic = EconomicIndicator::where('country_id', $country->id)
            ->orderBy('year', 'desc')
            ->first();

        if (!$economic || is_null($economic->inflation)) {
            return ['score' => 0, 'description' => 'Data inflasi tidak tersedia'];
        }

        $inflation = (float) $economic->inflation;
        $score     = 0;
        $description = '';

        if ($inflation > 20) {
            $score = 100;
            $description = "Inflasi sangat tinggi ({$inflation}%), berpotensi mengganggu produksi dan rantai pasok secara serius";
        } elseif ($inflation > 10) {
            $score = 75;
            $description = "Inflasi tinggi ({$inflation}%), biaya produksi meningkat signifikan";
        } elseif ($inflation > 5) {
            $score = 50;
            $description = "Inflasi moderat ({$inflation}%), perlu dipantau dampaknya pada biaya";
        } elseif ($inflation > 2) {
            $score = 25;
            $description = "Inflasi rendah ({$inflation}%), kondisi ekonomi relatif stabil";
        } else {
            $score = 10;
            $description = "Inflasi sangat rendah ({$inflation}%), kondisi ekonomi stabil";
        }

        return ['score' => $score, 'description' => $description];
    }

    private function calculateNewsRisk(Country $country): array
    {
        $news = NewsCache::where('country_id', $country->id)->get();

        if ($news->isEmpty()) {
            return ['score' => 0, 'description' => 'Tidak ada berita terkait negara ini'];
        }

        $positive = $news->where('sentiment', 'Positive')->count();
        $negative = $news->where('sentiment', 'Negative')->count();
        $neutral  = $news->where('sentiment', 'Neutral')->count();
        $total    = $news->count();

        $negativeRatio = $total > 0 ? ($negative / $total) : 0;
        $score = round($negativeRatio * 100);

        if ($negativeRatio > 0.6) {
            $description = "Sentimen berita mayoritas negatif ({$negative} dari {$total} berita), indikasi ketidakstabilan geopolitik/ekonomi";
        } elseif ($negativeRatio > 0.3) {
            $description = "Sentimen berita campuran ({$negative} negatif, {$positive} positif dari {$total} berita), perlu dipantau";
        } else {
            $description = "Sentimen berita mayoritas positif ({$positive} dari {$total} berita), kondisi geopolitik relatif stabil";
        }

        return ['score' => $score, 'description' => $description];
    }

    private function calculateCurrencyRisk(Country $country): array
    {
        $currency = CurrencyRate::where('country_id', $country->id)->first();

        if (!$currency) {
            return ['score' => 0, 'description' => 'Data kurs tidak tersedia'];
        }

        // ambil riwayat kurs 7 hari terakhir
        $histories = \App\Models\CurrencyHistory::where('country_id', $country->id)
            ->orderBy('rate_date', 'desc')
            ->take(7)
            ->get();

        if ($histories->count() < 2) {
            return [
                'score'       => 10,
                'description' => "Kurs {$currency->target_currency}: {$currency->rate} per USD, data historis belum cukup",
            ];
        }

        $latest = $histories->first()->rate;
        $oldest = $histories->last()->rate;

        // hitung persentase perubahan
        $change = $oldest > 0 ? (($latest - $oldest) / $oldest) * 100 : 0;
        $changeAbs = abs($change);

        $score = 0;
        if ($changeAbs > 10) {
            $score = 100;
        } elseif ($changeAbs > 5) {
            $score = 70;
        } elseif ($changeAbs > 2) {
            $score = 40;
        } else {
            $score = 10;
        }

        $direction   = $change > 0 ? 'melemah' : 'menguat';
        $description = "Kurs {$currency->target_currency} {$direction} {$changeAbs}% dalam 7 hari terakhir (1 USD = {$currency->rate} {$currency->target_currency})";

        return ['score' => $score, 'description' => $description];
    }

    private function getRiskLevel(float $score): string
    {
        if ($score >= 67) return 'High';
        if ($score >= 34) return 'Medium';
        return 'Low';
    }

    public function calculateAllCountries(): array
    {
        $countries = Country::whereNotNull('code')->get();
        $success = 0;
        $failed  = 0;

        foreach ($countries as $country) {
            $result = $this->calculateForCountry($country);
            $result ? $success++ : $failed++;
        }

        Log::info("RiskScoreService selesai: {$success} berhasil, {$failed} gagal");
        return ['success' => $success, 'failed' => $failed];
    }
}