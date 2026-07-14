<?php

namespace Database\Seeders;

use App\Models\PositiveWord;
use App\Models\NegativeWord;
use Illuminate\Database\Seeder;

class SentimentWordSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Mengisi kamus kata sentiment...');

        $positiveWords = [
            'growth', 'increase', 'profit', 'stable', 'improve',
            'recovery', 'expansion', 'investment', 'boost', 'gain',
            'rise', 'positive', 'strong', 'success', 'opportunity',
            'development', 'progress', 'surplus', 'export', 'trade',
            'agreement', 'partnership', 'innovation', 'efficient', 'productive',
            'demand', 'supply', 'open', 'free', 'benefit',
            'upgrade', 'reform', 'support', 'cooperation', 'deal',
            'advance', 'flourish', 'prosper', 'achieve', 'momentum',
        ];

        $negativeWords = [
            'war', 'crisis', 'inflation', 'delay', 'disaster',
            'decline', 'loss', 'deficit', 'recession', 'conflict',
            'sanctions', 'tariff', 'disruption', 'shortage', 'risk',
            'threat', 'collapse', 'debt', 'bankruptcy', 'corruption',
            'protest', 'strike', 'blockade', 'embargo', 'tension',
            'attack', 'flood', 'drought', 'earthquake', 'storm',
            'pandemic', 'outbreak', 'slowdown', 'contraction', 'unemployment',
            'poverty', 'instability', 'violence', 'coup', 'default',
        ];

        foreach ($positiveWords as $word) {
            PositiveWord::updateOrCreate(['word' => $word], ['weight' => 1]);
        }

        foreach ($negativeWords as $word) {
            NegativeWord::updateOrCreate(['word' => $word], ['weight' => 1]);
        }

        $this->command->info('Kamus kata sentiment berhasil diisi!');
    }
}