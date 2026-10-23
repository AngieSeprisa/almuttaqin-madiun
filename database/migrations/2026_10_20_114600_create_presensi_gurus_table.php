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
        Schema::create('presensi_gurus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('lembaga_id')->constrained('lembagas')->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('status', 30)->default('hadir'); // hadir, terlambat, izin, sakit, alpa
            $table->decimal('lat_masuk', 10, 8)->nullable();
            $table->decimal('long_masuk', 11, 8)->nullable();
            $table->unsignedInteger('jarak_masuk_meter')->nullable();
            $table->decimal('lat_pulang', 10, 8)->nullable();
            $table->decimal('long_pulang', 11, 8)->nullable();
            $table->unsignedInteger('jarak_pulang_meter')->nullable();
            $table->text('keterangan')->nullable();
            $table->string('dokumen_pendukung')->nullable();
            $table->string('metode_masuk', 30)->default('qr_scan'); // qr_scan, manual
            $table->string('metode_pulang', 30)->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tanggal', 'lembaga_id']);
            $table->index(['tanggal', 'status']);
            $table->index(['lembaga_id', 'tanggal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_gurus');
    }
};
