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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_guru_sekolah')) {
                $table->boolean('is_guru_sekolah')->default(false)->after('role')->index();
            }
        });

        // Tandai guru sekolah yang sudah ada sebelumnya (yang sudah memiliki mata pelajaran atau status disetujui)
        try {
            $sekolahLembagaIds = DB::table('lembagas')
                ->whereIn('kode', ['smp', 'sma'])
                ->pluck('id');

            DB::table('users')
                ->where(function ($q) use ($sekolahLembagaIds) {
                    $q->whereIn('primary_lembaga_id', $sekolahLembagaIds)
                      ->orWhere(function ($sub) {
                          $sub->whereNotNull('mata_pelajaran')
                              ->where('mata_pelajaran', '!=', '');
                      });
                })
                ->where(function ($q) {
                    $q->whereNotNull('mata_pelajaran')
                      ->where('mata_pelajaran', '!=', '')
                      ->orWhere('mapel_status', 'disetujui');
                })
                ->update(['is_guru_sekolah' => true]);
        } catch (\Throwable $e) {
            // Abaikan jika tabel atau data belum siap
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_guru_sekolah')) {
                $table->dropColumn('is_guru_sekolah');
            }
        });
    }
};
