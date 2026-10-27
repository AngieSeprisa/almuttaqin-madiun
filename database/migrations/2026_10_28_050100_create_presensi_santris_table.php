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
        if (!Schema::hasTable('presensi_santris')) {
            Schema::create('presensi_santris', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('siswas')->onDelete('cascade');
                $table->string('rfid_uid', 32)->index();
                $table->string('device_id', 50)->default('ESP32_Presensi_Santri');
                $table->date('tanggal')->index();
                $table->time('waktu');
                $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Terlambat'])->default('Hadir');
                $table->string('keterangan', 150)->nullable();
                $table->timestamps();

                $table->index(['tanggal', 'siswa_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presensi_santris');
    }
};
