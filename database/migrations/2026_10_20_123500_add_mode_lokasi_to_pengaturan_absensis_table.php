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
        Schema::table('pengaturan_absensis', function (Blueprint $table) {
            $table->string('mode_lokasi', 30)->default('opsional')->after('radius_meter'); 
            // 'opsional': catat jika ada tapi jangan kunci guru
            // 'wajib': kunci ketat harus dalam radius
            // 'nonaktif': hanya mengandalkan Dynamic QR (bebas kendala GPS)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pengaturan_absensis', function (Blueprint $table) {
            $table->dropColumn('mode_lokasi');
        });
    }
};
