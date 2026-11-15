<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('perpustakaan_bukus')) {
            Schema::create('perpustakaan_bukus', function (Blueprint $table) {
                $table->id();
                $table->string('kode_buku', 50)->unique();
                $table->string('barcode', 100)->nullable()->index();
                $table->string('judul_buku');
                $table->string('penulis')->nullable();
                $table->string('penerbit')->nullable();
                $table->string('kategori', 100)->nullable();
                $table->unsignedInteger('stok_total')->default(1);
                $table->unsignedInteger('stok_tersedia')->default(1);
                $table->string('rak', 100)->nullable();
                $table->text('keterangan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('perpustakaan_peminjamans')) {
            Schema::create('perpustakaan_peminjamans', function (Blueprint $table) {
                $table->id();
                $table->string('kode_pinjam', 50)->unique();
                $table->foreignId('perpustakaan_buku_id')->constrained('perpustakaan_bukus')->cascadeOnDelete();
                $table->foreignId('siswa_id')->nullable()->constrained('siswas')->nullOnDelete();
                $table->string('nama_peminjam');
                $table->string('kelas_kamar', 120)->nullable();
                $table->unsignedInteger('jumlah')->default(1);
                $table->date('tanggal_pinjam');
                $table->date('tanggal_jatuh_tempo')->nullable();
                $table->date('tanggal_kembali')->nullable();
                $table->string('status', 30)->default('dipinjam')->index();
                $table->text('catatan')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('perpustakaan_peminjamans');
        Schema::dropIfExists('perpustakaan_bukus');
    }
};

