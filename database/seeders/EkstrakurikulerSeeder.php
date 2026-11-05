<?php

namespace Database\Seeders;

use App\Models\Ekstrakurikuler;
use App\Models\Lembaga;
use App\Models\User;
use Illuminate\Database\Seeder;

class EkstrakurikulerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $smp = Lembaga::where('kode', 'smp')->first();
        $sma = Lembaga::where('kode', 'sma')->first();

        $defaultEkskuls = [
            [
                'nama_ekskul'  => 'Pramuka',
                'kode'         => 'PRAMUKA',
                'jenjang'      => 'SMP',
                'hari'         => 'Jumat',
                'jam_mulai'    => '14:30:00',
                'jam_selesai'  => '16:30:00',
                'tempat'       => 'Lapangan Utama Al-Muttaqin',
                'deskripsi'    => 'Kegiatan kepramukaan melatih kedisiplinan, kemandirian, dan kepemimpinan santri.',
                'lembaga_id'   => $smp?->id,
            ],
            [
                'nama_ekskul'  => 'Futsal',
                'kode'         => 'FUTSAL',
                'jenjang'      => 'SMP',
                'hari'         => 'Sabtu',
                'jam_mulai'    => '15:30:00',
                'jam_selesai'  => '17:00:00',
                'tempat'       => 'Lapangan Futsal Al-Muttaqin',
                'deskripsi'    => 'Olahraga futsal untuk kebugaran jasmani, sportivitas, dan kekompakan tim.',
                'lembaga_id'   => $smp?->id,
            ],
            [
                'nama_ekskul'  => 'PMR (Palang Merah Remaja)',
                'kode'         => 'PMR',
                'jenjang'      => 'SMP',
                'hari'         => 'Sabtu',
                'jam_mulai'    => '08:00:00',
                'jam_selesai'  => '09:30:00',
                'tempat'       => 'Ruang UKS / Aula SMP',
                'deskripsi'    => 'Pelatihan pertolongan pertama pada kecelakaan dan kepedulian sosial.',
                'lembaga_id'   => $smp?->id,
            ],
            [
                'nama_ekskul'  => 'Seni Hadroh & Banjari',
                'kode'         => 'HADROH',
                'jenjang'      => 'SMP',
                'hari'         => 'Kamis',
                'jam_mulai'    => '19:30:00',
                'jam_selesai'  => '21:00:00',
                'tempat'       => 'Masjid Al-Muttaqin',
                'deskripsi'    => 'Seni musik Islami sholawat rebana dan banjari.',
                'lembaga_id'   => $smp?->id,
            ],
            [
                'nama_ekskul'  => 'Paskibra',
                'kode'         => 'PASKIBRA',
                'jenjang'      => 'SMA',
                'hari'         => 'Sabtu',
                'jam_mulai'    => '15:00:00',
                'jam_selesai'  => '17:00:00',
                'tempat'       => 'Lapangan Upacara',
                'deskripsi'    => 'Pasukan Pengibar Bendera melatih PBB, ketegasan, dan nasionalisme.',
                'lembaga_id'   => $sma?->id,
            ],
            [
                'nama_ekskul'  => 'Klub Robotik & IT',
                'kode'         => 'ROBOTIK',
                'jenjang'      => 'SMA',
                'hari'         => 'Jumat',
                'jam_mulai'    => '15:30:00',
                'jam_selesai'  => '17:00:00',
                'tempat'       => 'Laboratorium Komputer',
                'deskripsi'    => 'Pengembangan teknologi komputer, koding, dan rangkaian elektronika.',
                'lembaga_id'   => $sma?->id,
            ],
        ];

        $guruList = User::where('role', 'guru')->get();

        foreach ($defaultEkskuls as $idx => $item) {
            $pembina = $guruList->get($idx % max(1, $guruList->count()));
            Ekstrakurikuler::updateOrInsert(
                [
                    'nama_ekskul' => $item['nama_ekskul'],
                    'jenjang'     => $item['jenjang'],
                ],
                array_merge($item, [
                    'pembina_id'   => $pembina?->id,
                    'nama_pembina' => $pembina?->name ?? 'Pembina Ekskul',
                    'is_active'    => true,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ])
            );
        }
    }
}
