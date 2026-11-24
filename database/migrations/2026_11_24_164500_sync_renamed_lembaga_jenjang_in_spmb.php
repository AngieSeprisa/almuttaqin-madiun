<?php

use App\Models\PenerimaanSantri;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lembagas')) {
            Schema::table('lembagas', function (Blueprint $table) {
                if (Schema::hasColumn('lembagas', 'kode')) {
                    $table->string('kode', 60)->change();
                }
                if (Schema::hasColumn('lembagas', 'singkatan')) {
                    $table->string('singkatan', 60)->change();
                }
            });
        }

        if (Schema::hasTable('penerimaan_santris') && Schema::hasColumn('penerimaan_santris', 'jenjang')) {
            Schema::table('penerimaan_santris', function (Blueprint $table) {
                $table->string('jenjang', 255)->default('Semua')->change();
            });
        }

        if (Schema::hasTable('pendaftarans') && Schema::hasColumn('pendaftarans', 'jenjang')) {
            Schema::table('pendaftarans', function (Blueprint $table) {
                $table->string('jenjang', 80)->default('Reguler')->change();
            });
        }

        if (Schema::hasTable('siswas') && Schema::hasColumn('siswas', 'jenjang')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('jenjang', 80)->default('Reguler')->change();
            });
        }

        if (Schema::hasTable('penerimaan_santris') && Schema::hasTable('lembagas')) {
            PenerimaanSantri::syncAllStaleJenjangKeys();
        }
    }

    public function down(): void
    {
        // Tidak perlu mengembalikan nilai jenjang lama
    }
};
