<?php

namespace Database\Seeders;

use App\Models\Classroom;
use App\Models\Lembaga;
use App\Models\MataPelajaran;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class JadwalPelajaranSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $smp = Lembaga::where('kode', 'smp')->first();
        $sma = Lembaga::where('kode', 'sma')->first();

        if (!$smp || !$sma) {
            return;
        }

        // =========================================================================
        // 1. DATA MASTER CLASSROOMS (SMP & SMA)
        // =========================================================================
        $smpClasses = [
            ['nama_kelas' => '7A', 'tingkat' => 7, 'urutan' => 1],
            ['nama_kelas' => '7B', 'tingkat' => 7, 'urutan' => 2],
            ['nama_kelas' => '8A', 'tingkat' => 8, 'urutan' => 3],
            ['nama_kelas' => '8B', 'tingkat' => 8, 'urutan' => 4],
            ['nama_kelas' => '9A', 'tingkat' => 9, 'urutan' => 5],
            ['nama_kelas' => '9B', 'tingkat' => 9, 'urutan' => 6],
        ];

        foreach ($smpClasses as $item) {
            Classroom::updateOrCreate(
                ['lembaga_id' => $smp->id, 'nama_kelas' => $item['nama_kelas']],
                [
                    'lembaga_id' => $smp->id,
                    'jenjang'    => 'SMP',
                    'nama_kelas' => $item['nama_kelas'],
                    'tingkat'    => $item['tingkat'],
                    'urutan'     => $item['urutan'],
                    'is_active'  => true,
                ]
            );
        }

        $smaClasses = [
            ['nama_kelas' => '10A', 'tingkat' => 10, 'urutan' => 1],
            ['nama_kelas' => '10B', 'tingkat' => 10, 'urutan' => 2],
            ['nama_kelas' => '11A', 'tingkat' => 11, 'urutan' => 3],
            ['nama_kelas' => '11B', 'tingkat' => 11, 'urutan' => 4],
            ['nama_kelas' => '12A', 'tingkat' => 12, 'urutan' => 5],
            ['nama_kelas' => '12B', 'tingkat' => 12, 'urutan' => 6],
        ];

        foreach ($smaClasses as $item) {
            Classroom::updateOrCreate(
                ['lembaga_id' => $sma->id, 'nama_kelas' => $item['nama_kelas']],
                [
                    'lembaga_id' => $sma->id,
                    'jenjang'    => 'SMA',
                    'nama_kelas' => $item['nama_kelas'],
                    'tingkat'    => $item['tingkat'],
                    'urutan'     => $item['urutan'],
                    'is_active'  => true,
                ]
            );
        }

        // =========================================================================
        // 2. DATA MASTER MATA PELAJARAN (TERINKRON DENGAN PLOTTING MATA PELAJARAN)
        // =========================================================================
        $smpMapels = [
            ['mata_pelajaran' => 'Matematika',                      'kode' => 'MTK'],
            ['mata_pelajaran' => 'Ilmu Pengetahuan Alam (IPA)',      'kode' => 'IPA'],
            ['mata_pelajaran' => 'Bahasa Indonesia',                'kode' => 'IND'],
            ['mata_pelajaran' => 'Bahasa Inggris',                  'kode' => 'ING'],
            ['mata_pelajaran' => 'Pendidikan Agama Islam (PAI)',    'kode' => 'PAI'],
            ['mata_pelajaran' => 'Informatika',                     'kode' => 'INF'],
            ['mata_pelajaran' => 'Ilmu Pengetahuan Sosial (IPS)',   'kode' => 'IPS'],
            ['mata_pelajaran' => 'Pendidikan Pancasila (PPKn)',     'kode' => 'PPKN'],
            ['mata_pelajaran' => 'Seni Budaya',                     'kode' => 'SNB'],
            ['mata_pelajaran' => 'Bahasa Arab',                     'kode' => 'ARB'],
        ];

        foreach ($smpMapels as $m) {
            MataPelajaran::firstOrCreate(
                ['lembaga_id' => $smp->id, 'mata_pelajaran' => $m['mata_pelajaran']],
                ['kode' => $m['kode']]
            );
        }

        $smaMapels = [
            ['mata_pelajaran' => 'Matematika',                      'kode' => 'MTK'],
            ['mata_pelajaran' => 'Fisika',                          'kode' => 'FIS'],
            ['mata_pelajaran' => 'Kimia',                           'kode' => 'KIM'],
            ['mata_pelajaran' => 'Biologi',                         'kode' => 'BIO'],
            ['mata_pelajaran' => 'Bahasa Indonesia',                'kode' => 'IND'],
            ['mata_pelajaran' => 'Bahasa Inggris',                  'kode' => 'ING'],
            ['mata_pelajaran' => 'Pendidikan Agama Islam (PAI)',    'kode' => 'PAI'],
            ['mata_pelajaran' => 'Informatika',                     'kode' => 'INF'],
            ['mata_pelajaran' => 'Ekonomi & Akuntansi',             'kode' => 'EKO'],
            ['mata_pelajaran' => 'Sosiologi',                       'kode' => 'SOS'],
            ['mata_pelajaran' => 'Sejarah Indonesia',               'kode' => 'SEJ'],
        ];

        foreach ($smaMapels as $m) {
            MataPelajaran::firstOrCreate(
                ['lembaga_id' => $sma->id, 'mata_pelajaran' => $m['mata_pelajaran']],
                ['kode' => $m['kode']]
            );
        }

        // =========================================================================
        // 3. DATA MASTER TIME SLOTS (SENIN S/D JUMAT, JAM 1 S/D 8 + NON-KBM)
        // =========================================================================
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        foreach ($days as $day) {
            if ($day === 'Senin') {
                $slots = [
                    ['jam_ke' => 0, 'mulai' => '07:00', 'selesai' => '07:45', 'break' => true,  'tipe' => 'Upacara',       'label' => 'Upacara Bendera'],
                    ['jam_ke' => 1, 'mulai' => '07:45', 'selesai' => '08:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 1'],
                    ['jam_ke' => 2, 'mulai' => '08:30', 'selesai' => '09:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 2'],
                    ['jam_ke' => 3, 'mulai' => '09:15', 'selesai' => '10:00', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 3'],
                    ['jam_ke' => 0, 'mulai' => '10:00', 'selesai' => '10:20', 'break' => true,  'tipe' => 'Istirahat',     'label' => 'Istirahat 1'],
                    ['jam_ke' => 4, 'mulai' => '10:20', 'selesai' => '11:05', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 4'],
                    ['jam_ke' => 5, 'mulai' => '11:05', 'selesai' => '11:50', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 5'],
                    ['jam_ke' => 0, 'mulai' => '11:50', 'selesai' => '12:45', 'break' => true,  'tipe' => 'Sholat Dhuhur', 'label' => 'Sholat Dhuhur & Isoma'],
                    ['jam_ke' => 6, 'mulai' => '12:45', 'selesai' => '13:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 6'],
                    ['jam_ke' => 7, 'mulai' => '13:30', 'selesai' => '14:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 7'],
                    ['jam_ke' => 8, 'mulai' => '14:15', 'selesai' => '15:00', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 8'],
                ];
            } elseif ($day === 'Jumat') {
                $slots = [
                    ['jam_ke' => 1, 'mulai' => '07:00', 'selesai' => '07:45', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 1'],
                    ['jam_ke' => 2, 'mulai' => '07:45', 'selesai' => '08:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 2'],
                    ['jam_ke' => 3, 'mulai' => '08:30', 'selesai' => '09:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 3'],
                    ['jam_ke' => 0, 'mulai' => '09:15', 'selesai' => '09:45', 'break' => true,  'tipe' => 'Istirahat',     'label' => 'Istirahat & Kudapan'],
                    ['jam_ke' => 4, 'mulai' => '09:45', 'selesai' => '10:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 4'],
                    ['jam_ke' => 5, 'mulai' => '10:30', 'selesai' => '11:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 5'],
                    ['jam_ke' => 0, 'mulai' => '11:15', 'selesai' => '13:00', 'break' => true,  'tipe' => 'Sholat Dhuhur', 'label' => 'Sholat Jumat & Istirahat'],
                    ['jam_ke' => 6, 'mulai' => '13:00', 'selesai' => '13:45', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 6'],
                    ['jam_ke' => 7, 'mulai' => '13:45', 'selesai' => '14:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 7'],
                    ['jam_ke' => 8, 'mulai' => '14:30', 'selesai' => '15:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 8'],
                ];
            } else {
                // Selasa, Rabu, Kamis
                $slots = [
                    ['jam_ke' => 1, 'mulai' => '07:00', 'selesai' => '07:45', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 1'],
                    ['jam_ke' => 2, 'mulai' => '07:45', 'selesai' => '08:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 2'],
                    ['jam_ke' => 3, 'mulai' => '08:30', 'selesai' => '09:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 3'],
                    ['jam_ke' => 4, 'mulai' => '09:15', 'selesai' => '10:00', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 4'],
                    ['jam_ke' => 0, 'mulai' => '10:00', 'selesai' => '10:20', 'break' => true,  'tipe' => 'Istirahat',     'label' => 'Istirahat'],
                    ['jam_ke' => 5, 'mulai' => '10:20', 'selesai' => '11:05', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 5'],
                    ['jam_ke' => 6, 'mulai' => '11:05', 'selesai' => '11:50', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 6'],
                    ['jam_ke' => 0, 'mulai' => '11:50', 'selesai' => '12:45', 'break' => true,  'tipe' => 'Sholat Dhuhur', 'label' => 'Sholat Dhuhur & Isoma'],
                    ['jam_ke' => 7, 'mulai' => '12:45', 'selesai' => '13:30', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 7'],
                    ['jam_ke' => 8, 'mulai' => '13:30', 'selesai' => '14:15', 'break' => false, 'tipe' => 'KBM',           'label' => 'Jam 8'],
                ];
            }

            foreach ($slots as $idx => $slot) {
                TimeSlot::updateOrCreate(
                    [
                        'jenjang'       => 'Semua',
                        'hari'          => $day,
                        'jam_ke'        => $slot['jam_ke'],
                        'waktu_mulai'   => $slot['mulai'],
                        'waktu_selesai' => $slot['selesai'],
                    ],
                    [
                        'jenjang'       => 'Semua',
                        'hari'          => $day,
                        'jam_ke'        => $slot['jam_ke'],
                        'waktu_mulai'   => $slot['mulai'],
                        'waktu_selesai' => $slot['selesai'],
                        'is_break'      => $slot['break'],
                        'tipe_kegiatan' => $slot['tipe'],
                        'label'         => $slot['label'],
                        'urutan'        => $idx + 1,
                        'is_active'     => true,
                    ]
                );
            }
        }

        // =========================================================================
        // 4. DATA GURU MULTI-JENJANG CONTOH (TERINKRON DENGAN MENU GURU SEKOLAH)
        // =========================================================================
        $defaultPassword = Hash::make('password123');

        // Guru 1: Guru Rangkap SMP & SMA (mengajar PAI di SMP dan SMA)
        $guruRangkap = User::updateOrCreate(
            ['email' => 'guru.rangkap@almuttaqin.id'],
            [
                'name'               => 'Ustadz Ahmad Zaki, M.Pd.',
                'password'           => $defaultPassword,
                'role'               => 'guru',
                'jabatan'            => 'Guru PAI & Bahasa Arab',
                'primary_lembaga_id' => $smp->id,
                'mata_pelajaran'     => 'Pendidikan Agama Islam (PAI)',
                'mapel_diajukan'     => 'Pendidikan Agama Islam (PAI)',
                'mapel_status'       => 'disetujui',
                'is_active'          => true,
            ]
        );
        $guruRangkap->lembagas()->sync([$smp->id, $sma->id]);

        // Guru 2: Guru Matematika SMP
        $guruMatSMP = User::updateOrCreate(
            ['email' => 'guru.mat.smp@almuttaqin.id'],
            [
                'name'               => 'Ustadzah Nurul Hidayah, S.Pd.',
                'password'           => $defaultPassword,
                'role'               => 'guru',
                'jabatan'            => 'Guru Matematika SMP',
                'primary_lembaga_id' => $smp->id,
                'mata_pelajaran'     => 'Matematika',
                'mapel_diajukan'     => 'Matematika',
                'mapel_status'       => 'disetujui',
                'is_active'          => true,
            ]
        );
        $guruMatSMP->lembagas()->sync([$smp->id]);

        // Guru 3: Guru Fisika / IPA Multi-Jenjang
        $guruIPA = User::updateOrCreate(
            ['email' => 'guru.ipa@almuttaqin.id'],
            [
                'name'               => 'Bambang Triyono, M.Si.',
                'password'           => $defaultPassword,
                'role'               => 'guru',
                'jabatan'            => 'Guru IPA & Fisika',
                'primary_lembaga_id' => $sma->id,
                'mata_pelajaran'     => 'Fisika',
                'mapel_diajukan'     => 'Fisika',
                'mapel_status'       => 'disetujui',
                'is_active'          => true,
            ]
        );
        $guruIPA->lembagas()->sync([$smp->id, $sma->id]);

        // Pastikan guru.pondok.smp juga punya mata_pelajaran disetujui (Matematika)
        $guruPondokSMP = User::where('email', 'guru.pondok.smp@almuttaqin.id')->first();
        if ($guruPondokSMP) {
            $guruPondokSMP->update([
                'mata_pelajaran' => 'Matematika',
                'mapel_diajukan' => 'Matematika',
                'mapel_status'   => 'disetujui',
            ]);
        }

        // =========================================================================
        // 5. SAMPLE INITIAL SCHEDULES (SEMESTER GASAL TA 2026/2027)
        // =========================================================================
        $mapelMatSMP = MataPelajaran::where('lembaga_id', $smp->id)->where('mata_pelajaran', 'Matematika')->first();
        $mapelPAI    = MataPelajaran::where('mata_pelajaran', 'like', '%PAI%')->first();
        $mapelFisika = MataPelajaran::where('lembaga_id', $sma->id)->where('mata_pelajaran', 'Fisika')->first();
        $mapelIPA    = MataPelajaran::where('lembaga_id', $smp->id)->where('mata_pelajaran', 'like', '%IPA%')->first();

        $c7A  = Classroom::where('nama_kelas', '7A')->first();
        $c8A  = Classroom::where('nama_kelas', '8A')->first();
        $c10A = Classroom::where('nama_kelas', '10A')->first();
        $c11A = Classroom::where('nama_kelas', '11A')->first();

        // Slot Senin: Jam 1 dan 2
        $slotSenin1 = TimeSlot::where('hari', 'Senin')->where('jam_ke', 1)->where('is_break', false)->first();
        $slotSenin2 = TimeSlot::where('hari', 'Senin')->where('jam_ke', 2)->where('is_break', false)->first();
        // Slot Senin: Jam 4 dan 5
        $slotSenin4 = TimeSlot::where('hari', 'Senin')->where('jam_ke', 4)->where('is_break', false)->first();
        $slotSenin5 = TimeSlot::where('hari', 'Senin')->where('jam_ke', 5)->where('is_break', false)->first();

        // Slot Selasa: Jam 1 dan 2
        $slotSelasa1 = TimeSlot::where('hari', 'Selasa')->where('jam_ke', 1)->where('is_break', false)->first();
        $slotSelasa2 = TimeSlot::where('hari', 'Selasa')->where('jam_ke', 2)->where('is_break', false)->first();
        // Slot Selasa: Jam 3 dan 4
        $slotSelasa3 = TimeSlot::where('hari', 'Selasa')->where('jam_ke', 3)->where('is_break', false)->first();
        $slotSelasa4 = TimeSlot::where('hari', 'Selasa')->where('jam_ke', 4)->where('is_break', false)->first();

        // Schedule 1: Guru Rangkap mengajar PAI di SMP Kelas 7A (Senin Jam 1 & 2)
        if ($c7A && $mapelPAI && $slotSenin1 && $slotSenin2) {
            Schedule::updateOrCreate(
                ['classroom_id' => $c7A->id, 'time_slot_id' => $slotSenin1->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal'],
                ['subject_id' => $mapelPAI->id, 'teacher_id' => $guruRangkap->id, 'is_locked' => false]
            );
            Schedule::updateOrCreate(
                ['classroom_id' => $c7A->id, 'time_slot_id' => $slotSenin2->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal'],
                ['subject_id' => $mapelPAI->id, 'teacher_id' => $guruRangkap->id, 'is_locked' => false]
            );
        }

        // Schedule 2: Guru Rangkap mengajar PAI di SMA Kelas 10A (Selasa Jam 1 & 2)
        if ($c10A && $mapelPAI && $slotSelasa1 && $slotSelasa2) {
            Schedule::updateOrCreate(
                ['classroom_id' => $c10A->id, 'time_slot_id' => $slotSelasa1->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal'],
                ['subject_id' => $mapelPAI->id, 'teacher_id' => $guruRangkap->id, 'is_locked' => false]
            );
            Schedule::updateOrCreate(
                ['classroom_id' => $c10A->id, 'time_slot_id' => $slotSelasa2->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal'],
                ['subject_id' => $mapelPAI->id, 'teacher_id' => $guruRangkap->id, 'is_locked' => false]
            );
        }

        // Schedule 3: Guru Matematika mengajar Matematika di SMP Kelas 8A (Senin Jam 4 & 5)
        if ($c8A && $mapelMatSMP && $slotSenin4 && $slotSenin5) {
            Schedule::updateOrCreate(
                ['classroom_id' => $c8A->id, 'time_slot_id' => $slotSenin4->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal'],
                ['subject_id' => $mapelMatSMP->id, 'teacher_id' => $guruMatSMP->id, 'is_locked' => false]
            );
            Schedule::updateOrCreate(
                ['classroom_id' => $c8A->id, 'time_slot_id' => $slotSenin5->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal'],
                ['subject_id' => $mapelMatSMP->id, 'teacher_id' => $guruMatSMP->id, 'is_locked' => false]
            );
        }

        // Schedule 4: Guru IPA mengajar Fisika di SMA Kelas 11A (Selasa Jam 3 & 4)
        if ($c11A && $mapelFisika && $slotSelasa3 && $slotSelasa4) {
            Schedule::updateOrCreate(
                ['classroom_id' => $c11A->id, 'time_slot_id' => $slotSelasa3->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal'],
                ['subject_id' => $mapelFisika->id, 'teacher_id' => $guruIPA->id, 'is_locked' => false]
            );
            Schedule::updateOrCreate(
                ['classroom_id' => $c11A->id, 'time_slot_id' => $slotSelasa4->id, 'tahun_ajaran' => '2026/2027', 'semester' => 'Gasal'],
                ['subject_id' => $mapelFisika->id, 'teacher_id' => $guruIPA->id, 'is_locked' => false]
            );
        }
    }
}
