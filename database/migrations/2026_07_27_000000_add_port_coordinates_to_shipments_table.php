<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            // Snapshot koordinat pelabuhan (atau fallback negara) yang PERSIS
            // dipakai saat rute pertama kali dihitung. Disimpan sebagai snapshot
            // (bukan foreign key ke ports) supaya kalau data pelabuhan berubah/dihapus
            // di kemudian hari, histori pengiriman lama tetap akurat.
            $table->decimal('origin_point_lat', 10, 6)->nullable()->after('origin_port');
            $table->decimal('origin_point_lng', 10, 6)->nullable()->after('origin_point_lat');
            $table->decimal('destination_point_lat', 10, 6)->nullable()->after('destination_port');
            $table->decimal('destination_point_lng', 10, 6)->nullable()->after('destination_point_lat');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'origin_point_lat',
                'origin_point_lng',
                'destination_point_lat',
                'destination_point_lng',
            ]);
        });
    }
};