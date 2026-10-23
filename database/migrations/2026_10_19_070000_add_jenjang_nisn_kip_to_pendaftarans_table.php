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
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->enum('jenjang', ['SMP', 'SMA', 'Reguler'])->default('Reguler')->after('id');
            $table->string('nisn', 20)->nullable()->after('nik');
            $table->string('nama_sekolah_asal', 255)->nullable()->after('pendidikan_terakhir');
            $table->boolean('penerima_kip')->default(false)->after('pj_biaya_hp');
            $table->string('nomor_kip', 50)->nullable()->after('penerima_kip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->dropColumn([
                'jenjang',
                'nisn',
                'nama_sekolah_asal',
                'penerima_kip',
                'nomor_kip',
            ]);
        });
    }
};
