<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ujian_sesi_jams')) {
            Schema::create('ujian_sesi_jams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lembaga_id')->nullable()->constrained('lembagas')->nullOnDelete();
                $table->string('nama_sesi', 100);
                $table->string('jam_mulai', 10);
                $table->string('jam_selesai', 10);
                $table->unsignedSmallInteger('urutan')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ujian_sesi_jams');
    }
};
