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
    Schema::create('watchlists', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->onDelete('cascade');       // relasi ke tabel users
        $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // relasi ke tabel countries
        $table->string('notes')->nullable();            // catatan user tentang negara ini
        $table->timestamps();
        
        $table->unique(['user_id', 'country_id']);      // 1 user tidak bisa simpan negara yang sama 2 kali
    });
}
    public function down(): void
    {
        Schema::dropIfExists('watchlists');
    }
};
