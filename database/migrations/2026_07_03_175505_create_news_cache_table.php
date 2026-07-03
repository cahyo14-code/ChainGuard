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
    Schema::create('news_cache', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // relasi ke tabel countries
        $table->string('title');                        // judul berita
        $table->text('description')->nullable();        // ringkasan berita
        $table->string('url')->nullable();              // link berita asli
        $table->string('source')->nullable();           // sumber: Reuters, BBC, dll
        $table->string('category')->nullable();         // kategori: economy, logistics, geopolitics
        $table->string('sentiment')->nullable();        // hasil analisis: Positive, Neutral, Negative
        $table->integer('positive_score')->default(0); // jumlah kata positif ditemukan
        $table->integer('negative_score')->default(0); // jumlah kata negatif ditemukan
        $table->timestamp('published_at')->nullable();  // tanggal berita diterbitkan
        $table->timestamp('fetched_at')->nullable();                // kapan berita diambil dari GNews
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('news_cache');
    }
};
