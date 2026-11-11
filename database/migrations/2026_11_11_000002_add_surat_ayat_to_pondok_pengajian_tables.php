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
        if (Schema::hasTable('pondok_target_pengajians')) {
            Schema::table('pondok_target_pengajians', function (Blueprint $table) {
                if (!Schema::hasColumn('pondok_target_pengajians', 'surat_nomor')) {
                    $table->unsignedSmallInteger('surat_nomor')->nullable()->after('pondok_materi_id');
                }
                if (!Schema::hasColumn('pondok_target_pengajians', 'nama_surat')) {
                    $table->string('nama_surat', 100)->nullable()->after('surat_nomor');
                }
                if (!Schema::hasColumn('pondok_target_pengajians', 'ayat')) {
                    $table->unsignedSmallInteger('ayat')->nullable()->after('nama_surat');
                }
            });
        }

        if (Schema::hasTable('pondok_jurnal_mengajis')) {
            Schema::table('pondok_jurnal_mengajis', function (Blueprint $table) {
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_surat_nomor')) {
                    $table->unsignedSmallInteger('target_surat_nomor')->nullable()->after('nama_pengajar');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_nama_surat')) {
                    $table->string('target_nama_surat', 100)->nullable()->after('target_surat_nomor');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_ayat')) {
                    $table->unsignedSmallInteger('target_ayat')->nullable()->after('target_nama_surat');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'capaian_surat_nomor')) {
                    $table->unsignedSmallInteger('capaian_surat_nomor')->nullable()->after('target_baris');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'capaian_nama_surat')) {
                    $table->string('capaian_nama_surat', 100)->nullable()->after('capaian_surat_nomor');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'capaian_ayat')) {
                    $table->unsignedSmallInteger('capaian_ayat')->nullable()->after('capaian_nama_surat');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

