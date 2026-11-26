<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pondok_capaian_materis')) {
            Schema::create('pondok_capaian_materis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
                $table->string('scope_key', 50);
                $table->string('kategori', 20);
                $table->foreignId('pondok_materi_id')->nullable()->constrained('pondok_materis')->cascadeOnDelete();
                $table->unsignedTinyInteger('juz')->nullable();
                $table->json('halaman_selesai')->nullable();
                $table->unsignedInteger('jumlah_selesai')->default(0);
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['siswa_id', 'scope_key'], 'pondok_capaian_siswa_scope_unique');
                $table->index(['siswa_id', 'kategori'], 'pondok_capaian_siswa_kategori_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pondok_capaian_materis');
    }
};
