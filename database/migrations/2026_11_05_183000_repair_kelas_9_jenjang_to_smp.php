<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Memperbaiki jenjang siswa siswi Kamar Kelas 9 Putra & Putri (serta Kamar Kelas 7 & 8 SMP)
     * yang sempat terisi / tertimpa menjadi 'Reguler' pada saat import data Excel.
     */
    public function up(): void
    {
        if (Schema::hasTable('siswas')) {
            // 1. Identifikasi siswa kelas 9 (dan kelas 7-8 formal SMP) yang berjenjang 'Reguler' atau kosong
            $siswaQuery = DB::table('siswas')
                ->where(function ($q) {
                    $q->where('kamar', 'LIKE', '%Kelas 9%')
                      ->orWhere('kamar', 'LIKE', '%Kelas 8%')
                      ->orWhere('kamar', 'LIKE', '%Kelas 7%')
                      ->orWhere('kelas', 'LIKE', '9%')
                      ->orWhere('kelas', 'LIKE', '8%')
                      ->orWhere('kelas', 'LIKE', '7%')
                      ->orWhere('kelas', 'LIKE', 'IX%')
                      ->orWhere('kelas', 'LIKE', 'VIII%')
                      ->orWhere('kelas', 'LIKE', 'VII%');
                })
                ->where(function ($q) {
                    $q->where('jenjang', 'Reguler')
                      ->orWhereNull('jenjang')
                      ->orWhere('jenjang', '');
                });

            // Ambil pendaftaran_id yang terkait
            $pendaftaranIds = (clone $siswaQuery)
                ->whereNotNull('pendaftaran_id')
                ->pluck('pendaftaran_id')
                ->toArray();

            // Ambil nama lengkap santri terkait
            $namaLengkaps = (clone $siswaQuery)
                ->pluck('nama_lengkap')
                ->toArray();

            // Update jenjang pada tabel siswas menjadi SMP
            $siswaQuery->update(['jenjang' => 'SMP']);

            // Sinkronkan ke tabel pendaftarans
            if (Schema::hasTable('pendaftarans')) {
                if (!empty($pendaftaranIds)) {
                    DB::table('pendaftarans')
                        ->whereIn('id', $pendaftaranIds)
                        ->update(['jenjang' => 'SMP']);
                }

                if (!empty($namaLengkaps)) {
                    DB::table('pendaftarans')
                        ->whereIn('nama_lengkap', $namaLengkaps)
                        ->where(function ($q) {
                            $q->where('jenjang', 'Reguler')
                              ->orWhereNull('jenjang')
                              ->orWhere('jenjang', '');
                        })
                        ->update(['jenjang' => 'SMP']);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for data repair
    }
};

