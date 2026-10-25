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
        // 1. Tabel Classrooms (Ruang Kelas Multi-Jenjang SMP & SMA)
        if (!Schema::hasTable('classrooms')) {
            Schema::create('classrooms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lembaga_id')->constrained('lembagas')->cascadeOnDelete();
                $table->string('jenjang', 10); // SMP, SMA
                $table->string('nama_kelas', 50); // 7A, 7B, 8A, 8B, 9A, 9B, 10A, 10B, 11A, 11B, 12A, 12B
                $table->integer('tingkat'); // 7, 8, 9, 10, 11, 12
                $table->integer('urutan')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['lembaga_id', 'jenjang']);
            });
        }

        // 2. Tabel Subjects (Mata Pelajaran Multi-Jenjang)
        if (!Schema::hasTable('subjects')) {
            Schema::create('subjects', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lembaga_id')->nullable()->constrained('lembagas')->nullOnDelete();
                $table->string('kode', 30);
                $table->string('nama_pelajaran', 100);
                $table->string('jenjang', 20)->default('Semua'); // SMP, SMA, Semua
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['jenjang', 'is_active']);
            });
        }

        // 3. Tabel Time Slots (Slot Jam KBM & Non-KBM: Upacara, Istirahat, Isoma)
        if (!Schema::hasTable('time_slots')) {
            Schema::create('time_slots', function (Blueprint $table) {
                $table->id();
                $table->string('jenjang', 20)->default('Semua'); // SMP, SMA, Semua
                $table->string('hari', 20); // Senin, Selasa, Rabu, Kamis, Jumat
                $table->integer('jam_ke')->default(1); // 0 (non-KBM/khusus), 1..8
                $table->time('waktu_mulai');
                $table->time('waktu_selesai');
                $table->boolean('is_break')->default(false); // true untuk Upacara, Istirahat, Sholat Dhuhur
                $table->string('tipe_kegiatan', 50)->default('KBM'); // KBM, Upacara, Istirahat, Sholat Dhuhur, Ekstrakurikuler
                $table->string('label', 100)->nullable();
                $table->integer('urutan')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['hari', 'jam_ke']);
                $table->index(['jenjang', 'hari']);
            });
        }

        // 4. Tabel Schedules (Jadwal Mengajar Guru Multi-Jenjang)
        if (!Schema::hasTable('schedules')) {
            Schema::create('schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
                $table->foreignId('time_slot_id')->constrained('time_slots')->cascadeOnDelete();
                $table->foreignId('subject_id')->constrained('mata_pelajarans')->cascadeOnDelete();
                $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
                $table->string('tahun_ajaran', 20)->default('2026/2027');
                $table->string('semester', 20)->default('Gasal');
                $table->boolean('is_locked')->default(false);
                $table->text('catatan')->nullable();
                $table->timestamps();

                $table->unique(['classroom_id', 'time_slot_id', 'tahun_ajaran', 'semester'], 'schedules_class_slot_unique');
                $table->index(['teacher_id', 'tahun_ajaran', 'semester'], 'schedules_teacher_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('time_slots');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('classrooms');
    }
};
