<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penerimaan_santris', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug', 160)->unique();
            $table->string('tahun_ajaran', 30);
            $table->string('jenjang', 30)->default('Semua');
            $table->string('kontak_wa', 30)->nullable();
            $table->string('jadwal_seleksi')->nullable();
            $table->string('lokasi_seleksi')->nullable();
            $table->json('syarat_dokumen')->nullable();
            $table->text('catatan_syarat')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('penerimaan_gelombangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penerimaan_santri_id')
                ->constrained('penerimaan_santris')
                ->cascadeOnDelete();
            $table->string('nama_gelombang', 100);
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->unsignedInteger('kuota')->nullable();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penerimaan_gelombangs');
        Schema::dropIfExists('penerimaan_santris');
    }
};
