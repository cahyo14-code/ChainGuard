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
    Schema::create('positive_words', function (Blueprint $table) {
        $table->id();
        $table->string('word')->unique();   // kata positif: growth, profit, stable
        $table->integer('weight')->default(1); // bobot kata (opsional, untuk akurasi lebih)
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('positive_words');
    }
};
