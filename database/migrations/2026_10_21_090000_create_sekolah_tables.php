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
        // 1. Tambah kolom kelas pada tabel siswas jika belum ada
        if (!Schema::hasColumn('siswas', 'kelas')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('kelas', 50)->nullable()->after('jenjang');
            });
        }

        // 2. Tambah kolom mata_pelajaran pada tabel users jika belum ada
        if (!Schema::hasColumn('users', 'mata_pelajaran')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('mata_pelajaran', 100)->nullable()->after('jabatan');
            });
        }

        // 3. Tabel jurnals (jurnal pembelajaran)
        if (!Schema::hasTable('jurnals')) {
            Schema::create('jurnals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lembaga_id')->nullable()->constrained('lembagas')->nullOnDelete();
                $table->foreignId('guru_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nama_guru');
                $table->string('kelas', 50);
                $table->string('semester', 20);
                $table->string('tahun_pelajaran', 20);
                $table->string('hari', 20);
                $table->date('tanggal');
                $table->string('mata_pelajaran', 100);
                $table->string('jam_ke', 50);
                $table->text('materi');
                $table->text('catatan')->nullable();
                $table->integer('total_siswa')->default(0);
                $table->integer('total_hadir')->default(0);
                $table->integer('total_sakit')->default(0);
                $table->integer('total_izin')->default(0);
                $table->integer('total_alpa')->default(0);
                $table->timestamps();
            });
        }

        // 4. Tabel detail absensi siswa pada setiap jurnal
        if (!Schema::hasTable('jurnal_absensis')) {
            Schema::create('jurnal_absensis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jurnal_id')->constrained('jurnals')->cascadeOnDelete();
                $table->foreignId('siswa_id')->nullable()->constrained('siswas')->nullOnDelete();
                $table->string('nama_siswa');
                $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa'])->default('hadir');
                $table->string('keterangan')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_absensis');
        Schema::dropIfExists('jurnals');

        if (Schema::hasColumn('siswas', 'kelas')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropColumn('kelas');
            });
        }

        if (Schema::hasColumn('users', 'mata_pelajaran')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('mata_pelajaran');
            });
        }
    }
};
