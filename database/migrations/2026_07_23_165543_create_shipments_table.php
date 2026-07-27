<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('origin_country_id')->constrained('countries')->onDelete('cascade');
            $table->foreignId('destination_country_id')->constrained('countries')->onDelete('cascade');

            // Info rute
            $table->string('origin_port')->nullable();
            $table->string('destination_port')->nullable();
            $table->integer('nautical_miles')->default(0);

            // ETA
            $table->integer('normal_days')->default(0);
            $table->date('normal_eta')->nullable();
            $table->integer('risk_adjusted_days')->default(0);
            $table->date('risk_adjusted_eta')->nullable();
            $table->integer('total_delay_days')->default(0);

            // Faktor kendala (JSON)
            $table->json('factors')->nullable();

            // Rekomendasi
            $table->text('recommendation')->nullable();
            $table->string('recommendation_level')->default('Low');

            // Status pengiriman
            $table->enum('status', ['active', 'completed'])->default('active');
            $table->timestamp('completed_at')->nullable();

            // Catatan
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
