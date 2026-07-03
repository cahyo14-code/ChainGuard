<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('countries', function (Blueprint $table) {
        $table->id();
        $table->string('code', 3)->unique();        // kode negara: ID, US, DE
        $table->string('name');                      // nama negara: Indonesia
        $table->string('capital')->nullable();       // ibu kota
        $table->string('region')->nullable();        // benua/wilayah: Asia, Europe
        $table->string('subregion')->nullable();     // sub-wilayah: Southeast Asia
        $table->string('currency_code', 10)->nullable(); // kode mata uang: IDR, USD
        $table->string('currency_name')->nullable(); // nama mata uang: Rupiah
        $table->string('flag_url')->nullable();      // URL bendera negara
        $table->bigInteger('population')->nullable(); // populasi
        $table->decimal('latitude', 10, 6)->nullable();  // koordinat untuk peta
        $table->decimal('longitude', 10, 6)->nullable();
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
