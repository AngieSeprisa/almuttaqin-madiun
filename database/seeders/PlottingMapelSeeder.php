<?php

namespace Database\Seeders;

use App\Models\Lembaga;
use App\Models\MataPelajaran;
use Illuminate\Database\Seeder;

class PlottingMapelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Pengaturan Sekolah: Plotting Mata Pelajaran HANYA untuk lembaga sekolah (SMP & SMA).
     * Pondok dan Yayasan tidak masuk dalam pengaturan sekolah.
     */
    public function run(): void
    {
        $smp = Lembaga::where('kode', 'smp')->first();
        $sma = Lembaga::where('kode', 'sma')->first();

        // 1. Mata Pelajaran SMP
        if ($smp) {
            $mapelSMP = [
                ['mata_pelajaran' => 'Matematika',                               'kode' => 'SMP-MAT'],
                ['mata_pelajaran' => 'Ilmu Pengetahuan Alam (IPA)',              'kode' => 'SMP-IPA'],
                ['mata_pelajaran' => 'Bahasa Indonesia',                         'kode' => 'SMP-IND'],
                ['mata_pelajaran' => 'Bahasa Inggris',                           'kode' => 'SMP-ING'],
                ['mata_pelajaran' => 'Pendidikan Agama Islam (PAI)',             'kode' => 'SMP-PAI'],
                ['mata_pelajaran' => 'Informatika',                              'kode' => 'SMP-INF'],
                ['mata_pelajaran' => 'Ilmu Pengetahuan Sosial (IPS)',            'kode' => 'SMP-IPS'],
                ['mata_pelajaran' => 'Pendidikan Pancasila & Kewarganegaraan (PPKn)', 'kode' => 'SMP-PKN'],
                ['mata_pelajaran' => 'Pendidikan Jasmani, Olahraga & Kesehatan (PJOK)', 'kode' => 'SMP-PJK'],
                ['mata_pelajaran' => 'Seni Budaya',                              'kode' => 'SMP-SNB'],
                ['mata_pelajaran' => 'Bahasa Arab',                              'kode' => 'SMP-ARB'],
            ];

            foreach ($mapelSMP as $item) {
                MataPelajaran::updateOrCreate(
                    [
                        'lembaga_id'     => $smp->id,
                        'mata_pelajaran' => $item['mata_pelajaran'],
                    ],
                    [
                        'lembaga_id'     => $smp->id,
                        'mata_pelajaran' => $item['mata_pelajaran'],
                        'kode'           => $item['kode'],
                    ]
                );
            }
        }

        // 2. Mata Pelajaran SMA
        if ($sma) {
            $mapelSMA = [
                ['mata_pelajaran' => 'Fisika',                 'kode' => 'SMA-FIS'],
                ['mata_pelajaran' => 'Kimia',                  'kode' => 'SMA-KIM'],
                ['mata_pelajaran' => 'Biologi',                'kode' => 'SMA-BIO'],
                ['mata_pelajaran' => 'Matematika Wajib',       'kode' => 'SMA-MAT'],
                ['mata_pelajaran' => 'Ekonomi & Akuntansi',    'kode' => 'SMA-EKO'],
                ['mata_pelajaran' => 'Sosiologi',              'kode' => 'SMA-SOS'],
                ['mata_pelajaran' => 'Bahasa Inggris Lanjut',  'kode' => 'SMA-ING'],
                ['mata_pelajaran' => 'Sejarah Indonesia',      'kode' => 'SMA-SEJ'],
            ];

            foreach ($mapelSMA as $item) {
                MataPelajaran::updateOrCreate(
                    [
                        'lembaga_id'     => $sma->id,
                        'mata_pelajaran' => $item['mata_pelajaran'],
                    ],
                    [
                        'lembaga_id'     => $sma->id,
                        'mata_pelajaran' => $item['mata_pelajaran'],
                        'kode'           => $item['kode'],
                    ]
                );
            }
        }
    }
}
