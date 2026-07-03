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
    Schema::create('api_logs', function (Blueprint $table) {
        $table->id();
        $table->string('api_name');                     // nama API: Open-Meteo, GNews, WorldBank
        $table->string('endpoint')->nullable();         // endpoint yang dipanggil
        $table->string('method', 10)->default('GET');   // method: GET, POST
        $table->integer('status_code')->nullable();     // HTTP status: 200, 404, 500
        $table->boolean('is_success')->default(true);   // berhasil atau tidak
        $table->text('error_message')->nullable();      // pesan error kalau gagal
        $table->integer('response_time')->nullable();   // waktu respons dalam milidetik
        $table->timestamp('called_at');                 // kapan API ini dipanggil
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('api_logs');
    }
};
