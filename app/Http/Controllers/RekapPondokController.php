<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\MasterDevice;
use App\Models\MasterKelasPengajian;
use App\Models\MasterSesi;
use App\Models\PerizinanSakit;
use App\Models\PerizinanSambang;
use App\Models\Presensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class RekapPondokController extends Controller
{
    /**
     * Halaman utama Rekapitulasi Presensi Pondok (Sholat & Pengajian).
     */
    public function index(Request $request)
    {
        $data = $this->buildRekapData($request);

        return view('rekap-absensi.pondok', $data);
    }

    /**
     * Ekspor Rekap Presensi Pondok ke CSV/Excel atau Cetak PDF/Print View.
     */
    public function export(Request $request)
    {
        $data = $this->buildRekapData($request);
        $format = strtolower((string) $request->get('format', 'excel'));

        if (in_array($format, ['pdf', 'print', 'cetak'], true)) {
            return view('rekap-absensi.pondok-cetak', $data);
        }

        $mode = $data['mode'];
        $tanggal = $data['tanggal'];
        $bulan = $data['bulan'];
        $tahun = $data['tahun'];
        $selectedSesi = $data['selectedSesi'];
        $sesiSlug = $selectedSesi ? strtolower(preg_replace('/[^a-zA-Z0-9]+/', '_', $selectedSesi->nama_sesi)) : $data['kategori'];

        if ($mode === 'bulanan') {
            $fileName = sprintf('rekap_pondok_bulanan_%s_%04d_%02d.csv', $sesiSlug, $tahun, $bulan);
        } else {
            $fileName = sprintf('rekap_pondok_harian_%s_%s.csv', $sesiSlug, str_replace('-', '', $tanggal));
        }

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($data, $mode) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM untuk kompatibilitas Microsoft Excel
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            if ($mode === 'bulanan') {
                $headerRow = ['No', 'NISN', 'Nama Santri', 'Gender', 'Kamar', 'Kelas Pengajian'];
                for ($d = 1; $d <= $data['daysInMonth']; $d++) {
                    $headerRow[] = (string) $d;
                }
                $headerRow = array_merge($headerRow, ['Total H', 'Total T', 'Total I/S', 'Total A', 'Persentase (%)']);
                fputcsv($handle, $headerRow);

                foreach ($data['matriksRows'] as $idx => $row) {
                    $line = [
                        $idx + 1,
                        $row['nisn'],
                        $row['nama_lengkap'],
                        $row['gender_code'],
                        $row['kamar'],
                        $row['kelas_pengajian'],
                    ];
                    for ($d = 1; $d <= $data['daysInMonth']; $d++) {
                        $line[] = $row['days'][$d]['code'] ?? '-';
                    }
                    $line[] = $row['total_h'];
                    $line[] = $row['total_t'];
                    $line[] = $row['total_is'];
                    $line[] = $row['total_a'];
                    $line[] = $row['persentase'] . '%';
                    fputcsv($handle, $line);
                }
            } else {
                fputcsv($handle, [
                    'No',
                    'Tanggal',
                    'NISN',
                    'Nama Santri',
                    'Gender',
                    'Kamar',
                    'Kelas Pengajian',
                    'Sesi',
                    'Jam Tap',
                    'Titik Mesin ESP32',
                    'Status',
                    'Keterangan',
                ]);

                foreach ($data['dailyRows'] as $idx => $row) {
                    fputcsv($handle, [
                        $idx + 1,
                        $data['tanggal'],
                        $row['nisn'],
                        $row['nama_lengkap'],
                        $row['gender_label'],
                        $row['kamar'],
                        $row['kelas_pengajian'],
                        $row['sesi_nama'],
                        $row['jam_tap'] ?: '-',
                        $row['device_label'] ?: '-',
                        $row['status_label'],
                        $row['keterangan'] ?: '-',
                    ]);
                }
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Membangun seluruh data rekapitulasi harian & matriks bulanan.
     */
    private function buildRekapData(Request $request): array
    {
        // 1. Parameter Filter
        $kategori = strtolower(trim((string) $request->get('kategori', 'semua')));
        if (!in_array($kategori, ['semua', 'sholat', 'pengajian'], true)) {
            $kategori = 'semua';
        }

        $sesiId = $request->get('sesi_id');
        $filterKamar = trim((string) $request->get('kamar', ''));
        $filterKelasPengajianId = trim((string) $request->get('kelas_pengajian_id', ''));
        $filterGender = strtoupper(trim((string) $request->get('gender', '')));
        if (!in_array($filterGender, ['L', 'P'], true)) {
            $filterGender = '';
        }

        $filterStatus = strtolower(trim((string) $request->get('status', '')));
        $search = trim((string) $request->get('search', ''));

        $mode = strtolower(trim((string) $request->get('mode', $request->get('tab', 'harian'))));
        if (!in_array($mode, ['harian', 'bulanan'], true)) {
            $mode = 'harian';
        }

        $tanggalInput = $request->get('tanggal');
        try {
            $tanggalCarbon = $tanggalInput ? Carbon::parse($tanggalInput) : Carbon::today();
        } catch (\Throwable $e) {
            $tanggalCarbon = Carbon::today();
        }
        $tanggal = $tanggalCarbon->toDateString();

        $bulan = (int) $request->get('bulan', $tanggalCarbon->month);
        if ($bulan < 1 || $bulan > 12) {
            $bulan = (int) $tanggalCarbon->month;
        }

        $tahun = (int) $request->get('tahun', $tanggalCarbon->year);
        if ($tahun < 2020 || $tahun > 2100) {
            $tahun = (int) $tanggalCarbon->year;
        }

        // 2. Data Master Sesi Pondok (Sholat & Pengajian)
        $allSesiPondok = MasterSesi::with('allocations')
            ->whereIn('kategori', ['sholat', 'pengajian'])
            ->orderBy('jam_mulai')
            ->orderBy('id')
            ->get();

        $sesiList = $allSesiPondok->filter(function (MasterSesi $s) use ($kategori) {
            if ($kategori === 'semua') {
                return true;
            }
            return strtolower((string) $s->kategori) === $kategori;
        })->values();

        $selectedSesi = null;
        if (!empty($sesiId)) {
            $selectedSesi = $allSesiPondok->firstWhere('id', (int) $sesiId)
                ?? MasterSesi::with('allocations')->find((int) $sesiId);
        }

        // Jika sesi spesifik dipilih, sinkronkan daftar sesi aktif yang dievaluasi
        $evaluatedSesis = $selectedSesi
            ? collect([$selectedSesi])
            : ($sesiList->isNotEmpty() ? $sesiList : $allSesiPondok);

        $evaluatedSesiIds = $evaluatedSesis->pluck('id')->all();

        // 3. Data Master Kamar, Kelas Pengajian, & Perangkat ESP32
        $kamarModels = Kamar::active()->orderBy('urutan')->orderBy('nama_kamar')->get();
        $kamarList = $kamarModels->pluck('nama_kamar')->filter()->unique()->values();
        if ($kamarList->isEmpty()) {
            $kamarList = Siswa::where('status', 'aktif')
                ->whereNotNull('kamar')
                ->where('kamar', '!=', '')
                ->where('kamar', '!=', '-')
                ->distinct()
                ->orderBy('kamar')
                ->pluck('kamar')
                ->values();
        }

        $kelasPengajianList = MasterKelasPengajian::orderBy('id')->get();
        $deviceMap = MasterDevice::pluck('nama_lokasi', 'device_id')->toArray();

        // 4. Ambil Santri Aktif & Filter Target Sasaran
        $allActiveSantri = Siswa::with(['pendaftaran', 'kelasPengajian'])
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->get();

        $targetSantris = $allActiveSantri->filter(function (Siswa $siswa) use (
            $selectedSesi,
            $evaluatedSesis,
            $kategori,
            $filterKamar,
            $filterKelasPengajianId,
            $filterGender,
            $search
        ) {
            // A. Jika sesi spesifik dipilih, santri harus masuk dalam target sesi tersebut
            if ($selectedSesi) {
                if (!self::matchesSesiTarget($siswa, $selectedSesi)) {
                    return false;
                }
            } elseif ($kategori !== 'semua' && $evaluatedSesis->isNotEmpty()) {
                // Jika kategori dipilih (misal: Pengajian) tanpa sesi spesifik, santri harus cocok dengan minimal 1 sesi di kategori tsb
                $matchesAnyInCat = $evaluatedSesis->contains(function (MasterSesi $s) use ($siswa) {
                    return self::matchesSesiTarget($siswa, $s);
                });
                if (!$matchesAnyInCat) {
                    return false;
                }
            }

            // B. Filter Kamar dari dropdown UI
            if ($filterKamar !== '') {
                if (!self::matchesKamarFilter((string) $siswa->kamar, $filterKamar)) {
                    return false;
                }
            }

            // C. Filter Kelas Pengajian dari dropdown UI
            if ($filterKelasPengajianId !== '') {
                if (is_numeric($filterKelasPengajianId)) {
                    if ((int) $siswa->kelas_pengajian_id !== (int) $filterKelasPengajianId) {
                        return false;
                    }
                } else {
                    if (!self::matchesKelasPengajianFilter($siswa, $filterKelasPengajianId)) {
                        return false;
                    }
                }
            }

            // D. Filter Gender dari dropdown UI
            if ($filterGender !== '') {
                if (self::normalizeGender($siswa) !== $filterGender) {
                    return false;
                }
            }

            // E. Filter Search (Nama Santri / NISN / NIK / UID)
            if ($search !== '') {
                $needle = mb_strtolower($search);
                $nama = mb_strtolower((string) $siswa->nama_lengkap);
                $nisn = mb_strtolower((string) ($siswa->pendaftaran?->nisn ?? $siswa->nisn ?? ''));
                $nik = mb_strtolower((string) ($siswa->pendaftaran?->nik ?? ''));
                $uid = mb_strtolower((string) ($siswa->rfid_uid ?? ''));

                if (
                    !str_contains($nama, $needle) &&
                    !str_contains($nisn, $needle) &&
                    !str_contains($nik, $needle) &&
                    !str_contains($uid, $needle)
                ) {
                    return false;
                }
            }

            return true;
        })->values();

        $targetSantriIds = $targetSantris->pluck('id')->all();

        // 5. Evaluasi Rekap Harian (pada $tanggal)
        $dailyPresensis = collect();
        if (!empty($targetSantriIds)) {
            $presensiQuery = Presensi::with(['sesi', 'device'])
                ->where('tipe_entitas', 'santri')
                ->whereIn('entitas_id', $targetSantriIds)
                ->whereDate('waktu_tap', $tanggal);

            if (!empty($evaluatedSesiIds)) {
                $presensiQuery->whereIn('sesi_id', $evaluatedSesiIds);
            }

            $dailyPresensis = $presensiQuery
                ->orderBy('waktu_tap', 'asc')
                ->get()
                ->groupBy('entitas_id');
        }

        // Ambil data Perizinan pada $tanggal
        $dailySakits = PerizinanSakit::where(function ($q) use ($tanggal) {
                $q->whereDate('tanggal_sakit', $tanggal)
                  ->orWhere(function ($q2) use ($tanggal) {
                      $q2->whereDate('tanggal_sakit', '<=', $tanggal)
                         ->where('status', 'sakit');
                  });
            })
            ->get();

        $dailySambangs = PerizinanSambang::whereDate('tanggal_mulai', '<=', $tanggal)
            ->whereDate('tanggal_kembali', '>=', $tanggal)
            ->get();

        $stats = [
            'total_target' => $targetSantris->count(),
            'hadir'        => 0,
            'terlambat'    => 0,
            'izin_sakit'   => 0,
            'izin'         => 0,
            'sakit'        => 0,
            'alfa'         => 0,
        ];

        $dailyRows = [];
        foreach ($targetSantris as $siswa) {
            $taps = $dailyPresensis->get($siswa->id, collect());
            $tap = $taps->first();

            $eval = $this->evaluateSantriStatusOnDate(
                $siswa,
                $tanggal,
                $tap,
                $selectedSesi,
                $dailySakits,
                $dailySambangs,
                $deviceMap
            );

            // Akumulasi kartu statistik ringkasan (sebelum disaring dropdown status tabel)
            if ($eval['status'] === 'hadir') {
                $stats['hadir']++;
            } elseif ($eval['status'] === 'terlambat') {
                $stats['terlambat']++;
            } elseif ($eval['status'] === 'izin') {
                $stats['izin']++;
                $stats['izin_sakit']++;
            } elseif ($eval['status'] === 'sakit') {
                $stats['sakit']++;
                $stats['izin_sakit']++;
            } else {
                $stats['alfa']++;
            }

            // Filter baris tabel jika pengguna memilih filter status tertentu
            if ($filterStatus !== '') {
                if ($filterStatus === 'izin_sakit' && !in_array($eval['status'], ['izin', 'sakit'], true)) {
                    continue;
                } elseif ($filterStatus !== 'izin_sakit' && $eval['status'] !== $filterStatus) {
                    continue;
                }
            }

            $dailyRows[] = $eval;
        }

        // 6. Evaluasi Matriks Bulanan (Tanggal 1 s/d akhir bulan $bulan-$tahun)
        $startOfMonth = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
        $endOfMonth = (clone $startOfMonth)->endOfMonth();
        $daysInMonth = $startOfMonth->daysInMonth;
        $todayStr = Carbon::today()->toDateString();

        $monthlyPresensisBySantriDate = [];
        $activeDatesInMonth = [];

        if (!empty($targetSantriIds)) {
            $mPresensiQuery = Presensi::with(['sesi', 'device'])
                ->where('tipe_entitas', 'santri')
                ->whereIn('entitas_id', $targetSantriIds)
                ->whereBetween('waktu_tap', [
                    $startOfMonth->toDateTimeString(),
                    $endOfMonth->toDateTimeString(),
                ]);

            if (!empty($evaluatedSesiIds)) {
                $mPresensiQuery->whereIn('sesi_id', $evaluatedSesiIds);
            }

            $mPresensis = $mPresensiQuery->orderBy('waktu_tap', 'asc')->get();
            foreach ($mPresensis as $mp) {
                $dStr = $mp->waktu_tap->toDateString();
                $activeDatesInMonth[$dStr] = true;
                if (!isset($monthlyPresensisBySantriDate[$mp->entitas_id][$dStr])) {
                    $monthlyPresensisBySantriDate[$mp->entitas_id][$dStr] = $mp;
                }
            }
        }

        $monthlySakits = PerizinanSakit::whereDate('tanggal_sakit', '<=', $endOfMonth->toDateString())
            ->where(function ($q) use ($startOfMonth) {
                $q->whereDate('tanggal_sakit', '>=', $startOfMonth->toDateString())
                  ->orWhere('status', 'sakit');
            })
            ->get();

        $monthlySambangs = PerizinanSambang::whereDate('tanggal_mulai', '<=', $endOfMonth->toDateString())
            ->whereDate('tanggal_kembali', '>=', $startOfMonth->toDateString())
            ->get();

        $matriksRows = [];
        foreach ($targetSantris as $siswa) {
            $daysData = [];
            $totalH = 0;
            $totalT = 0;
            $totalIS = 0;
            $totalA = 0;
            $totalEfektif = 0;

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $curDateStr = sprintf('%04d-%02d-%02d', $tahun, $bulan, $d);
                $tapOnDay = $monthlyPresensisBySantriDate[$siswa->id][$curDateStr] ?? null;

                $hasIzinOnDay = $this->findSantriIzinOnDate($siswa, $curDateStr, $monthlySakits, $monthlySambangs) !== null;
                $shouldEvaluateDay = ($curDateStr <= $todayStr) || ($tapOnDay !== null) || $hasIzinOnDay || isset($activeDatesInMonth[$curDateStr]);

                if (!$shouldEvaluateDay) {
                    $daysData[$d] = [
                        'date'   => $curDateStr,
                        'status' => 'belum',
                        'code'   => '-',
                        'label'  => 'Belum Berlangsung',
                        'jam'    => null,
                    ];
                    continue;
                }

                $dayEval = $this->evaluateSantriStatusOnDate(
                    $siswa,
                    $curDateStr,
                    $tapOnDay,
                    $selectedSesi,
                    $monthlySakits,
                    $monthlySambangs,
                    $deviceMap
                );

                $totalEfektif++;
                if ($dayEval['status'] === 'hadir') {
                    $totalH++;
                } elseif ($dayEval['status'] === 'terlambat') {
                    $totalT++;
                } elseif (in_array($dayEval['status'], ['izin', 'sakit'], true)) {
                    $totalIS++;
                } else {
                    $totalA++;
                }

                $daysData[$d] = [
                    'date'   => $curDateStr,
                    'status' => $dayEval['status'],
                    'code'   => $dayEval['matrix_code'],
                    'label'  => $dayEval['status_label'],
                    'jam'    => $dayEval['jam_tap'],
                ];
            }

            $hadirCount = $totalH + $totalT;
            $persentase = $totalEfektif > 0
                ? round(($hadirCount / $totalEfektif) * 100, 1)
                : 0.0;

            $genderNorm = self::normalizeGender($siswa);
            $nisn = $siswa->pendaftaran?->nisn ?: ($siswa->nisn ?: '-');

            $matriksRows[] = [
                'siswa_id'        => $siswa->id,
                'nisn'            => $nisn,
                'nama_lengkap'    => $siswa->nama_lengkap,
                'gender_code'     => $genderNorm ?: '-',
                'gender_label'    => $genderNorm === 'P' ? 'Putri (P)' : ($genderNorm === 'L' ? 'Putra (L)' : '-'),
                'kamar'           => $siswa->kamar ?: '-',
                'kelas_pengajian' => $siswa->kelasPengajian?->nama_kelas ?: 'Belum Diplot',
                'days'            => $daysData,
                'total_h'         => $totalH,
                'total_t'         => $totalT,
                'total_is'        => $totalIS,
                'total_a'         => $totalA,
                'total_efektif'   => $totalEfektif,
                'persentase'      => $persentase,
            ];
        }

        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $namaBulan = $namaBulanList[$bulan] ?? '';

        return [
            'kategori'              => $kategori,
            'sesiId'                => $sesiId,
            'selectedSesi'          => $selectedSesi,
            'allSesiPondok'         => $allSesiPondok,
            'sesiList'              => $sesiList,
            'kamarList'             => $kamarList,
            'kelasPengajianList'    => $kelasPengajianList,
            'filterKamar'           => $filterKamar,
            'filterKelasPengajianId'=> $filterKelasPengajianId,
            'filterGender'          => $filterGender,
            'filterStatus'          => $filterStatus,
            'search'                => $search,
            'mode'                  => $mode,
            'tanggal'               => $tanggal,
            'tanggalCarbon'         => $tanggalCarbon,
            'bulan'                 => $bulan,
            'tahun'                 => $tahun,
            'namaBulan'             => $namaBulan,
            'namaBulanList'         => $namaBulanList,
            'daysInMonth'           => $daysInMonth,
            'stats'                 => $stats,
            'dailyRows'             => $dailyRows,
            'matriksRows'           => $matriksRows,
        ];
    }

    /**
     * Evaluasi status kehadiran 1 santri pada 1 tanggal.
     */
    private function evaluateSantriStatusOnDate(
        Siswa $siswa,
        string $dateStr,
        ?Presensi $tap,
        ?MasterSesi $selectedSesi,
        Collection $sakits,
        Collection $sambangs,
        array $deviceMap
    ): array {
        $genderNorm = self::normalizeGender($siswa);
        $nisn = $siswa->pendaftaran?->nisn ?: ($siswa->nisn ?: '-');
        $kamar = $siswa->kamar ?: '-';
        $kelasPengajian = $siswa->kelasPengajian?->nama_kelas ?: 'Belum Diplot';

        if ($tap) {
            $sesiObj = $tap->sesi ?? $selectedSesi;
            $isLate = ($tap->status_kehadiran === 'terlambat');

            if (!$isLate && $sesiObj && !empty($sesiObj->jam_mulai)) {
                try {
                    $jamMulaiDt = Carbon::parse($dateStr . ' ' . $sesiObj->jam_mulai);
                    $batasToleransiDt = (clone $jamMulaiDt)->addMinutes((int) ($sesiObj->toleransi_menit ?? 0));
                    if ($tap->waktu_tap && $tap->waktu_tap->gt($batasToleransiDt)) {
                        $isLate = true;
                    }
                } catch (\Throwable $e) {
                    // Abaikan jika format jam tidak standar
                }
            }

            $status = $isLate ? 'terlambat' : 'hadir';
            $statusLabel = $isLate ? 'Terlambat' : 'Hadir';
            $matrixCode = $isLate ? 'T' : 'H';

            $devId = (string) ($tap->device_id ?? '');
            $lokasi = $deviceMap[$devId] ?? ($tap->device?->nama_lokasi ?? '');
            $deviceLabel = $devId ? ($lokasi ? "{$devId} ({$lokasi})" : $devId) : '-';

            return [
                'siswa_id'        => $siswa->id,
                'nisn'            => $nisn,
                'nama_lengkap'    => $siswa->nama_lengkap,
                'gender_code'     => $genderNorm ?: '-',
                'gender_label'    => $genderNorm === 'P' ? 'Putri (P)' : ($genderNorm === 'L' ? 'Putra (L)' : '-'),
                'kamar'           => $kamar,
                'kelas_pengajian' => $kelasPengajian,
                'sesi_nama'       => $sesiObj?->nama_sesi ?? '-',
                'jam_tap'         => $tap->waktu_tap ? $tap->waktu_tap->format('H:i:s') : null,
                'device_id'       => $devId ?: null,
                'device_label'    => $deviceLabel,
                'status'          => $status,
                'status_label'    => $statusLabel,
                'matrix_code'     => $matrixCode,
                'keterangan'      => $isLate ? 'Melewati batas toleransi' : 'Tepat waktu',
            ];
        }

        // Cek apakah santri memiliki izin / sakit yang valid pada tanggal tersebut
        $izinInfo = $this->findSantriIzinOnDate($siswa, $dateStr, $sakits, $sambangs);
        if ($izinInfo !== null) {
            return [
                'siswa_id'        => $siswa->id,
                'nisn'            => $nisn,
                'nama_lengkap'    => $siswa->nama_lengkap,
                'gender_code'     => $genderNorm ?: '-',
                'gender_label'    => $genderNorm === 'P' ? 'Putri (P)' : ($genderNorm === 'L' ? 'Putra (L)' : '-'),
                'kamar'           => $kamar,
                'kelas_pengajian' => $kelasPengajian,
                'sesi_nama'       => $selectedSesi?->nama_sesi ?? 'Semua Sesi',
                'jam_tap'         => null,
                'device_id'       => null,
                'device_label'    => '-',
                'status'          => $izinInfo['status'],      // 'izin' atau 'sakit'
                'status_label'    => $izinInfo['status_label'],// 'Izin' atau 'Sakit'
                'matrix_code'     => $izinInfo['matrix_code'], // 'I' atau 'S'
                'keterangan'      => $izinInfo['keterangan'],
            ];
        }

        // Tidak tap dan tidak memiliki surat izin/sakit -> Alfa
        return [
            'siswa_id'        => $siswa->id,
            'nisn'            => $nisn,
            'nama_lengkap'    => $siswa->nama_lengkap,
            'gender_code'     => $genderNorm ?: '-',
            'gender_label'    => $genderNorm === 'P' ? 'Putri (P)' : ($genderNorm === 'L' ? 'Putra (L)' : '-'),
            'kamar'           => $kamar,
            'kelas_pengajian' => $kelasPengajian,
            'sesi_nama'       => $selectedSesi?->nama_sesi ?? 'Semua Sesi',
            'jam_tap'         => null,
            'device_id'       => null,
            'device_label'    => '-',
            'status'          => 'alfa',
            'status_label'    => 'Alfa',
            'matrix_code'     => 'A',
            'keterangan'      => 'Tidak hadir / belum tap kartu',
        ];
    }

    /**
     * Cek data PerizinanSakit & PerizinanSambang santri pada tanggal tertentu.
     */
    private function findSantriIzinOnDate(
        Siswa $siswa,
        string $dateStr,
        Collection $sakits,
        Collection $sambangs
    ): ?array {
        // 1. Cek Perizinan Sakit
        $sakitMatch = $sakits->first(function (PerizinanSakit $sk) use ($siswa, $dateStr) {
            $sameSiswa = ($sk->siswa_id && (int) $sk->siswa_id === (int) $siswa->id)
                || (!$sk->siswa_id && mb_strtolower(trim((string) $sk->nama)) === mb_strtolower(trim((string) $siswa->nama_lengkap)));
            if (!$sameSiswa || !$sk->tanggal_sakit) {
                return false;
            }
            $tglSakit = $sk->tanggal_sakit->toDateString();
            if ($tglSakit === $dateStr) {
                return true;
            }
            return $tglSakit <= $dateStr && $sk->status === 'sakit';
        });

        if ($sakitMatch) {
            return [
                'status'       => 'sakit',
                'status_label' => 'Sakit',
                'matrix_code'  => 'S',
                'keterangan'   => 'Sakit: ' . ($sakitMatch->sakit ?: ($sakitMatch->keterangan ?: 'Rawat/Istirahat')),
            ];
        }

        // 2. Cek Perizinan Sambang / Pulang
        $sambangMatch = $sambangs->first(function (PerizinanSambang $sb) use ($siswa, $dateStr) {
            $sameSiswa = ($sb->siswa_id && (int) $sb->siswa_id === (int) $siswa->id)
                || (!$sb->siswa_id && mb_strtolower(trim((string) $sb->nama)) === mb_strtolower(trim((string) $siswa->nama_lengkap)));
            if (!$sameSiswa || !$sb->tanggal_mulai || !$sb->tanggal_kembali) {
                return false;
            }
            $mulai = $sb->tanggal_mulai->toDateString();
            $kembali = $sb->tanggal_kembali->toDateString();
            return $dateStr >= $mulai && $dateStr <= $kembali;
        });

        if ($sambangMatch) {
            return [
                'status'       => 'izin',
                'status_label' => 'Izin',
                'matrix_code'  => 'I',
                'keterangan'   => 'Izin: ' . ($sambangMatch->keterangan ?: 'Sambang / Pulang'),
            ];
        }

        return null;
    }

    /**
     * Memeriksa apakah seorang santri termasuk sasaran wajib hadir pada sebuah MasterSesi.
     */
    public static function matchesSesiTarget(Siswa $siswa, MasterSesi $sesi): bool
    {
        $allocations = $sesi->relationLoaded('allocations') ? $sesi->allocations : $sesi->allocations()->get();

        if ($allocations->isNotEmpty()) {
            foreach ($allocations as $alloc) {
                if (self::matchesSingleAllocation(
                    $siswa,
                    (string) ($alloc->target_kategori ?? 'semua'),
                    (string) ($alloc->target_filter ?? ''),
                    (string) ($alloc->target_gender ?? 'ALL')
                )) {
                    return true;
                }
            }
            return false;
        }

        // Fallback ke kolom legacy pada master_sesis
        return self::matchesSingleAllocation(
            $siswa,
            (string) ($sesi->target_kategori ?? 'semua_santri'),
            (string) ($sesi->target_filter ?? ''),
            'ALL'
        );
    }

    /**
     * Pencocokan santri terhadap 1 baris aturan alokasi target.
     */
    private static function matchesSingleAllocation(
        Siswa $siswa,
        string $targetKategori,
        string $targetFilter,
        string $targetGender = 'ALL'
    ): bool {
        $kat = strtolower(trim($targetKategori));
        $filter = trim($targetFilter);
        $genderReq = strtoupper(trim($targetGender));

        // 1. Cek Gender Alokasi
        if (in_array($genderReq, ['L', 'P'], true)) {
            if (self::normalizeGender($siswa) !== $genderReq) {
                return false;
            }
        }

        // Cek juga apabila ada tag [Gender: Putra] / [Gender: Putri] di dalam string filter
        if (stripos($filter, '[gender: putra]') !== false && self::normalizeGender($siswa) !== 'L') {
            return false;
        }
        if (stripos($filter, '[gender: putri]') !== false && self::normalizeGender($siswa) !== 'P') {
            return false;
        }

        $cleanFilter = trim((string) preg_replace('/\[gender:\s*(putra|putri)\]/i', '', $filter));

        // 2. Cek Kategori Target
        if (in_array($kat, ['', 'semua', 'semua_santri', 'gender', 'all'], true)) {
            return true;
        }

        if (in_array($kat, ['kamar', 'kamar_khusus'], true)) {
            if ($cleanFilter === '' || in_array(strtolower($cleanFilter), ['semua', 'all', 'semua_santri'], true)) {
                return true;
            }
            return self::matchesKamarFilter((string) $siswa->kamar, $cleanFilter);
        }

        if (in_array($kat, ['kelas_pengajian', 'pengajian'], true)) {
            if ($cleanFilter === '' || in_array(strtolower($cleanFilter), ['semua', 'all'], true)) {
                return true;
            }
            return self::matchesKelasPengajianFilter($siswa, $cleanFilter);
        }

        if (in_array($kat, ['kelas_sekolah', 'sekolah', 'jenjang'], true)) {
            if ($cleanFilter === '' || in_array(strtolower($cleanFilter), ['semua', 'all'], true)) {
                return true;
            }
            return self::matchesSekolahFilter($siswa, $cleanFilter);
        }

        return true;
    }

    /**
     * Pencocokan kamar santri terhadap filter kamar (bisa multi-kamar dipisah koma).
     */
    public static function matchesKamarFilter(string $santriKamar, string $filterKamar): bool
    {
        $normSantri = self::normalizeRoomName($santriKamar);
        if ($normSantri === '') {
            return false;
        }

        $targets = array_filter(array_map('trim', explode(',', $filterKamar)));
        foreach ($targets as $t) {
            $normTarget = self::normalizeRoomName($t);
            if ($normTarget === '') {
                continue;
            }
            if ($normSantri === $normTarget || str_contains($normSantri, $normTarget) || str_contains($normTarget, $normSantri)) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeRoomName(string $name): string
    {
        $clean = mb_strtolower(trim($name));
        if (str_starts_with($clean, 'kamar ')) {
            $clean = trim(mb_substr($clean, 6));
        }
        return preg_replace('/\s+/', ' ', $clean);
    }

    /**
     * Pencocokan kelas pengajian santri terhadap filter (nama_kelas, kode_kelas, atau ID).
     */
    public static function matchesKelasPengajianFilter(Siswa $siswa, string $filterKelas): bool
    {
        $namaKelas = mb_strtolower(trim((string) ($siswa->kelasPengajian?->nama_kelas ?? '')));
        $kodeKelas = mb_strtolower(trim((string) ($siswa->kelasPengajian?->kode_kelas ?? '')));
        $idKelas = (string) ($siswa->kelas_pengajian_id ?? '');

        if ($namaKelas === '' && $kodeKelas === '' && $idKelas === '') {
            return false;
        }

        $targets = array_filter(array_map('trim', explode(',', $filterKelas)));
        foreach ($targets as $t) {
            $tLower = mb_strtolower($t);
            if ($tLower === '') {
                continue;
            }
            if (
                $tLower === $idKelas ||
                ($namaKelas !== '' && ($namaKelas === $tLower || str_contains($namaKelas, $tLower) || str_contains($tLower, $namaKelas))) ||
                ($kodeKelas !== '' && ($kodeKelas === $tLower || str_contains($kodeKelas, $tLower)))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Pencocokan jenjang/kelas sekolah santri terhadap filter (misal SMP, SMA).
     */
    public static function matchesSekolahFilter(Siswa $siswa, string $filterSekolah): bool
    {
        $jenjang = mb_strtolower(trim((string) ($siswa->jenjang ?: ($siswa->pendaftaran?->jenjang ?? ''))));
        $kelas = mb_strtolower(trim((string) ($siswa->kelas ?? '')));

        $targets = array_filter(array_map('trim', explode(',', $filterSekolah)));
        foreach ($targets as $t) {
            $tLower = mb_strtolower($t);
            if ($tLower === '') {
                continue;
            }
            if (
                ($jenjang !== '' && ($jenjang === $tLower || str_contains($jenjang, $tLower) || str_contains($tLower, $jenjang))) ||
                ($kelas !== '' && ($kelas === $tLower || str_contains($kelas, $tLower)))
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalisasi jenis kelamin santri menjadi 'L' atau 'P'.
     */
    public static function normalizeGender(Siswa $siswa): string
    {
        $raw = trim((string) ($siswa->jenis_kelamin ?? ''));
        $upper = mb_strtoupper($raw);

        if (in_array($upper, ['L', 'LAKI-LAKI', 'LAKI - LAKI', 'PUTRA', 'IKHWAN', 'MALE', 'BANIN'], true)) {
            return 'L';
        }
        if (in_array($upper, ['P', 'PEREMPUAN', 'PUTRI', 'AKHWAT', 'FEMALE', 'BANAT'], true)) {
            return 'P';
        }

        return '';
    }
}

