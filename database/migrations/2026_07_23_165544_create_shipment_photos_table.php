<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->onDelete('cascade');

            // Tipe kondisi yang difoto/didokumentasikan
            $table->enum('condition_type', [
                'weather',      // kondisi cuaca
                'port',         // kondisi pelabuhan
                'geopolitics',  // kondisi geopolitik
                'currency',     // kondisi kurs
                'inflation',    // kondisi inflasi
                'general'       // umum
            ])->default('general');

            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // Data kondisi dari API saat foto diambil
            $table->json('condition_data')->nullable();

            // Lokasi saat kondisi ini terjadi
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('location_name')->nullable();

            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_photos');
    }
};
