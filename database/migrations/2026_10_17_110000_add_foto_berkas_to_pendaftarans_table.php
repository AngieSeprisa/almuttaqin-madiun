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
            // Path foto berkas (disk "public"), sudah dikompresi.
            $table->string('foto_akta')->nullable()->after('berkas_surat_pernyataan');
            $table->string('foto_kk')->nullable()->after('foto_akta');
            $table->string('foto_ktp_ortu')->nullable()->after('foto_kk');
            $table->string('foto_surat_pernyataan')->nullable()->after('foto_ktp_ortu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->dropColumn([
                'foto_akta',
                'foto_kk',
                'foto_ktp_ortu',
                'foto_surat_pernyataan',
            ]);
        });
    }
};
