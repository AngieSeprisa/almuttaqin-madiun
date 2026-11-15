<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            if (!Schema::hasColumn('siswas', 'kategori_lulus')) {
                $table->string('kategori_lulus')->nullable()->after('status');
            }
            if (!Schema::hasColumn('siswas', 'tahun_lulus')) {
                $table->string('tahun_lulus', 20)->nullable()->after('kategori_lulus');
            }
            if (!Schema::hasColumn('siswas', 'tanggal_lulus')) {
                $table->date('tanggal_lulus')->nullable()->after('tahun_lulus');
            }
            if (!Schema::hasColumn('siswas', 'tanggal_mutasi')) {
                $table->date('tanggal_mutasi')->nullable()->after('tanggal_lulus');
            }
            if (!Schema::hasColumn('siswas', 'tujuan_mutasi')) {
                $table->string('tujuan_mutasi')->nullable()->after('tanggal_mutasi');
            }
            if (!Schema::hasColumn('siswas', 'keterangan_status')) {
                $table->text('keterangan_status')->nullable()->after('tujuan_mutasi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $cols = array_filter(
                ['kategori_lulus', 'tahun_lulus', 'tanggal_lulus', 'tanggal_mutasi', 'tujuan_mutasi', 'keterangan_status'],
                fn ($c) => Schema::hasColumn('siswas', $c)
            );
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};

