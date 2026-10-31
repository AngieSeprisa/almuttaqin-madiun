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
        if (!Schema::hasColumn('siswas', 'jenis_kelamin')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('jenis_kelamin', 20)->nullable()->after('kamar');
            });
        }

        // Sinkronisasi data jenis kelamin dari tabel pendaftarans jika ada
        try {
            if (Schema::hasTable('pendaftarans') && Schema::hasColumn('pendaftarans', 'jenis_kelamin')) {
                $pendaftarans = DB::table('pendaftarans')
                    ->whereNotNull('jenis_kelamin')
                    ->where('jenis_kelamin', '!=', '')
                    ->select('id', 'jenis_kelamin')
                    ->get();

                foreach ($pendaftarans as $p) {
                    DB::table('siswas')
                        ->where('pendaftaran_id', $p->id)
                        ->where(function ($q) {
                            $q->whereNull('jenis_kelamin')->orWhere('jenis_kelamin', '');
                        })
                        ->update(['jenis_kelamin' => $p->jenis_kelamin]);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika tabel pendaftarans belum siap
        }

        // Inferensi fallback dari nama kamar (Putra / Putri) jika jenis_kelamin masih kosong
        try {
            DB::table('siswas')
                ->where(function ($q) {
                    $q->whereNull('jenis_kelamin')->orWhere('jenis_kelamin', '');
                })
                ->whereRaw('LOWER(kamar) LIKE ?', ['%putri%'])
                ->update(['jenis_kelamin' => 'Perempuan']);

            DB::table('siswas')
                ->where(function ($q) {
                    $q->whereNull('jenis_kelamin')->orWhere('jenis_kelamin', '');
                })
                ->whereRaw('LOWER(kamar) LIKE ?', ['%putra%'])
                ->update(['jenis_kelamin' => 'Laki-laki']);
        } catch (\Throwable $e) {
            // Abaikan jika terjadi error pada database SQLite / query raw
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('siswas', 'jenis_kelamin')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->dropColumn('jenis_kelamin');
            });
        }
    }
};

