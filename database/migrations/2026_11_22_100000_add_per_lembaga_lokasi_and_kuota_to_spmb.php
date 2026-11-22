<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penerimaan_santris', function (Blueprint $table) {
            $table->json('lokasi_seleksi_per_jenjang')->nullable()->after('lokasi_seleksi');
        });

        Schema::table('penerimaan_gelombangs', function (Blueprint $table) {
            $table->json('kuota_per_jenjang')->nullable()->after('kuota');
        });

        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->string('jenjang', 50)->default('Reguler')->change();
        });
    }

    public function down(): void
    {
        Schema::table('penerimaan_santris', function (Blueprint $table) {
            $table->dropColumn('lokasi_seleksi_per_jenjang');
        });

        Schema::table('penerimaan_gelombangs', function (Blueprint $table) {
            $table->dropColumn('kuota_per_jenjang');
        });
    }
};
