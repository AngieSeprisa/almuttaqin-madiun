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
        Schema::create('perizinan_sambangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->nullable()->constrained('siswas')->nullOnDelete();
            $table->string('nama');
            $table->string('kamar');
            $table->date('tanggal_mulai');
            $table->date('tanggal_kembali');
            $table->text('keterangan')->nullable();
            $table->enum('status', ['sambang', 'sudah_kembali', 'melewati_batas'])->default('sambang');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perizinan_sambangs');
    }
};
