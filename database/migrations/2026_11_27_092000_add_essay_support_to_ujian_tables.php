<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ujian', function (Blueprint $table) {
            if (!Schema::hasColumn('ujian', 'jumlah_soal_essay')) {
                $table->unsignedSmallInteger('jumlah_soal_essay')->default(0)->after('jumlah_soal');
            }
            if (!Schema::hasColumn('ujian', 'bobot_pg')) {
                $table->decimal('bobot_pg', 5, 2)->default(100.00)->after('bobot_benar');
            }
            if (!Schema::hasColumn('ujian', 'bobot_essay')) {
                $table->decimal('bobot_essay', 5, 2)->default(0.00)->after('bobot_pg');
            }
            if (!Schema::hasColumn('ujian', 'timer_essay_default')) {
                $table->unsignedInteger('timer_essay_default')->default(180)->after('timer_default');
            }
        });

        Schema::table('ujian_soal', function (Blueprint $table) {
            if (!Schema::hasColumn('ujian_soal', 'tipe_soal')) {
                $table->string('tipe_soal', 20)->default('pg')->after('ujian_id');
            }
            if (!Schema::hasColumn('ujian_soal', 'skor_maks')) {
                $table->decimal('skor_maks', 6, 2)->nullable()->after('kunci_jawaban');
            }
            if (!Schema::hasColumn('ujian_soal', 'pedoman_jawaban')) {
                $table->text('pedoman_jawaban')->nullable()->after('skor_maks');
            }
        });

        Schema::table('ujian_soal', function (Blueprint $table) {
            // Tambah indeks unik baru lebih dulu agar FK ujian_id di MySQL tetap memiliki indeks pendukung
            $table->unique(['ujian_id', 'tipe_soal', 'nomor_soal'], 'ujian_soal_ujian_tipe_nomor_unique');
        });

        Schema::table('ujian_soal', function (Blueprint $table) {
            $table->dropUnique(['ujian_id', 'nomor_soal']);
        });

        Schema::table('ujian_hasil', function (Blueprint $table) {
            if (!Schema::hasColumn('ujian_hasil', 'nilai_pg')) {
                $table->decimal('nilai_pg', 6, 2)->nullable()->after('jumlah_kosong');
            }
            if (!Schema::hasColumn('ujian_hasil', 'nilai_essay')) {
                $table->decimal('nilai_essay', 6, 2)->nullable()->after('nilai_pg');
            }
            if (!Schema::hasColumn('ujian_hasil', 'skor_essay_detail')) {
                $table->json('skor_essay_detail')->nullable()->after('nilai_essay');
            }
        });

        // Backfill nilai_pg dari nilai_akhir untuk hasil ujian PG sebelumnya
        DB::table('ujian_hasil')
            ->whereNull('nilai_pg')
            ->update(['nilai_pg' => DB::raw('nilai_akhir')]);
    }

    public function down(): void
    {
        Schema::table('ujian_hasil', function (Blueprint $table) {
            $cols = array_filter(['nilai_pg', 'nilai_essay', 'skor_essay_detail'], fn ($c) => Schema::hasColumn('ujian_hasil', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('ujian_soal', function (Blueprint $table) {
            $table->unique(['ujian_id', 'nomor_soal']);
        });

        Schema::table('ujian_soal', function (Blueprint $table) {
            $table->dropUnique('ujian_soal_ujian_tipe_nomor_unique');
            $cols = array_filter(['tipe_soal', 'skor_maks', 'pedoman_jawaban'], fn ($c) => Schema::hasColumn('ujian_soal', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });

        Schema::table('ujian', function (Blueprint $table) {
            $cols = array_filter(['jumlah_soal_essay', 'bobot_pg', 'bobot_essay', 'timer_essay_default'], fn ($c) => Schema::hasColumn('ujian', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
