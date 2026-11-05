<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('master_sesis')) {
            // 1. Pastikan sesi PEGAWAI_PULANG tersedia
            $existsPulang = DB::table('master_sesis')->where('kode_sesi', 'PEGAWAI_PULANG')->exists();
            if (!$existsPulang) {
                DB::table('master_sesis')->insert([
                    'device_id_target' => null,
                    'kode_sesi' => 'PEGAWAI_PULANG',
                    'nama_sesi' => 'Presensi Pulang Guru & Pegawai',
                    'jam_mulai' => '14:00:00',
                    'jam_selesai' => '17:30:00',
                    'toleransi_menit' => 0,
                    'kategori' => 'pegawai',
                    'target_kategori' => 'pegawai',
                    'target_filter' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 2. Jika sesi PEGAWAI_MASUK jam selesainya masih melebihi sore (misal default seeder lama),
            // sesuaikan jika masih default lama atau tambahkan jika belum ada.
            $existsMasuk = DB::table('master_sesis')->where('kode_sesi', 'PEGAWAI_MASUK')->first();
            if (!$existsMasuk) {
                DB::table('master_sesis')->insert([
                    'device_id_target' => null,
                    'kode_sesi' => 'PEGAWAI_MASUK',
                    'nama_sesi' => 'Presensi Datang Guru & Pegawai',
                    'jam_mulai' => '06:15:00',
                    'jam_selesai' => '08:00:00',
                    'toleransi_menit' => 15,
                    'kategori' => 'pegawai',
                    'target_kategori' => 'pegawai',
                    'target_filter' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('master_sesis')) {
            DB::table('master_sesis')->where('kode_sesi', 'PEGAWAI_PULANG')->delete();
        }
    }
};

