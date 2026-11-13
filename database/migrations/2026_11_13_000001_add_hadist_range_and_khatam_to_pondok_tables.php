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
                if (!Schema::hasColumn('pondok_target_pengajians', 'halaman_awal')) {
                    $table->unsignedInteger('halaman_awal')->nullable()->after('target_baris');
                }
                if (!Schema::hasColumn('pondok_target_pengajians', 'halaman_akhir')) {
                    $table->unsignedInteger('halaman_akhir')->nullable()->after('halaman_awal');
                }
                if (!Schema::hasColumn('pondok_target_pengajians', 'baris_awal')) {
                    $table->unsignedInteger('baris_awal')->nullable()->after('halaman_akhir');
                }
                if (!Schema::hasColumn('pondok_target_pengajians', 'baris_akhir')) {
                    $table->unsignedInteger('baris_akhir')->nullable()->after('baris_awal');
                }
            });
        }

        if (Schema::hasTable('pondok_jurnal_mengajis')) {
            Schema::table('pondok_jurnal_mengajis', function (Blueprint $table) {
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_halaman_awal')) {
                    $table->unsignedInteger('target_halaman_awal')->nullable()->after('target_baris');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_halaman_akhir')) {
                    $table->unsignedInteger('target_halaman_akhir')->nullable()->after('target_halaman_awal');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_baris_awal')) {
                    $table->unsignedInteger('target_baris_awal')->nullable()->after('target_halaman_akhir');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'target_baris_akhir')) {
                    $table->unsignedInteger('target_baris_akhir')->nullable()->after('target_baris_awal');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'baris_awal')) {
                    $table->unsignedInteger('baris_awal')->nullable()->after('halaman_akhir');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'baris_akhir')) {
                    $table->unsignedInteger('baris_akhir')->nullable()->after('baris_awal');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'is_khatam')) {
                    $table->boolean('is_khatam')->default(false)->after('baris_akhir');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'lanjutan_materi_id')) {
                    $table->foreignId('lanjutan_materi_id')->nullable()->after('is_khatam')->constrained('pondok_materis')->nullOnDelete();
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'lanjutan_halaman_awal')) {
                    $table->unsignedInteger('lanjutan_halaman_awal')->nullable()->after('lanjutan_materi_id');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'lanjutan_baris_awal')) {
                    $table->unsignedInteger('lanjutan_baris_awal')->nullable()->after('lanjutan_halaman_awal');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'lanjutan_halaman_akhir')) {
                    $table->unsignedInteger('lanjutan_halaman_akhir')->nullable()->after('lanjutan_baris_awal');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'lanjutan_baris_akhir')) {
                    $table->unsignedInteger('lanjutan_baris_akhir')->nullable()->after('lanjutan_halaman_akhir');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('pondok_jurnal_mengajis')) {
            Schema::table('pondok_jurnal_mengajis', function (Blueprint $table) {
                if (Schema::hasColumn('pondok_jurnal_mengajis', 'lanjutan_materi_id')) {
                    $table->dropForeign(['lanjutan_materi_id']);
                }
                $cols = array_filter(
                    [
                        'target_halaman_awal',
                        'target_halaman_akhir',
                        'target_baris_awal',
                        'target_baris_akhir',
                        'baris_awal',
                        'baris_akhir',
                        'is_khatam',
                        'lanjutan_materi_id',
                        'lanjutan_halaman_awal',
                        'lanjutan_baris_awal',
                        'lanjutan_halaman_akhir',
                        'lanjutan_baris_akhir',
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

