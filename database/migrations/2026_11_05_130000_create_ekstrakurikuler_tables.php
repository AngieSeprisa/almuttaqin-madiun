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
        // 1. Tabel master ekstrakurikuler
        if (!Schema::hasTable('ekstrakurikulers')) {
            Schema::create('ekstrakurikulers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lembaga_id')->nullable()->constrained('lembagas')->nullOnDelete();
                $table->foreignId('pembina_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nama_ekskul', 100);
                $table->string('kode', 50)->nullable();
                $table->string('jenjang', 20)->default('SMP'); // 'SMP', 'SMA', 'ALL'
                $table->string('nama_pembina', 100)->nullable();
                $table->string('hari', 20)->nullable(); // 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'
                $table->time('jam_mulai')->nullable();
                $table->time('jam_selesai')->nullable();
                $table->string('tempat', 100)->nullable();
                $table->text('deskripsi')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 2. Tabel keanggotaan siswa dalam ekstrakurikuler (pivot)
        if (!Schema::hasTable('ekstrakurikuler_siswas')) {
            Schema::create('ekstrakurikuler_siswas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ekstrakurikuler_id')->constrained('ekstrakurikulers')->cascadeOnDelete();
                $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
                $table->string('peran', 50)->default('anggota'); // 'ketua', 'wakil', 'sekretaris', 'bendahara', 'anggota'
                $table->date('tanggal_bergabung')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['ekstrakurikuler_id', 'siswa_id']);
            });
        }

        // 3. Tabel jurnal kegiatan ekstrakurikuler
        if (!Schema::hasTable('jurnal_ekskuls')) {
            Schema::create('jurnal_ekskuls', function (Blueprint $table) {
                $table->id();
                $table->foreignId('ekstrakurikuler_id')->constrained('ekstrakurikulers')->cascadeOnDelete();
                $table->foreignId('lembaga_id')->nullable()->constrained('lembagas')->nullOnDelete();
                $table->foreignId('pembina_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nama_pembina', 100);
                $table->string('nama_ekskul', 100);
                $table->date('tanggal');
                $table->string('hari', 20);
                $table->string('jam_mulai', 20)->nullable();
                $table->string('jam_selesai', 20)->nullable();
                $table->string('tempat', 100)->nullable();
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

        // 4. Tabel detail absensi siswa pada kegiatan ekstrakurikuler
        if (!Schema::hasTable('jurnal_ekskul_absensis')) {
            Schema::create('jurnal_ekskul_absensis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jurnal_ekskul_id')->constrained('jurnal_ekskuls')->cascadeOnDelete();
                $table->foreignId('siswa_id')->nullable()->constrained('siswas')->nullOnDelete();
                $table->string('nama_siswa', 150);
                $table->string('kelas', 50)->nullable();
                $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa'])->default('hadir');
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal_ekskul_absensis');
        Schema::dropIfExists('jurnal_ekskuls');
        Schema::dropIfExists('ekstrakurikuler_siswas');
        Schema::dropIfExists('ekstrakurikulers');
    }
};

