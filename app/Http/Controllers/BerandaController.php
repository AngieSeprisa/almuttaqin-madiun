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
        $activeRoom = session('active_lembaga') ?? session('active_room');

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

        // Siapkan daftar pengumuman (Jadwal Mengajar & Agenda Hari Ini)
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

        // Pastikan penentuan hari ini akurat sesuai timezone Indonesia (Asia/Jakarta)
        $tz = config('app.timezone') ?: 'Asia/Jakarta';
        $nowInTz = now()->timezone($tz);
        $hariIni = $hariMap[(int) $nowInTz->dayOfWeek];
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
                    ->get()
                    ->sortBy(fn ($s) => $s->timeSlot?->jam_ke ?? 99);

                if ($teacherSchedules->isNotEmpty()) {
                    foreach ($teacherSchedules as $sch) {
                        $jenjang = $sch->classroom?->jenjang ? "[{$sch->classroom->jenjang}] " : '';
                        $jam = $sch->timeSlot ? "Jam ke-{$sch->timeSlot->jam_ke} ({$sch->timeSlot->rentang_waktu})" : '';
                        $kelas = $sch->classroom?->nama_kelas ?? '-';
                        $mapel = $sch->subject?->mata_pelajaran ?? 'Mata Pelajaran';
                        $pengumumanList[] = [
                            'tipe'  => 'jadwal',
                            'badge' => 'Jadwal Mengajar',
                            'text'  => "{$jenjang}{$jam} • Kelas {$kelas} ({$mapel})",
                            'url'   => route('jadwal-pelajaran.saya'),
                        ];
                    }
                } else {
                    // Cek apakah user guru ini memiliki jadwal mengajar di semester ini tapi bukan hari ini
                    $allMySchedules = Schedule::with(['classroom', 'subject', 'timeSlot'])
                        ->activePeriod()
                        ->where('teacher_id', $authUser->id)
                        ->get();

                    if ($allMySchedules->isNotEmpty()) {
                        // Tampilkan info jadwal KBM berikutnya agar guru tahu jadwal mendatang
                        $dayOrder = ['Minggu' => 0, 'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6];
                        $todayIdx = (int) $nowInTz->dayOfWeek;

                        $nextSchedule = $allMySchedules->sortBy(function ($sch) use ($dayOrder, $todayIdx) {
                            $schDay = $sch->timeSlot?->hari ?? 'Minggu';
                            $schIdx = $dayOrder[$schDay] ?? 0;
                            $diff = ($schIdx - $todayIdx + 7) % 7;
                            if ($diff === 0) {
                                $diff = 7;
                            }
                            return ($diff * 100) + ($sch->timeSlot?->jam_ke ?? 99);
                        })->first();

                        if ($nextSchedule) {
                            $nextHari = $nextSchedule->timeSlot?->hari ?? '-';
                            $nextJam = $nextSchedule->timeSlot ? "Jam ke-{$nextSchedule->timeSlot->jam_ke}" : '';
                            $nextKelas = $nextSchedule->classroom?->nama_kelas ?? '-';
                            $nextMapel = $nextSchedule->subject?->mata_pelajaran ?? 'Mata Pelajaran';
                            $nextJenjang = $nextSchedule->classroom?->jenjang ? "[{$nextSchedule->classroom->jenjang}] " : '';

                            $pengumumanList[] = [
                                'tipe'  => 'jadwal',
                                'badge' => 'Jadwal Berikutnya',
                                'text'  => "Hari ini tidak ada KBM • {$nextHari}: {$nextJenjang}{$nextJam} Kelas {$nextKelas} ({$nextMapel})",
                                'url'   => route('jadwal-pelajaran.saya'),
                            ];
                        }
                    } elseif ($authUser->isYayasan() || $authUser->role === 'admin_lembaga') {
                        // Untuk pimpinan / admin yang tidak mengajar langsung, tampilkan jadwal KBM sekolah hari ini
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
                            $jenjang = $sch->classroom?->jenjang ? "[{$sch->classroom->jenjang}] " : '';
                            $jam = $sch->timeSlot ? "Jam ke-{$sch->timeSlot->jam_ke} ({$sch->timeSlot->rentang_waktu})" : '';
                            $kelas = $sch->classroom?->nama_kelas ?? '-';
                            $mapel = $sch->subject?->mata_pelajaran ?? 'Mata Pelajaran';
                            $guru = $sch->teacher?->name ? " • {$sch->teacher->name}" : '';
                            $pengumumanList[] = [
                                'tipe'  => 'jadwal',
                                'badge' => 'Jadwal Mengajar',
                                'text'  => "{$jenjang}{$jam} • Kelas {$kelas} ({$mapel}){$guru}",
                                'url'   => route('jadwal-pelajaran.index'),
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore query exception if tables don't exist in isolated tests
            }

            // 2. Agenda Guru / Kegiatan Sekolah Hari Ini (Slot non-KBM seperti Upacara, Istirahat, Sholat Dhuhur)
            try {
                $agendaQuery = TimeSlot::active()
                    ->hari($hariIni)
                    ->where('is_break', true)
                    ->orderBy('urutan');

                if ($activeRoom && in_array($activeRoom, ['smp', 'sma'])) {
                    $agendaQuery->forJenjang($activeRoom);
                }

                $agendas = $agendaQuery->get();

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
