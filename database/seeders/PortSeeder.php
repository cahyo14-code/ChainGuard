<?php

namespace Database\Seeders;

use App\Services\PortService;
use Illuminate\Database\Seeder;

class PortSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Mengambil data pelabuhan dari Sea Ports dataset...');

        $service = new PortService();
        $result  = $service->fetchAndStorePorts();

        if ($result) {
            $this->command->info("✅ {$result['success']} pelabuhan berhasil disimpan!");
            $this->command->warn("⚠️ {$result['failed']} pelabuhan gagal (negara tidak ditemukan)");
        } else {
            $this->command->error('❌ Gagal mengambil data pelabuhan!');
        }
    }
}