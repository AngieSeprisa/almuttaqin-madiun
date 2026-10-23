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
        Schema::create('pendaftarans', function (Blueprint $table) {
            $table->id();

            // Identitas Pribadi
            $table->string('no_induk')->nullable();
            $table->char('nik', 16)->unique();
            $table->string('nama_lengkap');
            $table->string('nama_panggilan');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->enum('jenis_kelamin', ['Laki-laki', 'Perempuan']);
            $table->integer('anak_ke');
            $table->integer('jumlah_saudara');
            $table->string('tinggal_bersama');
            $table->string('mulai_pengajian')->nullable();

            // Alamat Lengkap
            $table->string('alamat_jalan');
            $table->string('rt_rw');
            $table->string('desa_kelurahan');
            $table->string('kecamatan');
            $table->string('kab_kota');
            $table->string('kode_pos');
            $table->string('provinsi');
            $table->string('warga_negara')->default('Indonesia');

            // Alamat Pengajian (Struktur)
            $table->string('kelompok');
            $table->string('desa');
            $table->string('daerah');

            // Pendidikan Terakhir
            $table->string('pendidikan_terakhir');
            $table->string('jurusan')->nullable();
            $table->string('tahun_lulus')->nullable();

            // Status & PJ Biaya
            $table->enum('status_mondok', ['Person', 'Utusan/Kiriman']);
            $table->string('pj_biaya_nama');
            $table->string('pj_biaya_hp');

            // Riwayat Kesehatan & Fisik
            $table->string('penyakit_diderita')->nullable();
            $table->string('alergi')->nullable();
            $table->string('gol_darah')->nullable();
            $table->integer('tinggi_badan')->nullable();
            $table->integer('berat_badan')->nullable();
            $table->text('keahlian_prestasi')->nullable();

            // Data Ayah
            $table->string('ayah_nama');
            $table->enum('ayah_status', ['Hidup', 'Meninggal']);
            $table->string('ayah_ttl')->nullable();
            $table->string('ayah_pendidikan')->nullable();
            $table->string('ayah_pekerjaan')->nullable();
            $table->string('ayah_penghasilan')->nullable();
            $table->string('ayah_agama')->default('Islam');
            $table->text('ayah_alamat')->nullable();
            $table->string('ayah_wn')->default('Indonesia');
            $table->string('ayah_hp')->nullable();
            $table->string('ayah_daerah')->nullable();
            $table->string('ayah_desa')->nullable();
            $table->string('ayah_kelompok')->nullable();

            // Data Ibu
            $table->string('ibu_nama');
            $table->enum('ibu_status', ['Hidup', 'Meninggal']);
            $table->string('ibu_ttl')->nullable();
            $table->string('ibu_pendidikan')->nullable();
            $table->string('ibu_pekerjaan')->nullable();
            $table->string('ibu_penghasilan')->nullable();
            $table->string('ibu_agama')->default('Islam');
            $table->text('ibu_alamat')->nullable();
            $table->string('ibu_wn')->default('Indonesia');
            $table->string('ibu_hp')->nullable();
            $table->string('ibu_daerah')->nullable();
            $table->string('ibu_desa')->nullable();
            $table->string('ibu_kelompok')->nullable();

            // Persyaratan Berkas
            $table->boolean('berkas_akta')->default(false);
            $table->boolean('berkas_kk')->default(false);
            $table->boolean('berkas_ktp_ortu')->default(false);
            $table->boolean('berkas_surat_pernyataan')->default(false);

            // Status Pendaftaran
            $table->enum('status_pendaftaran', ['Pending', 'Diterima', 'Ditolak'])->default('Pending');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendaftarans');
    }
};
