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
        Schema::create('ujian', function (Blueprint $table) {
            $table->id();
            $table->string('kode_ujian')->unique();
            $table->string('nama_ujian');
            $table->foreignId('mapel_id')->nullable()->constrained('mata_pelajarans')->nullOnDelete();
            $table->foreignId('kelas_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->foreignId('guru_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('jumlah_soal')->default(40);
            $table->unsignedTinyInteger('jumlah_opsi')->default(4); // 4 = A-D, 5 = A-E
            $table->decimal('bobot_benar', 5, 2)->default(1.00);
            $table->date('tanggal_ujian');
            $table->enum('status', ['draft', 'aktif', 'selesai'])->default('draft');
            $table->timestamps();
        });

        Schema::create('ujian_kunci', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujian')->cascadeOnDelete();
            $table->unsignedSmallInteger('nomor_soal');
            $table->string('kunci_jawaban', 2); // 'A', 'B', 'C', 'D', 'E'
            $table->decimal('bobot', 5, 2)->default(1.00);
            $table->timestamps();

            $table->unique(['ujian_id', 'nomor_soal']);
        });

        Schema::create('ujian_hasil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujian')->cascadeOnDelete();
            $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
            $table->json('jawaban_siswa')->nullable();
            $table->unsignedSmallInteger('jumlah_benar')->default(0);
            $table->unsignedSmallInteger('jumlah_salah')->default(0);
            $table->unsignedSmallInteger('jumlah_kosong')->default(0);
            $table->decimal('nilai_akhir', 5, 2)->default(0.00);
            $table->string('status_scan', 30)->default('valid'); // 'valid', 'perlu_cek_manual'
            $table->text('catatan_scan')->nullable();
            $table->timestamp('waktu_scan')->nullable();
            $table->timestamps();

            $table->unique(['ujian_id', 'siswa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ujian_hasil');
        Schema::dropIfExists('ujian_kunci');
        Schema::dropIfExists('ujian');
    }
};
