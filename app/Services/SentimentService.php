<?php

namespace App\Services;

use App\Models\NewsCache;
use App\Models\PositiveWord;
use App\Models\NegativeWord;
use Illuminate\Support\Facades\Log;

class SentimentService
{
    protected $positiveWords = [];
    protected $negativeWords = [];

    public function __construct()
    {
        // load kamus kata dari database
        $this->positiveWords = PositiveWord::pluck('weight', 'word')->toArray();
        $this->negativeWords = NegativeWord::pluck('weight', 'word')->toArray();
    }

    public function analyze(string $text): array
    {
        // bersihkan & pecah teks jadi kata-kata
        $text  = strtolower($text);
        $text  = preg_replace('/[^a-z\s]/', '', $text);
        $words = explode(' ', $text);
        $words = array_filter($words);

        $positiveScore = 0;
        $negativeScore = 0;

        foreach ($words as $word) {
            if (isset($this->positiveWords[$word])) {
                $positiveScore += $this->positiveWords[$word];
            }
            if (isset($this->negativeWords[$word])) {
                $negativeScore += $this->negativeWords[$word];
            }
        }

        // tentukan sentiment
        if ($positiveScore > $negativeScore) {
            $sentiment = 'Positive';
        } elseif ($negativeScore > $positiveScore) {
            $sentiment = 'Negative';
        } else {
            $sentiment = 'Neutral';
        }

        return [
            'sentiment'      => $sentiment,
            'positive_score' => $positiveScore,
            'negative_score' => $negativeScore,
        ];
    }

    public function analyzeAllNews()
    {
        $news = NewsCache::whereNull('sentiment')->get();

        $count = 0;
        foreach ($news as $item) {
            $text   = ($item->title ?? '') . ' ' . ($item->description ?? '');
            $result = $this->analyze($text);

            $item->update([
                'sentiment'      => $result['sentiment'],
                'positive_score' => $result['positive_score'],
                'negative_score' => $result['negative_score'],
            ]);

            $count++;
        }

        Log::info("SentimentService: {$count} berita dianalisis");
        return $count;
    }
}