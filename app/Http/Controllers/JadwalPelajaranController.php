<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Lembaga;
use App\Models\MataPelajaran;
use App\Models\Schedule;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JadwalPelajaranController extends Controller
{
    /**
     * Tahun Ajaran & Semester Default
     */
    public const DEFAULT_TAHUN_AJARAN = '2026/2027';
    public const DEFAULT_SEMESTER = 'Gasal';

    /**
     * Tentukan daftar lembaga formal yang dapat diakses dan lembaga terpilih sesuai room aktif.
     */
    protected function resolveFormalScope(?User $authUser, ?string $requestedJenjang = null): array
    {
        $scopedFormal = Lembaga::activeScopedFormal($authUser);

        if ($authUser && $authUser->isYayasan()) {
            $formalLembagas = Lembaga::formalOptions();
        } elseif ($scopedFormal) {
            $formalLembagas = collect([$scopedFormal]);
        } else {
            $formalLembagas = Lembaga::formalOptions();
        }

        $selectedLembaga = null;
        if ($scopedFormal && $authUser && !$authUser->isYayasan()) {
            // Non-Yayasan di room formal selalu terkunci pada room lembaganya sendiri
            $selectedLembaga = $scopedFormal;
        } else {
            if ($requestedJenjang !== null && trim($requestedJenjang) !== '') {
                $resolved = Lembaga::resolveByKode($requestedJenjang);
                if ($resolved) {
                    $selectedLembaga = $formalLembagas->firstWhere('id', $resolved->id);
                }
            }
            if (!$selectedLembaga && $scopedFormal) {
                $selectedLembaga = $formalLembagas->firstWhere('id', $scopedFormal->id) ?? $scopedFormal;
            }
            if (!$selectedLembaga) {
                $selectedLembaga = $formalLembagas->first();
            }
        }

        $jenjang = $selectedLembaga ? $selectedLembaga->jenjang_key : 'SMP';
        $lookupKey = $selectedLembaga ? $selectedLembaga->kode : $jenjang;

        return [$formalLembagas, $selectedLembaga, $jenjang, $lookupKey, $scopedFormal];
    }

    /**
     * Tampilkan Halaman Master Jadwal Grid (Admin Lembaga & Yayasan)
     */
    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        [$formalLembagas, $selectedLembaga, $jenjang, $lookupKey, $scopedFormal] = $this->resolveFormalScope(
            $authUser,
            $request->filled('jenjang') ? (string) $request->get('jenjang') : null
        );

        $hariFilter = $request->get('hari', 'Semua');
        $validDays = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        $tahunAjaran = self::DEFAULT_TAHUN_AJARAN;
        $semester = self::DEFAULT_SEMESTER;

        // Ambil data ruang kelas untuk jenjang/lembaga yang dipilih
        $classrooms = Classroom::forJenjang($lookupKey)
            ->active()
            ->orderBy('urutan')
            ->get();

        // Ambil data time slots (termasuk slot umum 'Semua' dan slot khusus jenjang ini)
        $timeSlotsQuery = TimeSlot::active()->forJenjang($lookupKey)->orderBy('urutan');
        if ($hariFilter !== 'Semua' && in_array($hariFilter, $validDays)) {
            $timeSlotsQuery->where('hari', $hariFilter);
        }
        $timeSlots = $timeSlotsQuery->get();

        // Group time slots berdasarkan hari
        $timeSlotsByDay = $timeSlots->groupBy('hari');

        // Ambil seluruh jadwal yang telah terisi pada periode ini
        $schedules = Schedule::with(['classroom', 'subject', 'teacher', 'timeSlot'])
            ->activePeriod($tahunAjaran, $semester)
            ->forJenjang($lookupKey)
            ->get();

        // Index matrix: [hari][jam_ke][classroom_id] => Schedule
        $matrix = [];
        foreach ($schedules as $sched) {
            $matrix[$sched->timeSlot->hari][$sched->timeSlot->id][$sched->classroom_id] = $sched;
        }

        // Cek status kunci jadwal untuk jenjang ini
        $isLocked = Schedule::forJenjang($lookupKey)
            ->activePeriod($tahunAjaran, $semester)
            ->where('is_locked', true)
            ->exists();

        // Statistik ringkas untuk monitoring
        $totalKbmSlots = $timeSlots->where('is_break', false)->count() * $classrooms->count();
        $totalTerjadwal = $schedules->count();
        $totalKosong = max(0, $totalKbmSlots - $totalTerjadwal);
        $totalGuruPengampu = $schedules->pluck('teacher_id')->unique()->count();

        $stats = [
            'total_terjadwal' => $totalTerjadwal,
            'total_slot_kbm'  => $totalKbmSlots,
            'total_kosong'    => $totalKosong,
            'persentase'      => $totalKbmSlots > 0 ? round(($totalTerjadwal / $totalKbmSlots) * 100, 1) : 0,
            'total_guru'      => $totalGuruPengampu,
            'total_kelas'     => $classrooms->count(),
        ];

        // Teacher Legends
        $teacherLegends = [];
        foreach ($schedules as $sched) {
            if ($sched->teacher && $sched->subject) {
                $teacherLegends[$sched->teacher_id] = [
                    'nama_guru' => $sched->teacher->name,
                    'mapel'     => $sched->subject->nama_pelajaran,
                    'kode'      => $sched->subject->kode,
                ];
            }
        }

        return view('jadwal-pelajaran.index', compact(
            'jenjang',
            'formalLembagas',
            'selectedLembaga',
            'scopedFormal',
            'hariFilter',
            'validDays',
            'tahunAjaran',
            'semester',
            'classrooms',
            'timeSlotsByDay',
            'matrix',
            'isLocked',
            'stats',
            'totalTerjadwal',
            'totalKosong',
            'totalGuruPengampu',
            'teacherLegends',
            'authUser'
        ));
    }

    /**
     * Tampilkan Halaman "Jadwal Mengajar Saya" (Khusus Penginputan Mandiri Guru)
     */
    public function saya(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $tahunAjaran = self::DEFAULT_TAHUN_AJARAN;
        $semester = self::DEFAULT_SEMESTER;

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal) {
            $formalLembagas = collect([$scopedFormal]);
        } elseif ($authUser->isYayasan()) {
            $formalLembagas = Lembaga::formalOptions();
        } else {
            $formalLembagas = $authUser->accessibleLembagas()
                ->filter(fn (Lembaga $l) => $l->isFormalSchool())
                ->values();
        }

        // Guru yang login
        $teacherId = $authUser->id;

        // Ambil jadwal guru ini, discope ke room aktif bila berada di room formal
        $mySchedulesQuery = Schedule::with(['classroom', 'subject', 'timeSlot'])
            ->activePeriod($tahunAjaran, $semester)
            ->where('teacher_id', $teacherId);

        if ($scopedFormal) {
            $mySchedulesQuery->forJenjang($scopedFormal->kode);
        } elseif (!$authUser->isYayasan() && $formalLembagas->isNotEmpty()) {
            $allowedLembagaIds = $formalLembagas->pluck('id')->all();
            $mySchedulesQuery->whereHas('classroom', fn ($q) => $q->whereIn('lembaga_id', $allowedLembagaIds));
        }

        $mySchedules = $mySchedulesQuery->get();

        // Urutkan berdasarkan hari & jam_ke
        $dayWeights = ['Senin' => 1, 'Selasa' => 2, 'Rabu' => 3, 'Kamis' => 4, 'Jumat' => 5];
        $mySchedules = $mySchedules->sortBy(function ($item) use ($dayWeights) {
            $wHari = $dayWeights[$item->timeSlot->hari] ?? 99;
            return ($wHari * 100) + $item->timeSlot->jam_ke;
        });

        // Hitung statistik guru
        $totalJam = $mySchedules->count();
        $totalJamSMP = $mySchedules->filter(fn ($s) => strcasecmp((string) $s->classroom?->jenjang, 'SMP') === 0)->count();
        $totalJamSMA = $mySchedules->filter(fn ($s) => strcasecmp((string) $s->classroom?->jenjang, 'SMA') === 0)->count();

        // Status kunci untuk guru ini
        $hasLocked = $mySchedules->contains('is_locked', true);

        // SINKRONISASI 2: Mata Pelajaran yang Diampu Guru di Menu Guru Sekolah (users.mata_pelajaran)
        $teacherMapelRaw = trim((string) ($authUser->mata_pelajaran ?? ''));
        $teacherMapels = array_values(array_filter(array_map('trim', preg_split('/[,|;]/', $teacherMapelRaw))));
        $hasAssignedMapel = !empty($teacherMapels);
        $mapelStatus = $authUser->mapel_status; // 'disetujui', 'menunggu', dll.
        $isPrivileged = $authUser->isYayasan() || $authUser->role === 'admin_lembaga';

        // Helper untuk mencocokkan nama mapel plotting dengan penugasan guru di Guru Sekolah
        $matchMapel = function ($plottedName, $assignedList) {
            if (empty($assignedList)) {
                return false;
            }
            $cleanPlotted = strtolower(trim((string) $plottedName));
            foreach ($assignedList as $assigned) {
                $cleanAssigned = strtolower(trim((string) $assigned));
                if ($cleanPlotted === $cleanAssigned || str_contains($cleanPlotted, $cleanAssigned) || str_contains($cleanAssigned, $cleanPlotted)) {
                    return true;
                }
            }
            return false;
        };

        $markAssigned = function ($collection) use ($matchMapel, $teacherMapels) {
            return $collection->map(function ($item) use ($matchMapel, $teacherMapels) {
                $item->is_guru_assigned = !empty($teacherMapels) && $matchMapel($item->mata_pelajaran, $teacherMapels);
                return $item;
            });
        };

        // Bangun data dinamis per lembaga formal yang dapat diakses user
        $lembagaJadwalData = [];
        foreach ($formalLembagas as $lemb) {
            $cls = Classroom::forJenjang($lemb->kode)->active()->orderBy('urutan')->get();
            $allSubs = $markAssigned(MataPelajaran::forJenjang($lemb->kode)->orderBy('mata_pelajaran')->get());
            $filteredSubs = (!$isPrivileged && $hasAssignedMapel)
                ? $allSubs->where('is_guru_assigned', true)->values()
                : $allSubs;

            $aliasesLower = array_map('strtolower', Lembaga::jenjangAliasesForRoom($lemb->kode));
            $jamCount = $mySchedules->filter(function ($s) use ($lemb, $aliasesLower) {
                if ($s->classroom && (int) $s->classroom->lembaga_id === (int) $lemb->id) {
                    return true;
                }
                return in_array(strtolower(trim((string) $s->classroom?->jenjang)), $aliasesLower, true);
            })->count();

            $lembagaJadwalData[$lemb->jenjang_key] = [
                'lembaga'      => $lemb,
                'kode'         => $lemb->kode,
                'jenjang'      => $lemb->jenjang_key,
                'nama'         => $lemb->nama,
                'singkatan'    => $lemb->nama_singkat,
                'classrooms'   => $cls,
                'subjects'     => $filteredSubs,
                'all_subjects' => $allSubs,
                'total_jam'    => $jamCount,
            ];
        }

        // Kompatibilitas variabel lama (hanya terisi bila lembaga terkait masuk ruang lingkup user)
        $smpData = $lembagaJadwalData['SMP'] ?? null;
        $smaData = $lembagaJadwalData['SMA'] ?? null;
        $smpClassrooms = $smpData ? $smpData['classrooms'] : collect();
        $smaClassrooms = $smaData ? $smaData['classrooms'] : collect();
        $smpSubjects = $smpData ? $smpData['subjects'] : collect();
        $smaSubjects = $smaData ? $smaData['subjects'] : collect();
        $allSmpSubjects = $smpData ? $smpData['all_subjects'] : collect();
        $allSmaSubjects = $smaData ? $smaData['all_subjects'] : collect();

        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        return view('jadwal-pelajaran.saya', compact(
            'authUser',
            'formalLembagas',
            'scopedFormal',
            'lembagaJadwalData',
            'tahunAjaran',
            'semester',
            'mySchedules',
            'totalJam',
            'totalJamSMP',
            'totalJamSMA',
            'hasLocked',
            'smpClassrooms',
            'smaClassrooms',
            'smpSubjects',
            'smaSubjects',
            'allSmpSubjects',
            'allSmaSubjects',
            'teacherMapelRaw',
            'teacherMapels',
            'hasAssignedMapel',
            'mapelStatus',
            'isPrivileged',
            'days'
        ));
    }

    /**
     * Simpan Penginputan Jadwal Mengajar Mandiri Guru dengan Validasi Ketat Anti-Bentrok
     */
    public function storeSaya(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'subject_id'   => 'required|exists:mata_pelajarans,id',
            'hari'         => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat',
            'jam_mulai'    => 'required|integer|min:1|max:8',
            'jam_selesai'  => 'required|integer|min:1|max:8|gte:jam_mulai',
            'teacher_id'   => 'nullable|exists:users,id',
            'catatan'      => 'nullable|string|max:255',
        ], [
            'classroom_id.required' => 'Pilih ruang kelas tujuan.',
            'subject_id.required'   => 'Pilih mata pelajaran yang diampu.',
            'subject_id.exists'     => 'Mata pelajaran tidak ditemukan di master Plotting Mata Pelajaran.',
            'hari.required'         => 'Pilih hari mengajar.',
            'jam_mulai.required'    => 'Pilih jam pelajaran mulai.',
            'jam_selesai.required'  => 'Pilih jam pelajaran selesai.',
            'jam_selesai.gte'       => 'Jam selesai tidak boleh lebih awal dari jam mulai.',
        ]);

        $tahunAjaran = self::DEFAULT_TAHUN_AJARAN;
        $semester = self::DEFAULT_SEMESTER;

        // Tentukan guru yang dijadwalkan: jika admin mengisi untuk orang lain atau guru sendiri
        $teacherId = $authUser->id;
        if (($authUser->isYayasan() || $authUser->role === 'admin_lembaga') && !empty($validated['teacher_id'])) {
            $teacherId = (int) $validated['teacher_id'];
        }

        $classroom = Classroom::with('lembaga')->findOrFail($validated['classroom_id']);
        $subject = MataPelajaran::with('lembaga')->findOrFail($validated['subject_id']);
        $teacher = User::findOrFail($teacherId);

        // Proteksi akses lintas room lembaga
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal) {
            $classInRoom = Classroom::forJenjang($scopedFormal->kode)->whereKey($classroom->id)->exists();
            $subjectInRoom = $subject->lembaga_id && (int) $subject->lembaga_id === (int) $scopedFormal->id;
            if (!$classInRoom || !$subjectInRoom) {
                abort(403, 'Anda hanya dapat menginput jadwal untuk ruang kerja lembaga aktif Anda.');
            }
        }

        // A. Validasi Sinkronisasi Jenjang/Lembaga Kelas vs Plotting Mata Pelajaran
        $classroomLembaga = $classroom->lembaga ?? Lembaga::resolveByKode($classroom->jenjang);
        $subjectLembaga = $subject->lembaga;
        $mismatch = false;
        if ($classroomLembaga && $subjectLembaga) {
            $mismatch = (int) $classroomLembaga->id !== (int) $subjectLembaga->id;
        } elseif ($subjectLembaga) {
            $aliases = array_map('strtolower', Lembaga::jenjangAliasesForRoom($subjectLembaga->kode));
            $mismatch = !in_array(strtolower(trim((string) $classroom->jenjang)), $aliases, true);
        }
        if ($mismatch) {
            $expectedLabel = $classroomLembaga?->nama_singkat ?? $classroom->jenjang;
            $subjectLabel = $subjectLembaga?->nama_singkat ?? strtoupper((string) $subjectLembaga?->kode);
            $msg = "Mata pelajaran {$subject->mata_pelajaran} ({$subject->kode}) terdaftar untuk jenjang {$subjectLabel}, tidak dapat dijadwalkan pada kelas {$classroom->nama_kelas} ({$expectedLabel}).";
            throw ValidationException::withMessages([
                'subject_id' => $msg,
                'jadwal'     => $msg,
            ]);
        }

        // B. Validasi Sinkronisasi Mata Pelajaran Guru Sekolah (untuk guru biasa)
        $isPrivileged = $authUser->isYayasan() || $authUser->role === 'admin_lembaga';
        $teacherMapelRaw = trim((string)($teacher->mata_pelajaran ?? ''));
        if (!$isPrivileged && !empty($teacherMapelRaw)) {
            $teacherMapels = array_values(array_filter(array_map('trim', preg_split('/[,|;]/', $teacherMapelRaw))));
            $match = false;
            foreach ($teacherMapels as $assigned) {
                if (str_contains(strtolower($subject->mata_pelajaran), strtolower($assigned)) || str_contains(strtolower($assigned), strtolower($subject->mata_pelajaran))) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                $msg = "Anda hanya berhak menginput jadwal untuk mata pelajaran yang Anda ampu di Guru Sekolah ({$teacherMapelRaw}).";
                throw ValidationException::withMessages([
                    'subject_id' => $msg,
                    'jadwal'     => $msg,
                ]);
            }
        }

        // 1. Proteksi Finalisasi / Kunci Jadwal
        $isLocked = Schedule::where('classroom_id', $classroom->id)
            ->activePeriod($tahunAjaran, $semester)
            ->where('is_locked', true)
            ->exists();

        if ($isLocked) {
            $msg = "Jadwal untuk jenjang {$classroom->jenjang} (Kelas {$classroom->nama_kelas}) telah dikunci oleh pihak kurikulum/administrasi dan tidak dapat diubah.";
            throw ValidationException::withMessages([
                'classroom_id' => $msg,
                'jadwal'       => $msg,
            ]);
        }

        // 2. Ambil seluruh slot waktu pada rentang jam yang dipilih
        $jamMulai = (int) $validated['jam_mulai'];
        $jamSelesai = (int) $validated['jam_selesai'];
        $hari = $validated['hari'];

        $slotLookup = $classroom->lembaga?->kode ?? $classroom->jenjang;
        $targetSlots = TimeSlot::where('hari', $hari)
            ->whereBetween('jam_ke', [$jamMulai, $jamSelesai])
            ->active()
            ->forJenjang($slotLookup)
            ->orderBy('urutan')
            ->get();

        if ($targetSlots->isEmpty()) {
            $msg = "Tidak ditemukan slot waktu KBM yang valid untuk hari {$hari} pada jam {$jamMulai} s/d {$jamSelesai}.";
            throw ValidationException::withMessages([
                'jam_mulai' => $msg,
                'jadwal'    => $msg,
            ]);
        }

        // 3. Proteksi Slot Khusus (Non-KBM: Upacara, Istirahat, Sholat Dhuhur / Isoma)
        foreach ($targetSlots as $slot) {
            if ($slot->is_break) {
                $msg = "Slot waktu hari {$hari} pada pukul {$slot->waktu_mulai_formatted} - {$slot->waktu_selesai_formatted} adalah kegiatan khusus ({$slot->label}) dan tidak dapat dipilih untuk jam mengajar KBM.";
                throw ValidationException::withMessages([
                    'jam_mulai' => $msg,
                    'jadwal'    => $msg,
                ]);
            }
        }

        // Ambil hanya slot KBM murni
        $kbmSlots = $targetSlots->where('is_break', false);

        // 4. Validasi Bentrok Kelas & Bentrok Guru Lintas Jenjang
        foreach ($kbmSlots as $slot) {
            // A. Validasi Bentrok Kelas: Kelas sudah terisi mata pelajaran lain pada slot ini
            $existingClassSchedule = Schedule::with(['subject', 'teacher'])
                ->where('classroom_id', $classroom->id)
                ->where('time_slot_id', $slot->id)
                ->activePeriod($tahunAjaran, $semester)
                ->first();

            if ($existingClassSchedule) {
                $guruLain = $existingClassSchedule->teacher?->name ?? 'Guru Lain';
                $mapelLain = $existingClassSchedule->subject?->nama_pelajaran ?? 'Mata Pelajaran Lain';
                $msg = "Bentrok Kelas: Kelas {$classroom->nama_kelas} pada hari {$slot->hari} Jam ke-{$slot->jam_ke} ({$slot->rentang_waktu}) sudah terisi oleh {$mapelLain} (Pengampu: {$guruLain}).";
                throw ValidationException::withMessages([
                    'classroom_id' => $msg,
                    'jadwal'       => $msg,
                ]);
            }

            // B. Validasi Bentrok Guru Lintas Jenjang (SANGAT PENTING!)
            // Cek apakah guru ini di jam yang sama sudah memiliki jadwal mengajar di kelas lain (baik SMP maupun SMA)
            $existingTeacherSchedule = Schedule::with(['classroom', 'subject', 'timeSlot'])
                ->where('teacher_id', $teacherId)
                ->activePeriod($tahunAjaran, $semester)
                ->whereHas('timeSlot', function ($q) use ($slot) {
                    $q->where('hari', $slot->hari)
                      ->where(function ($w) use ($slot) {
                          $w->where('jam_ke', $slot->jam_ke)
                            ->orWhere(function ($timeOverlap) use ($slot) {
                                $timeOverlap->where('waktu_mulai', '<', $slot->waktu_selesai)
                                            ->where('waktu_selesai', '>', $slot->waktu_mulai);
                            });
                      });
                })
                ->first();

            if ($existingTeacherSchedule) {
                $conflictingJenjang = $existingTeacherSchedule->classroom->jenjang;
                $conflictingClass = $existingTeacherSchedule->classroom->nama_kelas;
                $conflictingSubject = $existingTeacherSchedule->subject?->nama_pelajaran ?? 'Mata Pelajaran';
                $conflictingTime = $existingTeacherSchedule->timeSlot?->rentang_waktu ?? '';
                $msg = "Bentrok Jadwal Guru Lintas Jenjang: Anda sudah dijadwalkan mengajar {$conflictingSubject} di jenjang {$conflictingJenjang} (Kelas {$conflictingClass}) pada hari {$slot->hari} Jam ke-{$slot->jam_ke} ({$conflictingTime}). Anda tidak dapat mengambil jam yang sama di kelas {$classroom->nama_kelas}.";

                throw ValidationException::withMessages([
                    'jam_mulai' => $msg,
                    'jadwal'    => $msg,
                ]);
            }
        }

        // 5. Simpan jadwal ke database secara atomik dalam Transaction
        DB::transaction(function () use ($kbmSlots, $classroom, $subject, $teacherId, $tahunAjaran, $semester, $validated) {
            foreach ($kbmSlots as $slot) {
                Schedule::create([
                    'classroom_id' => $classroom->id,
                    'time_slot_id' => $slot->id,
                    'subject_id'   => $subject->id,
                    'teacher_id'   => $teacherId,
                    'tahun_ajaran' => $tahunAjaran,
                    'semester'     => $semester,
                    'is_locked'    => false,
                    'catatan'      => $validated['catatan'] ?? null,
                ]);
            }
        });

        $slotCount = $kbmSlots->count();
        $msg = "Jadwal mengajar {$subject->nama_pelajaran} di Kelas {$classroom->nama_kelas} hari {$hari} (Jam ke-{$jamMulai} s/d {$jamSelesai}, total {$slotCount} jam) berhasil disimpan!";

        return redirect()->route('jadwal-pelajaran.saya')->with('success', $msg);
    }

    /**
     * Hapus / Batalkan Jadwal Mengajar (Selama Belum Dikunci)
     */
    public function destroy($id)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        $schedule = Schedule::with(['classroom', 'subject', 'timeSlot'])->findOrFail($id);

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal && !Schedule::forJenjang($scopedFormal->kode)->whereKey($schedule->id)->exists()) {
            abort(403, 'Anda tidak memiliki akses untuk menghapus jadwal lembaga lain.');
        }

        // Cek status kunci
        if ($schedule->is_locked) {
            return redirect()->back()->with('error', 'Jadwal tidak dapat dihapus karena telah dikunci/difinalisasi oleh pihak kurikulum.');
        }

        // Cek hak akses pemilik jadwal
        $isPrivileged = $authUser->isYayasan() || $authUser->role === 'admin_lembaga';
        if (!$isPrivileged && $schedule->teacher_id !== $authUser->id) {
            abort(403, 'Anda hanya memiliki izin untuk menghapus jadwal mengajar milik Anda sendiri.');
        }

        $namaMapel = $schedule->subject?->nama_pelajaran ?? 'Mata Pelajaran';
        $namaKelas = $schedule->classroom?->nama_kelas ?? 'Kelas';
        $hariJam = ($schedule->timeSlot?->hari ?? '') . ' Jam ke-' . ($schedule->timeSlot?->jam_ke ?? '');

        $schedule->delete();

        return redirect()->back()->with('success', "Jadwal {$namaMapel} ({$namaKelas}, {$hariJam}) berhasil dibatalkan/dihapus.");
    }

    /**
     * Kunci / Finalisasi Jadwal Pelajaran (Admin Lembaga & Yayasan)
     */
    public function toggleLock(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if (!$authUser->isYayasan() && $authUser->role !== 'admin_lembaga') {
            abort(403, 'Hanya Admin Lembaga atau Yayasan yang memiliki hak untuk mengunci/membuka kunci jadwal.');
        }

        if ($request->has('is_locked')) {
            $isLockedVal = filter_var($request->get('is_locked'), FILTER_VALIDATE_BOOLEAN);
            $request->merge(['action' => $isLockedVal ? 'lock' : 'unlock']);
        }

        $validated = $request->validate([
            'jenjang' => 'required|string|max:50',
            'action'  => 'required|in:lock,unlock',
        ]);

        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $targetJenjang = trim((string) $validated['jenjang']);

        if (strcasecmp($targetJenjang, 'ALL') === 0) {
            if (!$authUser->isYayasan()) {
                abort(403, 'Hanya Yayasan yang dapat mengunci seluruh jenjang sekaligus.');
            }
            $lookupKey = 'ALL';
            $jenjangText = 'seluruh jenjang formal';
        } else {
            $resolvedLembaga = Lembaga::resolveByKode($targetJenjang);
            if ($scopedFormal && !$authUser->isYayasan() && (!$resolvedLembaga || (int) $resolvedLembaga->id !== (int) $scopedFormal->id)) {
                abort(403, 'Anda hanya dapat mengunci/membuka jadwal untuk lembaga Anda sendiri.');
            }
            if (!$authUser->isYayasan() && (!$resolvedLembaga || !$authUser->hasAccessToLembaga($resolvedLembaga->kode))) {
                abort(403, 'Anda tidak memiliki akses ke lembaga tersebut.');
            }
            $lookupKey = $resolvedLembaga ? $resolvedLembaga->kode : $targetJenjang;
            $jenjangText = 'jenjang ' . ($resolvedLembaga?->nama_singkat ?? $targetJenjang);
        }

        $tahunAjaran = self::DEFAULT_TAHUN_AJARAN;
        $semester = self::DEFAULT_SEMESTER;
        $shouldLock = $validated['action'] === 'lock';

        $query = Schedule::activePeriod($tahunAjaran, $semester);
        if ($lookupKey !== 'ALL') {
            $query->forJenjang($lookupKey);
        }

        $updatedCount = $query->update(['is_locked' => $shouldLock]);

        $statusText = $shouldLock ? 'dikunci (final)' : 'dibuka kembali untuk penginputan';

        return redirect()->back()->with('success', "Jadwal pelajaran {$jenjangText} berhasil {$statusText}. Total {$updatedCount} slot jadwal terpengaruh.");
    }

    /**
     * Tampilan Siap Cetak (Print View A4 Landscape) Jadwal Pelajaran per Jenjang
     */
    public function cetak(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();
        [$formalLembagas, $lembaga, $jenjang, $lookupKey] = $this->resolveFormalScope(
            $authUser,
            $request->filled('jenjang') ? (string) $request->get('jenjang') : null
        );

        $tahunAjaran = self::DEFAULT_TAHUN_AJARAN;
        $semester = self::DEFAULT_SEMESTER;

        $classrooms = Classroom::forJenjang($lookupKey)
            ->active()
            ->orderBy('urutan')
            ->get();

        $timeSlots = TimeSlot::active()->forJenjang($lookupKey)->orderBy('urutan')->get();
        $timeSlotsByDay = $timeSlots->groupBy('hari');

        $schedules = Schedule::with(['classroom', 'subject', 'teacher', 'timeSlot'])
            ->activePeriod($tahunAjaran, $semester)
            ->forJenjang($lookupKey)
            ->get();

        $matrix = [];
        $teacherLegends = [];

        foreach ($schedules as $sched) {
            $matrix[$sched->timeSlot->hari][$sched->timeSlot->id][$sched->classroom_id] = $sched;
            if ($sched->teacher && $sched->subject) {
                $teacherLegends[$sched->teacher_id] = [
                    'nama_guru' => $sched->teacher->name,
                    'mapel'     => $sched->subject->nama_pelajaran,
                    'kode'      => $sched->subject->kode,
                ];
            }
        }

        return view('jadwal-pelajaran.cetak', compact(
            'jenjang',
            'lembaga',
            'tahunAjaran',
            'semester',
            'classrooms',
            'timeSlotsByDay',
            'matrix',
            'teacherLegends'
        ));
    }

    /**
     * Endpoint API JSON untuk cascading dropdown Kelas & Mapel berdasarkan pilihan Jenjang
     */
    public function ajaxClassesAndSubjects(Request $request)
    {
        /** @var User|null $authUser */
        $authUser = Auth::user();

        [$formalLembagas, $selectedLembaga, $jenjang, $lookupKey] = $this->resolveFormalScope(
            $authUser,
            $request->filled('jenjang') ? (string) $request->get('jenjang') : null
        );

        $classrooms = Classroom::forJenjang($lookupKey)->active()->orderBy('urutan')->get(['id', 'nama_kelas', 'tingkat']);
        $allSubjects = MataPelajaran::forJenjang($lookupKey)->orderBy('mata_pelajaran')->get();

        $teacherMapelRaw = trim((string)($authUser?->mata_pelajaran ?? ''));
        $teacherMapels = array_values(array_filter(array_map('trim', preg_split('/[,|;]/', $teacherMapelRaw))));
        $hasAssignedMapel = !empty($teacherMapels);
        $isPrivileged = $authUser && ($authUser->isYayasan() || $authUser->role === 'admin_lembaga');

        $matchMapel = function ($plottedName, $assignedList) {
            if (empty($assignedList)) return false;
            $cleanPlotted = strtolower(trim((string)$plottedName));
            foreach ($assignedList as $assigned) {
                $cleanAssigned = strtolower(trim((string)$assigned));
                if ($cleanPlotted === $cleanAssigned || str_contains($cleanPlotted, $cleanAssigned) || str_contains($cleanAssigned, $cleanPlotted)) {
                    return true;
                }
            }
            return false;
        };

        $showAll = $request->boolean('all_subjects', false);

        if (!$isPrivileged && $hasAssignedMapel && !$showAll) {
            $filtered = $allSubjects->filter(fn ($m) => $matchMapel($m->mata_pelajaran, $teacherMapels))->values();
        } else {
            $filtered = $allSubjects;
        }

        $subjectsData = $filtered->map(function ($s) use ($matchMapel, $teacherMapels) {
            $isAssigned = !empty($teacherMapels) && $matchMapel($s->mata_pelajaran, $teacherMapels);
            return [
                'id'             => $s->id,
                'kode'           => $s->kode,
                'nama'           => $s->mata_pelajaran,
                'nama_pelajaran' => $s->mata_pelajaran,
                'is_assigned'    => $isAssigned,
            ];
        });

        return response()->json([
            'jenjang'        => $jenjang,
            'classrooms'     => $classrooms,
            'subjects'       => $subjectsData,
            'teacher_mapels' => $teacherMapels,
        ]);
    }
}
