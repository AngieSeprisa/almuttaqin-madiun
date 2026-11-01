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
        if (!Schema::hasTable('perizinan_haids')) {
            Schema::create('perizinan_haids', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->nullable()->constrained('siswas')->nullOnDelete();
                $table->string('nama');
                $table->string('kamar')->nullable();
                $table->date('tanggal_mulai');
                $table->date('tanggal_selesai')->nullable();
                $table->enum('status', ['sedang_haid', 'sudah_suci'])->default('sedang_haid');
                $table->text('keterangan')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['siswa_id', 'status']);
                $table->index(['tanggal_mulai', 'tanggal_selesai']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perizinan_haids');
    }
};
