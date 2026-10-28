<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MasterDeviceAndSesiSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Master Devices
        $devices = [
            [
                'device_id' => 'ESP32_Presensi_Santri',
                'nama_lokasi' => 'Asrama Santri Kamar Reguler',
                'kategori_area' => 'pondok',
                'status_aktif' => true,
            ],
            [
                'device_id' => 'ESP32_MASJID',
                'nama_lokasi' => 'Masjid Pondok Al Muttaqin',
                'kategori_area' => 'pondok',
                'status_aktif' => true,
            ],
            [
                'device_id' => 'ESP32_SEKOLAH',
                'nama_lokasi' => 'Lobi Utama Sekolah (SMP/SMA)',
                'kategori_area' => 'sekolah',
                'status_aktif' => true,
            ],
            [
                'device_id' => 'ESP32_DAPUR',
                'nama_lokasi' => 'Dapur & Ruang Makan Asrama',
                'kategori_area' => 'dapur',
                'status_aktif' => true,
            ],
        ];

        foreach ($devices as $d) {
            DB::table('master_devices')->updateOrInsert(
                ['device_id' => $d['device_id']],
                array_merge($d, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // 2. Seed Master Sesis
        $sesis = [
            [
                'device_id_target' => 'ESP32_Presensi_Santri',
                'kode_sesi' => 'REGULER_HARIAN',
                'nama_sesi' => 'Presensi Kamar Reguler (24 Jam)',
                'jam_mulai' => '00:00:00',
                'jam_selesai' => '23:59:59',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
            ],
            [
                'device_id_target' => 'ESP32_MASJID',
                'kode_sesi' => 'SUBUH',
                'nama_sesi' => 'Sholat Subuh Berjamaah',
                'jam_mulai' => '04:00:00',
                'jam_selesai' => '05:30:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
            ],
            [
                'device_id_target' => 'ESP32_MASJID',
                'kode_sesi' => 'DZUHUR',
                'nama_sesi' => 'Sholat Dzuhur Berjamaah',
                'jam_mulai' => '11:30:00',
                'jam_selesai' => '13:00:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
            ],
            [
                'device_id_target' => 'ESP32_MASJID',
                'kode_sesi' => 'ASHAR',
                'nama_sesi' => 'Sholat Ashar Berjamaah',
                'jam_mulai' => '14:45:00',
                'jam_selesai' => '16:00:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
            ],
            [
                'device_id_target' => 'ESP32_MASJID',
                'kode_sesi' => 'MAGHRIB',
                'nama_sesi' => 'Sholat Maghrib & Pengajian',
                'jam_mulai' => '17:30:00',
                'jam_selesai' => '18:45:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
            ],
            [
                'device_id_target' => 'ESP32_MASJID',
                'kode_sesi' => 'ISYA',
                'nama_sesi' => 'Sholat Isya Berjamaah',
                'jam_mulai' => '18:45:00',
                'jam_selesai' => '20:30:00',
                'toleransi_menit' => 15,
                'kategori' => 'sholat',
            ],
            [
                'device_id_target' => 'ESP32_SEKOLAH',
                'kode_sesi' => 'SEKOLAH_MASUK',
                'nama_sesi' => 'Masuk Sekolah Pagi (SMP/SMA)',
                'jam_mulai' => '06:30:00',
                'jam_selesai' => '07:30:00',
                'toleransi_menit' => 15,
                'kategori' => 'sekolah',
            ],
            [
                'device_id_target' => 'ESP32_SEKOLAH',
                'kode_sesi' => 'SEKOLAH_PULANG',
                'nama_sesi' => 'Kepulangan Sekolah (SMP/SMA)',
                'jam_mulai' => '13:30:00',
                'jam_selesai' => '15:30:00',
                'toleransi_menit' => 0,
                'kategori' => 'sekolah',
            ],
            [
                'device_id_target' => 'ESP32_DAPUR',
                'kode_sesi' => 'MAKAN_SIANG',
                'nama_sesi' => 'Jatah Makan Siang Asrama',
                'jam_mulai' => '11:45:00',
                'jam_selesai' => '13:30:00',
                'toleransi_menit' => 0,
                'kategori' => 'makan',
            ],
            [
                'device_id_target' => null,
                'kode_sesi' => 'PEGAWAI_MASUK',
                'nama_sesi' => 'Presensi Datang Guru & Pegawai',
                'jam_mulai' => '06:15:00',
                'jam_selesai' => '07:30:00',
                'toleransi_menit' => 15,
                'kategori' => 'pegawai',
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
