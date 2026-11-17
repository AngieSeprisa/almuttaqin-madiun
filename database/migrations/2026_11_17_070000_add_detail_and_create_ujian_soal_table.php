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
        Schema::table('ujian', function (Blueprint $table) {
            $table->string('file_soal')->nullable()->after('status');
            $table->unsignedInteger('timer_default')->default(90)->after('file_soal'); // detik
            $table->text('peraturan_ujian')->nullable()->after('timer_default');
            $table->string('jam_mulai', 10)->nullable()->after('peraturan_ujian');
            $table->string('jam_selesai', 10)->nullable()->after('jam_mulai');
        });

        Schema::create('ujian_soal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ujian_id')->constrained('ujian')->cascadeOnDelete();
            $table->unsignedSmallInteger('nomor_soal');
            $table->longText('pertanyaan');
            $table->text('opsi_a')->nullable();
            $table->text('opsi_b')->nullable();
            $table->text('opsi_c')->nullable();
            $table->text('opsi_d')->nullable();
            $table->text('opsi_e')->nullable();
            $table->string('kunci_jawaban', 5)->nullable();
            $table->unsignedInteger('timer_detik')->default(90);
            $table->string('gambar')->nullable();
            $table->timestamps();

            $table->unique(['ujian_id', 'nomor_soal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ujian_soal');

        Schema::table('ujian', function (Blueprint $table) {
            $table->dropColumn([
                'file_soal',
                'timer_default',
                'peraturan_ujian',
                'jam_mulai',
                'jam_selesai',
            ]);
        });
    }
};

