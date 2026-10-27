<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiswaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $santriReguler = [
            ['nama' => 'Moch Rokib Fastabiqul Huda', 'rfid_uid' => 'C1F8EFA7'],
            ['nama' => 'Aprilian Dias Saifudin', 'rfid_uid' => '6116F1A7'],
            ['nama' => 'Muhammad Hafid Wicaksono', 'rfid_uid' => null],
            ['nama' => 'Moch Roichan Khayul Huda', 'rfid_uid' => null],
            ['nama' => 'Umar Hadi Pamungkas', 'rfid_uid' => null],
            ['nama' => 'Iza Septian Syah', 'rfid_uid' => null],
            ['nama' => 'Bintang Nur Ihsan', 'rfid_uid' => null],
            ['nama' => 'Muhammad Rizki Firdaus', 'rfid_uid' => null],
            ['nama' => 'Angga Hutri Prasetyo', 'rfid_uid' => null],
            ['nama' => 'Ahnaf Dzaki Amrullah', 'rfid_uid' => null],
            ['nama' => 'Ascha Yudha Febriano', 'rfid_uid' => null],
            ['nama' => 'Muhammad Rafli Rifaldo', 'rfid_uid' => null],
            ['nama' => 'Muhamad Ar Royan', 'rfid_uid' => null],
            ['nama' => 'Octoberlian Rifki Mahaposya', 'rfid_uid' => null],
            ['nama' => 'Firman Maulana', 'rfid_uid' => null],
            ['nama' => 'Yan Parlindungan', 'rfid_uid' => null],
        ];

        foreach ($santriReguler as $item) {
            DB::table('siswas')->updateOrInsert(
                ['nama_lengkap' => $item['nama']],
                [
                    'rfid_uid' => $item['rfid_uid'],
                    'kamar' => 'Reguler',
                    'status' => 'aktif',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
