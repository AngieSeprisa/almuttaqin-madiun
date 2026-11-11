<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
                if (!Schema::hasColumn('pondok_target_pengajians', 'surat_akhir_nomor')) {
                    $table->unsignedSmallInteger('surat_akhir_nomor')->nullable()->after('ayat');
                }
                if (!Schema::hasColumn('pondok_target_pengajians', 'nama_surat_akhir')) {
                    $table->string('nama_surat_akhir', 100)->nullable()->after('surat_akhir_nomor');
                }
                if (!Schema::hasColumn('pondok_target_pengajians', 'ayat_akhir')) {
                    $table->unsignedSmallInteger('ayat_akhir')->nullable()->after('nama_surat_akhir');
                }
            });
        }

        if (Schema::hasTable('pondok_jurnal_mengajis')) {
            Schema::table('pondok_jurnal_mengajis', function (Blueprint $table) {
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_surat_akhir_nomor')) {
                    $table->unsignedSmallInteger('target_surat_akhir_nomor')->nullable()->after('target_ayat');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_nama_surat_akhir')) {
                    $table->string('target_nama_surat_akhir', 100)->nullable()->after('target_surat_akhir_nomor');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_ayat_akhir')) {
                    $table->unsignedSmallInteger('target_ayat_akhir')->nullable()->after('target_nama_surat_akhir');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'capaian_surat_akhir_nomor')) {
                    $table->unsignedSmallInteger('capaian_surat_akhir_nomor')->nullable()->after('capaian_ayat');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'capaian_nama_surat_akhir')) {
                    $table->string('capaian_nama_surat_akhir', 100)->nullable()->after('capaian_surat_akhir_nomor');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'capaian_ayat_akhir')) {
                    $table->unsignedSmallInteger('capaian_ayat_akhir')->nullable()->after('capaian_nama_surat_akhir');
                }
            });
        }

        if (Schema::hasTable('pondok_materis')) {
            DB::table('pondok_materis')
                ->where('keterangan', 'like', '%Mushaf Madinah%')
                ->update(['keterangan' => 'Makna Al-Qur\'an Halaman 1–604']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pondok_target_pengajians')) {
            Schema::table('pondok_target_pengajians', function (Blueprint $table) {
                $cols = array_filter(
                    ['surat_akhir_nomor', 'nama_surat_akhir', 'ayat_akhir'],
                    fn ($col) => Schema::hasColumn('pondok_target_pengajians', $col)
                );
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }

        if (Schema::hasTable('pondok_jurnal_mengajis')) {
            Schema::table('pondok_jurnal_mengajis', function (Blueprint $table) {
                $cols = array_filter(
                    [
                        'target_surat_akhir_nomor',
                        'target_nama_surat_akhir',
                        'target_ayat_akhir',
                        'capaian_surat_akhir_nomor',
                        'capaian_nama_surat_akhir',
                        'capaian_ayat_akhir',
                    ],
                    fn ($col) => Schema::hasColumn('pondok_jurnal_mengajis', $col)
                );
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
