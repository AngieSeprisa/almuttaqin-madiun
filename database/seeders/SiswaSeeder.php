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
            'Moch Rokib Fastabiqul Huda',
            'Aprilian Dias Saifudin',
            'Muhammad Hafid Wicaksono',
            'Moch Roichan Khayul Huda',
            'Umar Hadi Pamungkas',
            'Iza Septian Syah',
            'Bintang Nur Ihsan',
            'Muhammad Rizki Firdaus',
            'Angga Hutri Prasetyo',
            'Ahnaf Dzaki Amrullah',
            'Ascha Yudha Febriano',
            'Muhammad Rafli Rifaldo',
            'Muhamad Ar Royan',
            'Octoberlian Rifki Mahaposya',
            'Firman Maulana',
            'Yan Parlindungan',
        ];

        foreach ($santriReguler as $nama) {
            DB::table('siswas')->insert([
                'nama_lengkap' => $nama,
                'kamar' => 'Reguler',
                'status' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
