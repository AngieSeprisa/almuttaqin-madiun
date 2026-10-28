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
        // 1. Tambah rfid_uid ke users untuk guru/pegawai jika belum ada
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'rfid_uid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('rfid_uid', 32)->nullable()->unique()->after('id');
            });
        }

        // 2. Tabel master_devices
        if (!Schema::hasTable('master_devices')) {
            Schema::create('master_devices', function (Blueprint $table) {
                $table->id();
                $table->string('device_id', 50)->unique();
                $table->string('nama_lokasi', 100);
                $table->enum('kategori_area', ['pondok', 'sekolah', 'dapur', 'umum'])->default('pondok');
                $table->boolean('status_aktif')->default(true);
                $table->timestamps();
            });
        }

        // 3. Tabel master_sesis
        if (!Schema::hasTable('master_sesis')) {
            Schema::create('master_sesis', function (Blueprint $table) {
                $table->id();
                $table->string('device_id_target', 50)->nullable();
                $table->string('kode_sesi', 30)->unique();
                $table->string('nama_sesi', 100);
                $table->time('jam_mulai');
                $table->time('jam_selesai');
                $table->integer('toleransi_menit')->default(15);
                $table->enum('kategori', ['sholat', 'pengajian', 'sekolah', 'makan', 'pegawai'])->default('sholat');
                $table->timestamps();

                $table->foreign('device_id_target')
                      ->references('device_id')
                      ->on('master_devices')
                      ->nullOnDelete()
                      ->cascadeOnUpdate();
            });
        }

        // 4. Tabel presensis (Konsolidasi Log Terpadu)
        if (!Schema::hasTable('presensis')) {
            Schema::create('presensis', function (Blueprint $table) {
                $table->id();
                $table->string('device_id', 50);
                $table->foreignId('sesi_id')->constrained('master_sesis')->cascadeOnDelete();
                $table->enum('tipe_entitas', ['santri', 'guru_pegawai']);
                $table->unsignedBigInteger('entitas_id')->comment('ID dari tabel siswas atau users');
                $table->string('rfid_uid', 32);
                $table->dateTime('waktu_tap');
                $table->enum('status_kehadiran', ['tepat_waktu', 'terlambat', 'sudah_makan'])->default('tepat_waktu');
                $table->timestamps();

                $table->index(['sesi_id', 'waktu_tap']);
                $table->index(['sesi_id', 'tipe_entitas', 'entitas_id']);
                $table->index('rfid_uid');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensis');
        Schema::dropIfExists('master_sesis');
        Schema::dropIfExists('master_devices');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'rfid_uid')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('rfid_uid');
            });
        }
    }
};
