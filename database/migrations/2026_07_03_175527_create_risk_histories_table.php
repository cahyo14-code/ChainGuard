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
    Schema::create('risk_histories', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // relasi ke tabel countries
        $table->decimal('weather_risk', 8, 2)->default(0);    // skor risiko cuaca saat itu
        $table->decimal('inflation_risk', 8, 2)->default(0);  // skor risiko inflasi saat itu
        $table->decimal('news_risk', 8, 2)->default(0);       // skor risiko berita saat itu
        $table->decimal('currency_risk', 8, 2)->default(0);   // skor risiko kurs saat itu
        $table->decimal('total_risk', 8, 2)->default(0);      // skor total saat itu
        $table->string('risk_level', 20)->nullable();         // Low, Medium, High
        $table->date('recorded_date');                        // tanggal skor ini dicatat
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('risk_histories');
    }
};
