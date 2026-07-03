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
    Schema::create('weather_data', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // relasi ke tabel countries
        $table->decimal('temperature', 8, 2)->nullable();   // suhu dalam celsius
        $table->decimal('rainfall', 8, 2)->nullable();      // curah hujan dalam mm
        $table->decimal('wind_speed', 8, 2)->nullable();    // kecepatan angin dalam km/h
        $table->boolean('storm_risk')->default(false);      // ada risiko badai atau tidak
        $table->string('weather_condition')->nullable();    // kondisi: Clear, Rainy, Stormy
        $table->string('risk_level', 20)->nullable();       // Low, Medium, High
        $table->timestamp('fetched_at');                    // kapan data diambil dari Open-Meteo
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('weather_data');
    }
};
