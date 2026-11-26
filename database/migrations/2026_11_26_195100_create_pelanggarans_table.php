<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pelanggarans')) {
            Schema::create('pelanggarans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
                $table->string('nama_siswa', 150);
                $table->string('kelas', 100)->nullable();
                $table->text('pelanggaran');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('siswa_id');
                $table->index('kelas');
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggarans');
    }
};
