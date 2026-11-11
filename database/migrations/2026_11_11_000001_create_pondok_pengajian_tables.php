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
        if (!Schema::hasTable('pondok_materis')) {
            Schema::create('pondok_materis', function (Blueprint $table) {
                $table->id();
                $table->string('kode_materi', 50)->nullable();
                $table->string('nama_materi', 150);
                $table->string('kategori', 30)->index()->comment('al_quran | al_hadist');
                $table->unsignedInteger('jumlah_halaman')->default(1);
                $table->unsignedInteger('jumlah_baris')->nullable()->comment('Khusus kategori al_hadist');
                $table->text('keterangan')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pondok_target_pengajians')) {
            Schema::create('pondok_target_pengajians', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal')->index();
                $table->foreignId('kelas_pengajian_id')->constrained('master_kelas_pengajians')->cascadeOnDelete();
                $table->foreignId('pondok_materi_id')->constrained('pondok_materis')->cascadeOnDelete();
                $table->unsignedInteger('target_halaman')->default(0);
                $table->unsignedInteger('target_baris')->nullable()->default(0);
                $table->unsignedInteger('halaman_awal')->nullable();
                $table->unsignedInteger('halaman_akhir')->nullable();
                $table->unsignedInteger('baris_awal')->nullable();
                $table->unsignedInteger('baris_akhir')->nullable();
                $table->text('keterangan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('pondok_jurnal_mengajis')) {
            Schema::create('pondok_jurnal_mengajis', function (Blueprint $table) {
                $table->id();
                $table->date('tanggal')->index();
                $table->foreignId('kelas_pengajian_id')->constrained('master_kelas_pengajians')->cascadeOnDelete();
                $table->foreignId('pondok_materi_id')->constrained('pondok_materis')->cascadeOnDelete();
                $table->foreignId('target_pengajian_id')->nullable()->constrained('pondok_target_pengajians')->nullOnDelete();
                $table->foreignId('pengajar_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nama_pengajar', 150)->nullable();
                $table->unsignedInteger('target_halaman')->default(0);
                $table->unsignedInteger('target_baris')->nullable()->default(0);
                $table->unsignedInteger('capaian_halaman')->default(0);
                $table->unsignedInteger('capaian_baris')->nullable()->default(0);
                $table->unsignedInteger('halaman_awal')->nullable();
                $table->unsignedInteger('halaman_akhir')->nullable();
                $table->string('status_ketercapaian', 30)->default('tercapai')->index()->comment('tercapai | kurang | terlampaui');
                $table->text('catatan')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pondok_jurnal_mengajis');
        Schema::dropIfExists('pondok_target_pengajians');
        Schema::dropIfExists('pondok_materis');
    }
};

