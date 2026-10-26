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
            $table->string('nik', 16)->nullable()->change();
            $table->string('nama_lengkap')->nullable()->change();
            $table->string('nama_panggilan', 100)->nullable()->change();
            $table->string('tempat_lahir', 100)->nullable()->change();
            $table->date('tanggal_lahir')->nullable()->change();
            $table->string('jenis_kelamin', 20)->nullable()->change();
            $table->integer('anak_ke')->nullable()->change();
            $table->integer('jumlah_saudara')->nullable()->change();
            $table->string('tinggal_bersama', 100)->nullable()->change();

            $table->string('alamat_jalan')->nullable()->change();
            $table->string('rt_rw', 50)->nullable()->change();
            $table->string('desa_kelurahan', 100)->nullable()->change();
            $table->string('kecamatan', 100)->nullable()->change();
            $table->string('kab_kota', 100)->nullable()->change();
            $table->string('kode_pos', 10)->nullable()->change();
            $table->string('provinsi', 100)->nullable()->change();

            $table->string('kelompok', 100)->nullable()->change();
            $table->string('desa', 100)->nullable()->change();
            $table->string('daerah', 100)->nullable()->change();

            $table->string('pendidikan_terakhir', 100)->nullable()->change();
            $table->string('status_mondok', 50)->nullable()->change();
            $table->string('pj_biaya_nama')->nullable()->change();
            $table->string('pj_biaya_hp', 30)->nullable()->change();

            $table->string('ayah_nama')->nullable()->change();
            $table->string('ayah_status', 50)->nullable()->change();
            $table->string('ibu_nama')->nullable()->change();
            $table->string('ibu_status', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Tetap nullable agar tidak terjadi error pada data yang sudah tersimpan kosong
    }
};
