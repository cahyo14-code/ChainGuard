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
    Schema::create('articles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // relasi ke users (admin yang buat)
        $table->foreignId('country_id')->nullable()->constrained('countries')->onDelete('set null'); // relasi ke negara (opsional)
        $table->string('title');                        // judul artikel
        $table->text('content');                        // isi artikel analisis
        $table->string('category')->nullable();         // kategori: economy, logistics, geopolitics
        $table->string('thumbnail')->nullable();        // gambar thumbnail artikel
        $table->enum('status', ['draft', 'published'])->default('draft'); // status artikel
        $table->timestamp('published_at')->nullable();  // kapan artikel dipublikasikan
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
