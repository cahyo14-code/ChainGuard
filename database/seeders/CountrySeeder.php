<?php

namespace Database\Seeders;

use App\Services\CountryService;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Mengambil data negara dari REST Countries API...');
        
        $service = new CountryService();
        $result = $service->fetchAndStoreAllCountries();

        if ($result) {
            $this->command->info('✅ Data negara berhasil disimpan!');
        } else {
            $this->command->error('❌ Gagal mengambil data negara!');
        }
    }
}