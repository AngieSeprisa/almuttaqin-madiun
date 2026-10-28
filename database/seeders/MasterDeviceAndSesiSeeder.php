<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDeviceAndSesiSeeder extends Seeder
{
    public function run(): void
    {
        // Perangkat fisik ESP32 dibiarkan kosong secara default (tidak membuat alat dummy).
        // Alat akan otomatis terdaftar saat ESP32 menyala/tap dengan Serial Hardware (eFuse MAC)
        // atau ketika didaftarkan oleh admin melalui menu Pengaturan Sesi & Perangkat.

        $sesis = [
            [
                'device_id_target' => null,
                'kode_sesi' => 'REGULER_HARIAN',
                'nama_sesi' => 'Presensi Kamar Reguler (24 Jam)',
                'jam_mulai' => '00:00:00',
                'jam_selesai' => '23:59:59',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
                'target_kategori' => 'kamar_khusus',
                'target_filter' => 'Reguler',
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'SUBUH',
                'nama_sesi' => 'Sholat Subuh Berjamaah',
                'jam_mulai' => '04:00:00',
                'jam_selesai' => '05:30:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
                'target_kategori' => 'semua_santri',
                'target_filter' => null,
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'DZUHUR',
                'nama_sesi' => 'Sholat Dzuhur Berjamaah',
                'jam_mulai' => '11:30:00',
                'jam_selesai' => '13:00:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
                'target_kategori' => 'semua_santri',
                'target_filter' => null,
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'ASHAR',
                'nama_sesi' => 'Sholat Ashar Berjamaah',
                'jam_mulai' => '14:45:00',
                'jam_selesai' => '16:00:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
                'target_kategori' => 'semua_santri',
                'target_filter' => null,
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'MAGHRIB',
                'nama_sesi' => 'Sholat Maghrib & Pengajian',
                'jam_mulai' => '17:30:00',
                'jam_selesai' => '18:45:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
                'target_kategori' => 'semua_santri',
                'target_filter' => null,
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'ISYA',
                'nama_sesi' => 'Sholat Isya Berjamaah',
                'jam_mulai' => '18:45:00',
                'jam_selesai' => '20:30:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
                'target_kategori' => 'semua_santri',
                'target_filter' => null,
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'SEKOLAH_MASUK',
                'nama_sesi' => 'Masuk Sekolah Pagi (SMP/SMA)',
                'jam_mulai' => '06:30:00',
                'jam_selesai' => '07:30:00',
                'toleransi_menit' => 15,
                'kategori' => 'sekolah',
                'target_kategori' => 'sekolah',
                'target_filter' => 'SMP',
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'SEKOLAH_PULANG',
                'nama_sesi' => 'Kepulangan Sekolah (SMP/SMA)',
                'jam_mulai' => '13:30:00',
                'jam_selesai' => '15:30:00',
                'toleransi_menit' => 0,
                'kategori' => 'sekolah',
                'target_kategori' => 'sekolah',
                'target_filter' => 'SMP',
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'MAKAN_SIANG',
                'nama_sesi' => 'Jatah Makan Siang Asrama',
                'jam_mulai' => '11:45:00',
                'jam_selesai' => '13:30:00',
                'toleransi_menit' => 0,
                'kategori' => 'makan',
                'target_kategori' => 'semua_santri',
                'target_filter' => null,
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'PEGAWAI_MASUK',
                'nama_sesi' => 'Presensi Datang Guru & Pegawai',
                'jam_mulai' => '06:15:00',
                'jam_selesai' => '07:30:00',
                'toleransi_menit' => 15,
                'kategori' => 'pegawai',
                'target_kategori' => 'pegawai',
                'target_filter' => null,
            ],
        ];

        foreach ($sesis as $s) {
            DB::table('master_sesis')->updateOrInsert(
                ['kode_sesi' => $s['kode_sesi']],
                array_merge($s, ['created_at' => now(), 'updated_at' => now()])
            );
        }
    }
}
