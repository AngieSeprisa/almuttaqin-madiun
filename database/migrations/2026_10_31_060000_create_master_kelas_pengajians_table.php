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
        if (!Schema::hasTable('master_kelas_pengajians')) {
            Schema::create('master_kelas_pengajians', function (Blueprint $table) {
                $table->id();
                $table->string('kode_kelas', 50)->unique();
                $table->string('nama_kelas', 100);
                $table->enum('jenjang', ['smp', 'sma', 'reguler', 'umum'])->default('smp');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('siswas') && !Schema::hasColumn('siswas', 'kelas_pengajian_id')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->foreignId('kelas_pengajian_id')
                    ->nullable()
                    ->after('kamar')
                    ->constrained('master_kelas_pengajians')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('siswas') && Schema::hasColumn('siswas', 'kelas_pengajian_id')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropForeign(['kelas_pengajian_id']);
                $table->dropColumn('kelas_pengajian_id');
            });
        }

        Schema::dropIfExists('master_kelas_pengajians');
    }
};

