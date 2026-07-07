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

    public function run(): void
    {
    $this->command->info('Mengambil data negara dari API...');
    
    $service = new CountryService();
    $result = $service->fetchAndStoreAllCountries();

    if ($result) {
        $this->command->info('✅ Data negara berhasil disimpan!');
    } else {
        $this->command->error('❌ Gagal mengambil data negara!');
    }

    // update koordinat
    $this->command->info('Mengupdate koordinat negara...');
    $service->updateCoordinates();
    $this->command->info('✅ Koordinat berhasil diupdate!');

    // update region
    $this->command->info('Mengupdate region negara...');
    $service->updateRegions();
    $this->command->info('✅ Region berhasil diupdate!');
    }

}