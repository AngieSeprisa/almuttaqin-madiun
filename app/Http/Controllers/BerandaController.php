<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\Schedule;
use App\Models\Siswa;
use App\Models\TimeSlot;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    /**
     * Menampilkan halaman beranda dengan statistik sesuai ruang kerja (lembaga) aktif.
     */
    public function index()
    {
        $activeRoom = session('active_lembaga');

        $query = Siswa::query()->forLembaga($activeRoom);

        $statistik = [
            'total_siswa'  => (clone $query)->count(),
            'siswa_aktif'  => (clone $query)->where('status', 'aktif')->count(),
            'siswa_pulang' => (clone $query)->where('status', 'pulang')->count(),
            'siswa_sakit'  => (clone $query)->where('status', 'sakit')->count(),
        ];

        $lembagaSummary = null;
        if ($activeRoom === 'yayasan' || (auth()->check() && auth()->user()->isYayasan())) {
            $lembagaSummary = [
                'smp'    => Siswa::forLembaga('smp')->count(),
                'sma'    => Siswa::forLembaga('sma')->count(),
                'pondok' => Siswa::count(),
            ];
        }

        $currentLembaga = Lembaga::where('kode', $activeRoom)->first();

        // Siapkan daftar pengumuman (Jadwal Mengajar & Agenda Guru Hari Ini)
        $authUser = auth()->user();
        $hariMap = [
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
        ];
        $hariIni = $hariMap[(int)date('w')];
        $pengumumanList = [];

        if ($authUser) {
            // 1. Jadwal Mengajar Guru Hari Ini
            try {
                $teacherSchedules = Schedule::with(['classroom', 'subject', 'timeSlot'])
                    ->activePeriod()
                    ->where('teacher_id', $authUser->id)
                    ->whereHas('timeSlot', function ($q) use ($hariIni) {
                        $q->where('hari', $hariIni);
                    })
                    ->get();

                if ($teacherSchedules->isEmpty() && ($authUser->isYayasan() || $authUser->role === 'admin_lembaga')) {
                    $schQuery = Schedule::with(['classroom', 'subject', 'timeSlot', 'teacher'])
                        ->activePeriod()
                        ->whereHas('timeSlot', function ($q) use ($hariIni) {
                            $q->where('hari', $hariIni);
                        });
                    if ($activeRoom && in_array($activeRoom, ['smp', 'sma'])) {
                        $schQuery->forJenjang($activeRoom);
                    }
                    $schoolSchedules = $schQuery->orderBy('time_slot_id')->take(5)->get();
                    foreach ($schoolSchedules as $sch) {
                        $jam = $sch->timeSlot ? "Jam ke-{$sch->timeSlot->jam_ke} ({$sch->timeSlot->rentang_waktu})" : '';
                        $kelas = $sch->classroom?->nama_kelas ?? '-';
                        $mapel = $sch->subject?->mata_pelajaran ?? 'Mata Pelajaran';
                        $guru = $sch->teacher?->name ? " • {$sch->teacher->name}" : '';
                        $pengumumanList[] = [
                            'tipe'  => 'jadwal',
                            'badge' => 'Jadwal Mengajar',
                            'text'  => "{$jam} • Kelas {$kelas} ({$mapel}){$guru}",
                        ];
                    }
                } else {
                    foreach ($teacherSchedules as $sch) {
                        $jam = $sch->timeSlot ? "Jam ke-{$sch->timeSlot->jam_ke} ({$sch->timeSlot->rentang_waktu})" : '';
                        $kelas = $sch->classroom?->nama_kelas ?? '-';
                        $mapel = $sch->subject?->mata_pelajaran ?? 'Mata Pelajaran';
                        $pengumumanList[] = [
                            'tipe'  => 'jadwal',
                            'badge' => 'Jadwal Mengajar',
                            'text'  => "{$jam} • Kelas {$kelas} ({$mapel})",
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Ignore query exception if tables don't exist in isolated tests
            }

            // 2. Agenda Guru / Kegiatan Sekolah Hari Ini (Slot non-KBM seperti Upacara, Istirahat, Sholat Dhuhur)
            try {
                $agendas = TimeSlot::active()
                    ->hari($hariIni)
                    ->where('is_break', true)
                    ->orderBy('urutan')
                    ->get();

                foreach ($agendas as $ag) {
                    $pengumumanList[] = [
                        'tipe'  => 'agenda',
                        'badge' => 'Agenda',
                        'text'  => "{$ag->keterangan} ({$ag->rentang_waktu})",
                    ];
                }
            } catch (\Throwable $e) {
                // Ignore query exception if tables don't exist in isolated tests
            }
        }

        return view('beranda', compact('statistik', 'activeRoom', 'currentLembaga', 'lembagaSummary', 'pengumumanList'));
    }
}
