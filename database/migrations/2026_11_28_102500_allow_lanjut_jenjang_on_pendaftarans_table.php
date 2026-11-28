<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Lepas unique constraint global pada kolom nik agar siswa internal (mis. lulusan SMP -> SMA)
        //    tetap bisa mendaftar kembali di jenjang / tahun ajaran berikutnya dengan NIK aslinya.
        $driver = Schema::getConnection()->getDriverName();
        $hasNikUnique = false;

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $indexes = DB::select("SHOW INDEX FROM pendaftarans WHERE Key_name = 'pendaftarans_nik_unique'");
            $hasNikUnique = !empty($indexes);
        } elseif ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('pendaftarans')");
            foreach ($indexes as $idx) {
                if (($idx->name ?? '') === 'pendaftarans_nik_unique') {
                    $hasNikUnique = true;
                    break;
                }
            }
        } else {
            $hasNikUnique = true;
        }

        if ($hasNikUnique) {
            Schema::table('pendaftarans', function (Blueprint $table) {
                $table->dropUnique('pendaftarans_nik_unique');
            });
        }

        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->index('nik', 'pendaftarans_nik_index');

            if (!Schema::hasColumn('pendaftarans', 'is_lanjut_jenjang')) {
                $table->boolean('is_lanjut_jenjang')->default(false)->after('jenjang');
            }
            if (!Schema::hasColumn('pendaftarans', 'jenjang_sebelumnya')) {
                $table->string('jenjang_sebelumnya', 50)->nullable()->after('is_lanjut_jenjang');
            }
            if (!Schema::hasColumn('pendaftarans', 'siswa_asal_id')) {
                $table->foreignId('siswa_asal_id')->nullable()->after('jenjang_sebelumnya')->constrained('siswas')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            if (Schema::hasColumn('pendaftarans', 'siswa_asal_id')) {
                $table->dropConstrainedForeignId('siswa_asal_id');
            }
            $cols = array_filter(['is_lanjut_jenjang', 'jenjang_sebelumnya'], fn ($c) => Schema::hasColumn('pendaftarans', $c));
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
            $table->dropIndex('pendaftarans_nik_index');
        });
    }
};
