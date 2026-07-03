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
    Schema::create('ports', function (Blueprint $table) {
        $table->id();
        $table->foreignId('country_id')->constrained('countries')->onDelete('cascade'); // relasi ke tabel countries
        $table->string('name');                         // nama pelabuhan: Tanjung Priok
        $table->string('code', 20)->nullable();         // kode pelabuhan: IDTPP
        $table->string('city')->nullable();             // kota: Jakarta
        $table->decimal('latitude', 10, 6)->nullable(); // koordinat untuk marker di peta
        $table->decimal('longitude', 10, 6)->nullable();
        $table->string('type')->nullable();             // tipe: Seaport, Airport, Inland
        $table->boolean('is_active')->default(true);    // pelabuhan aktif atau tidak
        $table->timestamps();
    });
}
    public function down(): void
    {
        Schema::dropIfExists('ports');
    }
};
