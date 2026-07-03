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
    Schema::create('risk_scores', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // relasi ke tabel countries
        $table->decimal('weather_risk', 8, 2)->default(0);    // skor risiko cuaca (0-100)
        $table->decimal('inflation_risk', 8, 2)->default(0);  // skor risiko inflasi (0-100)
        $table->decimal('news_risk', 8, 2)->default(0);       // skor risiko berita/geopolitik (0-100)
        $table->decimal('currency_risk', 8, 2)->default(0);   // skor risiko kurs (0-100)
        $table->decimal('total_risk', 8, 2)->default(0);      // skor risiko total (0-100)
        $table->string('risk_level', 20)->nullable();         // Low, Medium, High
        $table->text('weather_description')->nullable();      // deskripsi kendala cuaca
        $table->text('inflation_description')->nullable();    // deskripsi kendala inflasi
        $table->text('news_description')->nullable();         // deskripsi kendala geopolitik
        $table->text('currency_description')->nullable();     // deskripsi kendala kurs
        $table->timestamp('calculated_at');                   // kapan skor ini dihitung
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('risk_scores');
    }
};
