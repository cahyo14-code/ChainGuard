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
    Schema::create('economic_indicators', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // relasi ke tabel countries
        $table->integer('year');                        // tahun data: 2023, 2024
        $table->decimal('gdp', 20, 2)->nullable();      // GDP dalam USD
        $table->decimal('inflation', 8, 2)->nullable(); // inflasi dalam persen
        $table->bigInteger('population')->nullable();   // populasi
        $table->decimal('exports', 20, 2)->nullable();  // nilai ekspor dalam USD
        $table->decimal('imports', 20, 2)->nullable();  // nilai impor dalam USD
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('economic_indicators');
    }
};
