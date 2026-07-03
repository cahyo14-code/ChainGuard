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
    Schema::create('currency_rates', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // relasi ke tabel countries
        $table->string('base_currency', 10);            // mata uang dasar: USD
        $table->string('target_currency', 10);          // mata uang tujuan: IDR, EUR
        $table->decimal('rate', 20, 6);                 // nilai tukar: 16250.500000
        $table->timestamp('fetched_at');                // kapan data ini diambil dari API
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
