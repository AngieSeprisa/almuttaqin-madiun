<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\Kamar;
use App\Models\MasterKelasPengajian;
use App\Models\MasterSesi;
use App\Models\PerizinanSakit;
use App\Models\PerizinanSambang;
use App\Models\Presensi;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RekapPondokController extends Controller
{
    /**
     * Konfigurasi standar 5 waktu sholat.
     */
    public const SHOLAT_SLOTS = [
        'subuh' => [
            'key'       => 'subuh',
            'label'     => 'SUBUH',
            'jam_mulai' => '04:00',
            'jam_akhir' => '06:30',
        ],
        'dzuhur' => [
            'key'       => 'dzuhur',
            'label'     => 'DZUHUR',
            'jam_mulai' => '11:30',
            'jam_akhir' => '13:30',
        ],
        'ashar' => [
            'key'       => 'ashar',
            'label'     => 'ASAR',
            'jam_mulai' => '14:45',
            'jam_akhir' => '17:00',
        ],
        'maghrib' => [
            'key'       => 'maghrib',
            'label'     => 'MAGHRIB',
            'jam_mulai' => '17:30',
            'jam_akhir' => '18:45',
        ],
        'isya' => [
            'key'       => 'isya',
            'label'     => 'ISYA',
            'jam_mulai' => '18:45',
            'jam_akhir' => '21:30',
        ],
    ];

    /**
     * Bersihkan nama siswa dari embel-embel jenjang sekolah seperti (MTK-SMP), (SMP), dll.
     */
    public static function cleanStudentName(?string $name): string
    {
        if (!$name) {
            return '';
        }
        // Hapus keterangan kurung di akhir seperti (MTK-SMP), (MTK-SMA), (SMP), (Reguler), dsb.
        $cleaned = preg_replace('/\s*\([^)]*\)\s*$/', '', $name);

        return trim((string) $cleaned);
    }

    /**
     * Halaman utama Rekap Presensi Pondok (Sholat & Pengajian) dengan UI Filament 3.x.
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

        $rentang = $data['rentang'];
        $tanggal = $data['tanggal'];
        $fileName = sprintf('rekap_presensi_pondok_%s_%s.csv', $rentang, str_replace('-', '', $tanggal));

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

            fputcsv($handle, [
                'No',
                'NISN',
                'Nama',
                'Kamar',
                'Kelas',
                'Jenjang',
                'Hadir',
                'Telat',
                'Izin',
                'Alfa',
            ]);

            foreach ($data['rows'] as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r['nisn'],
                    $r['nama_lengkap'],
                    $r['kamar'],
                    $r['kelas'],
                    $r['jenjang'],
                    $r['total_h'],
                    $r['total_t'],
                    $r['total_i'] + $r['total_s'],
                    $r['total_a'],
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Membangun data presensi pondok (sholat & pengajian) sesuai filter lengkap.
     */
    public function buildRekapData(Request $request): array
    {
        // 1. Parameter Filter
        $kategori = strtolower(trim((string) $request->get('kategori', 'semua')));
        if (!in_array($kategori, ['semua', 'sholat', 'pengajian'], true)) {
            $kategori = 'semua';
        }

        $filterSesiId = trim((string) $request->get('sesi_id', $request->get('sesi_sholat', 'semua')));
        $filterKelas = trim((string) $request->get('kelas', 'semua'));
        $filterKamar = trim((string) $request->get('kamar', ''));
        $filterKelasPengajian = trim((string) $request->get('kelas_pengajian_id', 'semua'));
        $filterGender = strtoupper(trim((string) $request->get('gender', 'semua')));
        $filterJenjang = strtolower(trim((string) $request->get('jenjang', 'semua')));
        $search = trim((string) $request->get('search', ''));

        $rentang = strtolower(trim((string) $request->get('rentang', $request->get('mode', 'harian'))));
        if (!in_array($rentang, ['harian', 'mingguan', 'bulanan'], true)) {
            $rentang = 'harian';
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

        $perPage = (int) $request->get('per_page', 10);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }
        $currentPage = max(1, (int) $request->get('page', 1));

        // 2. Rentang Tanggal Evaluasi
        if ($rentang === 'bulanan') {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            $endDate = (clone $startDate)->endOfMonth();
        } elseif ($rentang === 'mingguan') {
            $startDate = (clone $tanggalCarbon)->startOfWeek(Carbon::MONDAY);
            $endDate = (clone $startDate)->addDays(6);
        } else {
            $startDate = clone $tanggalCarbon;
            $endDate = clone $tanggalCarbon;
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();
        $todayStr = Carbon::today()->toDateString();

        $evalDates = [];
        $currDate = clone $startDate;
        while ($currDate->lte($endDate)) {
            $evalDates[] = $currDate->toDateString();
            $currDate->addDay();
        }

        // 3. Dropdown Options List
        // A. Kategori List
        $kategoriList = [
            'semua'     => 'Semua Kategori',
            'sholat'    => 'Sholat',
            'pengajian' => 'Pengajian',
        ];

        // B. Master Sesi List
        $masterSesiQuery = MasterSesi::with(['allocations']);
        if ($kategori === 'sholat') {
            $masterSesiQuery->where(function ($q) {
                $q->where('kategori', 'sholat')
                  ->orWhere('kode_sesi', 'LIKE', '%SHOLAT%')
                  ->orWhere('nama_sesi', 'LIKE', '%sholat%')
                  ->orWhere('kode_sesi', 'LIKE', '%SUBUH%')
                  ->orWhere('kode_sesi', 'LIKE', '%DZUHUR%')
                  ->orWhere('kode_sesi', 'LIKE', '%ASHAR%')
                  ->orWhere('kode_sesi', 'LIKE', '%MAGHRIB%')
                  ->orWhere('kode_sesi', 'LIKE', '%ISYA%');
            });
        } elseif ($kategori === 'pengajian') {
            $masterSesiQuery->where(function ($q) {
                $q->where('kategori', 'pengajian')
                  ->orWhere('kode_sesi', 'LIKE', '%PENGAJIAN%')
                  ->orWhere('nama_sesi', 'LIKE', '%pengajian%');
            });
        } else {
            $masterSesiQuery->whereIn('kategori', ['sholat', 'pengajian']);
        }
        $dbSesiList = $masterSesiQuery->orderBy('jam_mulai')->get();

        // C. Sesi Spesifik Dropdown Options
        $sesiOptions = [];
        $sesiOptions['semua'] = 'Semua Sesi';

        if ($kategori === 'semua' || $kategori === 'sholat') {
            foreach (self::SHOLAT_SLOTS as $slotKey => $slotMeta) {
                $sesiOptions[$slotKey] = 'Sholat: ' . $slotMeta['label'];
            }
        }

        foreach ($dbSesiList as $s) {
            $katLabel = ucfirst($s->kategori ?? 'sesi');
            $sesiOptions[(string) $s->id] = "{$katLabel}: {$s->nama_sesi}";
        }

        // Cek jika filterSesiId merujuk ke MasterSesi spesifik
        $selectedSesiModel = null;
        if ($filterSesiId !== 'semua' && is_numeric($filterSesiId)) {
            $selectedSesiModel = MasterSesi::find((int) $filterSesiId);
        }

        // D. Daftar Kelas Sekolah
        $kelasList = Classroom::where('is_active', true)
            ->orderBy('urutan')
            ->orderBy('nama_kelas')
            ->pluck('nama_kelas')
            ->filter()
            ->unique()
            ->values();

        if ($kelasList->isEmpty()) {
            $kelasList = Siswa::whereNotNull('kelas')
                ->where('kelas', '!=', '')
                ->where('kelas', '!=', '-')
                ->distinct()
                ->orderBy('kelas')
                ->pluck('kelas')
                ->values();
        }

        // E. Daftar Kamar Asrama
        $kamarModels = Kamar::active()->orderBy('urutan')->orderBy('nama_kamar')->get();
        $kamarList = $kamarModels->pluck('nama_kamar')->filter()->unique()->values();
        if ($kamarList->isEmpty()) {
            $kamarList = Siswa::whereNotNull('kamar')
                ->where('kamar', '!=', '')
                ->where('kamar', '!=', '-')
                ->distinct()
                ->orderBy('kamar')
                ->pluck('kamar')
                ->values();
        }

        // F. Daftar Kelas Pengajian
        $kelasPengajianList = MasterKelasPengajian::orderBy('nama_kelas')->get();

        // G. Gender List
        $genderList = [
            'semua' => 'Semua Gender',
            'L'     => 'Putra (L)',
            'P'     => 'Putri (P)',
        ];

        // 4. Ambil Santri Aktif & Terapkan Filter Target
        $allActiveSantri = Siswa::with(['pendaftaran', 'kelasPengajian'])
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->get();

        $filteredSantris = $allActiveSantri->filter(function (Siswa $siswa) use (
            $filterKelas,
            $filterKamar,
            $filterKelasPengajian,
            $filterGender,
            $filterJenjang,
            $search
        ) {
            // Filter Kelas Sekolah
            if ($filterKelas !== 'semua' && $filterKelas !== '') {
                $kls = trim((string) $siswa->kelas);
                if (strcasecmp($kls, $filterKelas) !== 0) {
                    return false;
                }
            }

            // Filter Kamar
            if ($filterKamar !== '') {
                if (stripos((string) $siswa->kamar, $filterKamar) === false) {
                    return false;
                }
            }

            // Filter Kelas Pengajian
            if ($filterKelasPengajian !== 'semua' && $filterKelasPengajian !== '') {
                $kpId = (string) $siswa->kelas_pengajian_id;
                $kpKode = $siswa->kelasPengajian?->kode_kelas ?? '';
                if ($kpId !== $filterKelasPengajian && $kpKode !== $filterKelasPengajian) {
                    return false;
                }
            }

            // Filter Gender
            if ($filterGender !== 'SEMUA' && $filterGender !== '') {
                $g = strtoupper((string) ($siswa->gender ?: $siswa->jenis_kelamin ?: ''));
                $isMale = str_contains($g, 'LAKI') || $g === 'L';
                $isFemale = str_contains($g, 'PEREMPUAN') || $g === 'P' || str_contains($g, 'PUTRI') || str_contains($g, 'BANAT');

                if ($filterGender === 'L' && !$isMale) {
                    return false;
                }
                if ($filterGender === 'P' && !$isFemale) {
                    return false;
                }
            }

            // Filter Jenjang (SMP / SMA / Reguler)
            if ($filterJenjang !== 'semua' && $filterJenjang !== '') {
                $jenjangSantri = strtolower(trim((string) ($siswa->jenjang ?: ($siswa->pendaftaran?->jenjang ?: ''))));
                $kamarSantri = strtolower(trim((string) $siswa->kamar));

                if ($filterJenjang === 'smp' && !str_contains($jenjangSantri, 'smp')) {
                    return false;
                }
                if ($filterJenjang === 'sma' && !str_contains($jenjangSantri, 'sma')) {
                    return false;
                }
                if ($filterJenjang === 'reguler') {
                    $isReguler = str_contains($jenjangSantri, 'reguler')
                        || str_contains($kamarSantri, 'reguler')
                        || (!str_contains($jenjangSantri, 'smp') && !str_contains($jenjangSantri, 'sma'));
                    if (!$isReguler) {
                        return false;
                    }
                }
            }

            // Filter Pencarian Live (Nama / NISN)
            if ($search !== '') {
                $needle = mb_strtolower($search);
                $nama = mb_strtolower((string) $siswa->nama_lengkap);
                $nisn = mb_strtolower((string) ($siswa->pendaftaran?->nisn ?? $siswa->nisn ?? ''));
                if (!str_contains($nama, $needle) && !str_contains($nisn, $needle)) {
                    return false;
                }
            }

            return true;
        })->values();

        $filteredSantriIds = $filteredSantris->pluck('id')->all();

        // 5. Query Presensi & Perizinan pada Rentang Tanggal
        $presensisBySantri = [];
        if (!empty($filteredSantriIds)) {
            $presensiList = Presensi::with('sesi')
                ->where('tipe_entitas', 'santri')
                ->whereIn('entitas_id', $filteredSantriIds)
                ->whereBetween('waktu_tap', [
                    $startDate->copy()->startOfDay()->toDateTimeString(),
                    $endDate->copy()->endOfDay()->toDateTimeString(),
                ])
                ->orderBy('waktu_tap', 'asc')
                ->get();

            foreach ($presensiList as $p) {
                $dStr = $p->waktu_tap ? $p->waktu_tap->toDateString() : null;
                if (!$dStr) continue;

                $presensisBySantri[$p->entitas_id][$dStr][] = $p;
            }
        }

        // Query Perizinan Sakit & Sambang
        $sakits = PerizinanSakit::whereDate('tanggal_sakit', '<=', $endDateStr)
            ->where(function ($q) use ($startDateStr) {
                $q->whereDate('tanggal_sakit', '>=', $startDateStr)
                  ->orWhere('status', 'sakit');
            })
            ->get();

        $sambangs = PerizinanSambang::whereDate('tanggal_mulai', '<=', $endDateStr)
            ->whereDate('tanggal_kembali', '>=', $startDateStr)
            ->get();

        // 6. Evaluasi Presensi per Santri
        $rows = [];
        $grandTotalH = 0;
        $grandTotalT = 0;
        $grandTotalI = 0;
        $grandTotalS = 0;
        $grandTotalA = 0;

        // Tentukan sesi/slot yang dievaluasi
        $isSlotSholat = array_key_exists($filterSesiId, self::SHOLAT_SLOTS);
        $evaluateSlots = [];

        if ($isSlotSholat) {
            $evaluateSlots[$filterSesiId] = self::SHOLAT_SLOTS[$filterSesiId];
        } elseif ($selectedSesiModel) {
            $evaluateSlots['sesi_' . $selectedSesiModel->id] = [
                'key'       => 'sesi_' . $selectedSesiModel->id,
                'label'     => $selectedSesiModel->nama_sesi,
                'model'     => $selectedSesiModel,
                'jam_mulai' => $selectedSesiModel->jam_mulai,
                'jam_akhir' => $selectedSesiModel->jam_selesai,
            ];
        } elseif ($kategori === 'pengajian') {
            foreach ($dbSesiList as $ps) {
                $evaluateSlots['sesi_' . $ps->id] = [
                    'key'       => 'sesi_' . $ps->id,
                    'label'     => $ps->nama_sesi,
                    'model'     => $ps,
                    'jam_mulai' => $ps->jam_mulai,
                    'jam_akhir' => $ps->jam_selesai,
                ];
            }
        } else {
            // Default 5 waktu sholat
            $evaluateSlots = self::SHOLAT_SLOTS;
        }

        foreach ($filteredSantris as $siswa) {
            $siswaRow = [
                'siswa_id'        => $siswa->id,
                'nisn'            => $siswa->pendaftaran?->nisn ?: ($siswa->nisn ?: '-'),
                'nama_lengkap'    => $siswa->nama_lengkap,
                'nama_bersih'     => self::cleanStudentName($siswa->nama_lengkap),
                'kamar'           => $siswa->kamar ?: '-',
                'kelas'           => $siswa->kelas ?: '-',
                'jenjang'         => $siswa->jenjang ?: ($siswa->pendaftaran?->jenjang ?: 'Reguler'),
                'total_h'         => 0,
                'total_t'         => 0,
                'total_i'         => 0,
                'total_s'         => 0,
                'total_a'         => 0,
                'sholat'          => [],
                'riwayat'         => [],
                'status_per_sesi' => [],
            ];

            // Inisialisasi struktur sholat
            foreach (self::SHOLAT_SLOTS as $slotKey => $slotMeta) {
                $siswaRow['sholat'][$slotKey] = [
                    'key'     => $slotKey,
                    'label'   => $slotMeta['label'],
                    'code'    => '-',
                    'display' => '-',
                    'jam'     => null,
                ];
            }

            foreach ($evalDates as $dStr) {
                $tapsToday = $presensisBySantri[$siswa->id][$dStr] ?? [];
                $izinInfo = $this->findSantriIzin($siswa, $dStr, $sakits, $sambangs);

                foreach ($evaluateSlots as $slotKey => $slotMeta) {
                    $matchedTap = null;

                    // Cocokkan tap dengan slot
                    if (isset($slotMeta['model'])) {
                        // Sesi spesifik dari MasterSesi
                        $targetSesiId = $slotMeta['model']->id;
                        foreach ($tapsToday as $tap) {
                            if ((int) $tap->sesi_id === (int) $targetSesiId) {
                                $matchedTap = $tap;
                                break;
                            }
                        }
                    } else {
                        // Slot sholat standar
                        foreach ($tapsToday as $tap) {
                            $detected = self::detectSlotSholat($tap->sesi, $tap->waktu_tap);
                            if ($detected === $slotKey) {
                                $matchedTap = $tap;
                                break;
                            }
                        }
                    }

                    if ($matchedTap) {
                        $isLate = ($matchedTap->status_kehadiran === 'terlambat');
                        if (!$isLate && $matchedTap->sesi && !empty($matchedTap->sesi->jam_mulai)) {
                            try {
                                $jamMulaiDt = Carbon::parse($dStr . ' ' . $matchedTap->sesi->jam_mulai);
                                $toleransi = (int) ($matchedTap->sesi->toleransi_menit ?? 15);
                                $batasDt = (clone $jamMulaiDt)->addMinutes($toleransi);
                                if ($matchedTap->waktu_tap && $matchedTap->waktu_tap->gt($batasDt)) {
                                    $isLate = true;
                                }
                            } catch (\Throwable $e) {}
                        }

                        $jamTapStr = $matchedTap->waktu_tap ? $matchedTap->waktu_tap->format('H:i:s') : '-';
                        if ($isLate) {
                            $siswaRow['total_t']++;
                            $statusCode = 'T';
                            $statusLabel = 'TERLAMBAT';
                            $statusBadge = 'status-badge-terlambat';
                            $keterangan = "Tap pukul {$jamTapStr} WIB (Melewati Toleransi)";
                        } else {
                            $siswaRow['total_h']++;
                            $statusCode = 'H';
                            $statusLabel = 'HADIR';
                            $statusBadge = 'status-badge-hadir';
                            $keterangan = "Tap pukul {$jamTapStr} WIB (Tepat Waktu)";
                        }

                        if (isset($siswaRow['sholat'][$slotKey])) {
                            $siswaRow['sholat'][$slotKey]['code'] = $statusCode;
                            $siswaRow['sholat'][$slotKey]['display'] = $statusCode;
                            $siswaRow['sholat'][$slotKey]['jam'] = $jamTapStr;
                        }

                        $siswaRow['riwayat'][] = [
                            'tanggal'    => $dStr,
                            'sesi'       => $slotMeta['label'],
                            'status'     => $statusLabel,
                            'badge'      => $statusBadge,
                            'keterangan' => $keterangan,
                        ];

                        $siswaRow['status_per_sesi'][$slotKey] = [
                            'status'    => $isLate ? 'terlambat' : 'hadir',
                            'jam'       => $jamTapStr,
                            'wa_emoji'  => $isLate ? '⚠️ - (telat)' : '✅',
                        ];
                    } elseif ($izinInfo) {
                        if ($izinInfo['status'] === 'sakit') {
                            $siswaRow['total_s']++;
                            $statusCode = 'S';
                            $statusLabel = 'SAKIT';
                            $statusBadge = 'status-badge-sakit';
                            $waEmoji = '✉️ - SAKIT';
                        } else {
                            $siswaRow['total_i']++;
                            $statusCode = 'I';
                            $statusLabel = 'IZIN';
                            $statusBadge = 'status-badge-izin';
                            $waEmoji = '✉️ - IZIN';
                        }

                        if (isset($siswaRow['sholat'][$slotKey])) {
                            $siswaRow['sholat'][$slotKey]['code'] = $statusCode;
                            $siswaRow['sholat'][$slotKey]['display'] = $statusCode;
                        }

                        $siswaRow['riwayat'][] = [
                            'tanggal'    => $dStr,
                            'sesi'       => $slotMeta['label'],
                            'status'     => $statusLabel,
                            'badge'      => $statusBadge,
                            'keterangan' => $izinInfo['label'],
                        ];

                        $siswaRow['status_per_sesi'][$slotKey] = [
                            'status'    => $izinInfo['status'],
                            'jam'       => '-',
                            'wa_emoji'  => $waEmoji,
                        ];
                    } else {
                        // Tidak tap dan tidak ada izin
                        if ($dStr <= $todayStr) {
                            $siswaRow['total_a']++;
                            if (isset($siswaRow['sholat'][$slotKey])) {
                                $siswaRow['sholat'][$slotKey]['code'] = 'A';
                                $siswaRow['sholat'][$slotKey]['display'] = 'A';
                            }

                            $siswaRow['riwayat'][] = [
                                'tanggal'    => $dStr,
                                'sesi'       => $slotMeta['label'],
                                'status'     => 'ALPA',
                                'badge'      => 'status-badge-alpa',
                                'keterangan' => 'Tidak Hadir / Belum Absen',
                            ];

                            $siswaRow['status_per_sesi'][$slotKey] = [
                                'status'    => 'alpa',
                                'jam'       => '-',
                                'wa_emoji'  => '❌ - ALPA',
                            ];
                        } else {
                            // Masa Depan
                            $siswaRow['status_per_sesi'][$slotKey] = [
                                'status'    => 'belum',
                                'jam'       => '-',
                                'wa_emoji'  => '⏳',
                            ];
                        }
                    }
                }
            }

            $grandTotalH += $siswaRow['total_h'];
            $grandTotalT += $siswaRow['total_t'];
            $grandTotalI += $siswaRow['total_i'];
            $grandTotalS += $siswaRow['total_s'];
            $grandTotalA += $siswaRow['total_a'];

            $rows[] = $siswaRow;
        }

        $totalFiltered = count($rows);

        // Pagination
        $paginatedRows = new LengthAwarePaginator(
            array_slice($rows, ($currentPage - 1) * $perPage, $perPage),
            $totalFiltered,
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // 7. Kartu Statistik Ringkasan (Stats Cards)
        $stats = [
            'total_target' => $totalFiltered,
            'hadir'        => $grandTotalH,
            'terlambat'    => $grandTotalT,
            'izin'         => $grandTotalI,
            'sakit'        => $grandTotalS,
            'izin_sakit'   => $grandTotalI + $grandTotalS,
            'alfa'         => $grandTotalA,
        ];

        // 8. Teks Format Laporan WhatsApp
        $waReports = $this->buildWhatsAppReports(
            $rows,
            $tanggalCarbon,
            $filterKelas,
            $filterKamar,
            $filterKelasPengajian,
            $kategori,
            $filterSesiId,
            $selectedSesiModel,
            $evaluateSlots
        );

        // Active filters count untuk badge icon
        $activeFiltersCount = 0;
        if ($kategori !== 'semua') $activeFiltersCount++;
        if ($filterSesiId !== 'semua') $activeFiltersCount++;
        if ($filterKelas !== 'semua') $activeFiltersCount++;
        if ($filterKamar !== '') $activeFiltersCount++;
        if ($filterKelasPengajian !== 'semua') $activeFiltersCount++;
        if ($filterGender !== 'SEMUA') $activeFiltersCount++;
        if ($rentang !== 'harian') $activeFiltersCount++;

        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return [
            'kategori'             => $kategori,
            'kategoriList'         => $kategoriList,
            'sesi_id'              => $filterSesiId,
            'filterSesiSholat'     => $filterSesiId,
            'sesiOptions'          => $sesiOptions,
            'selectedSesi'         => $selectedSesiModel,
            'kelas'                => $filterKelas,
            'kelasList'            => $kelasList,
            'kamar'                => $filterKamar,
            'filterKamar'          => $filterKamar,
            'kamarList'            => $kamarList,
            'kelas_pengajian_id'   => $filterKelasPengajian,
            'kelasPengajianList'   => $kelasPengajianList,
            'gender'               => $filterGender,
            'genderList'           => $genderList,
            'jenjang'              => $filterJenjang,
            'filterJenjang'        => $filterJenjang,
            'rentang'              => $rentang,
            'mode'                 => $rentang,
            'tanggal'              => $tanggal,
            'tanggalCarbon'        => $tanggalCarbon,
            'startDate'            => $startDate,
            'endDate'              => $endDate,
            'bulan'                => $bulan,
            'tahun'                => $tahun,
            'namaBulan'            => $namaBulanList[$bulan] ?? '',
            'namaBulanList'        => $namaBulanList,
            'search'               => $search,
            'perPage'              => $perPage,
            'activeFiltersCount'   => $activeFiltersCount,
            'stats'                => $stats,
            'rows'                 => $rows,
            'paginatedRows'        => $paginatedRows,
            'dailyRows'            => $rows,
            'matriksRows'          => $rows,
            'daysInMonth'          => $startDate->daysInMonth,
            'sholatSlots'          => self::SHOLAT_SLOTS,
            // Data Laporan WhatsApp
            'waText'               => $waReports['primary']['text'] ?? '',
            'waEncoded'            => urlencode($waReports['primary']['text'] ?? ''),
            'waSessions'           => $waReports['sessions'] ?? [],
            'waPrimarySlot'        => $waReports['primary_slot'] ?? 'dzuhur',
        ];
    }

    /**
     * Membangun teks laporan WhatsApp dengan format baku persis seperti spesifikasi:
     * 
     * 📅 *LAPORAN ABSENSI SHOLAT SISWA KELAS {NAMA_KELAS_ATAU_SASARAN}*
     * _{HARI}, {TANGGAL} {BULAN} {TAHUN}_
     * 
     * *Waktu Sholat:* {NAMA_SESI_SHOLAT}
     * 
     * *Daftar Kehadiran Siswa:*
     * 
     * 1. {Nama Siswa} ✅
     * 2. {Nama Siswa} ⚠️ - (telat)
     * 3. {Nama Siswa} ✉️ - IZIN
     * 4. {Nama Siswa} ❌ - ALPA
     * 
     * *Ringkasan Kehadiran:*
     * • Hadir: {TOTAL_HADIR} Siswa
     * • Sakit/Izin/Terlambat: {TOTAL_IZIN_TERLAMBAT} Siswa
     * • Alpha: {TOTAL_ALPA} Siswa
     */
    public function buildWhatsAppReports(
        array $rows,
        Carbon $tanggalCarbon,
        string $filterKelas,
        string $filterKamar,
        string $filterKelasPengajian,
        string $kategori,
        string $filterSesiId,
        ?MasterSesi $selectedSesiModel,
        array $evaluateSlots
    ): array {
        // Tentukan Nama Kelas / Sasaran
        if ($filterKelas !== 'semua' && $filterKelas !== '') {
            $sasaran = strtoupper($filterKelas);
        } elseif ($filterKamar !== '') {
            $sasaran = 'KAMAR ' . strtoupper($filterKamar);
        } elseif ($filterKelasPengajian !== 'semua' && $filterKelasPengajian !== '') {
            $kp = MasterKelasPengajian::find($filterKelasPengajian);
            $sasaran = strtoupper($kp ? $kp->nama_kelas : $filterKelasPengajian);
        } else {
            $sasaran = 'SEMUA SANTRI';
        }

        // Tanggal Bahasa Indonesia: _{HARI}, {TANGGAL} {BULAN} {TAHUN}_
        $hariIndo = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        $hari = $hariIndo[$tanggalCarbon->format('l')] ?? $tanggalCarbon->format('l');
        $tgl = $tanggalCarbon->day;
        $bln = $bulanIndo[$tanggalCarbon->month] ?? $tanggalCarbon->format('F');
        $thn = $tanggalCarbon->year;
        $dateFormatted = "_{$hari}, {$tgl} {$bln} {$thn}_";

        $sessions = [];
        $slotsToBuild = $evaluateSlots;

        // Jika semua sholat dievaluasi, susun daftar opsi sesi untuk WhatsApp
        if (empty($slotsToBuild)) {
            $slotsToBuild = self::SHOLAT_SLOTS;
        }

        foreach ($slotsToBuild as $slotKey => $slotMeta) {
            $slotLabel = strtoupper($slotMeta['label'] ?? $slotKey);
            $isPengajian = ($kategori === 'pengajian') || (isset($slotMeta['model']) && ($slotMeta['model']->kategori === 'pengajian'));

            $headerKegiatan = $isPengajian ? 'PENGAJIAN' : 'SHOLAT';
            $headerWaktuLabel = $isPengajian ? 'Waktu Pengajian' : 'Waktu Sholat';

            $header = "📅 *LAPORAN ABSENSI {$headerKegiatan} SISWA KELAS {$sasaran}*\n{$dateFormatted}\n\n*{$headerWaktuLabel}:* {$slotLabel}\n\n*Daftar Kehadiran Siswa:*";

            $lines = [];
            $hadirCount = 0;
            $izinTerlambatCount = 0;
            $alpaCount = 0;

            foreach ($rows as $idx => $r) {
                $cleanName = self::cleanStudentName($r['nama_lengkap']);
                $sesiStatus = $r['status_per_sesi'][$slotKey] ?? null;

                if ($sesiStatus) {
                    $statusTag = $sesiStatus['wa_emoji'];
                    $st = $sesiStatus['status'];
                    if ($st === 'hadir') {
                        $hadirCount++;
                    } elseif (in_array($st, ['terlambat', 'izin', 'sakit'], true)) {
                        $izinTerlambatCount++;
                    } else {
                        $alpaCount++;
                    }
                } else {
                    $statusTag = '❌ - ALPA';
                    $alpaCount++;
                }

                $lines[] = sprintf('%d. %s %s', $idx + 1, $cleanName, $statusTag);
            }

            $body = implode("\n", $lines);

            $summary = "*Ringkasan Kehadiran:*\n" .
                       "• Hadir: {$hadirCount} Siswa\n" .
                       "• Sakit/Izin/Terlambat: {$izinTerlambatCount} Siswa\n" .
                       "• Alpha: {$alpaCount} Siswa";

            $fullMessage = "{$header}\n\n{$body}\n\n{$summary}";

            $sessions[$slotKey] = [
                'slot_key'               => $slotKey,
                'label'                  => $slotLabel,
                'header_kegiatan'        => $headerKegiatan,
                'text'                   => $fullMessage,
                'total_hadir'            => $hadirCount,
                'total_izin_terlambat'   => $izinTerlambatCount,
                'total_alpa'             => $alpaCount,
            ];
        }

        // Tentukan sesi primer default
        $primarySlot = 'dzuhur';
        if (isset($sessions[$filterSesiId])) {
            $primarySlot = $filterSesiId;
        } elseif ($selectedSesiModel && isset($sessions['sesi_' . $selectedSesiModel->id])) {
            $primarySlot = 'sesi_' . $selectedSesiModel->id;
        } elseif (!isset($sessions[$primarySlot])) {
            $keys = array_keys($sessions);
            $primarySlot = reset($keys) ?: 'dzuhur';
        }

        return [
            'primary'      => $sessions[$primarySlot] ?? (reset($sessions) ?: ['text' => '']),
            'primary_slot' => $primarySlot,
            'sessions'     => $sessions,
        ];
    }

    /**
     * Deteksi slot sholat dari MasterSesi atau waktu tap.
     */
    public static function detectSlotSholat(?MasterSesi $sesi, ?Carbon $waktuTap = null): ?string
    {
        if ($sesi) {
            $kode = strtolower((string) $sesi->kode_sesi);
            $nama = strtolower((string) $sesi->nama_sesi);

            if (str_contains($kode, 'subuh') || str_contains($nama, 'subuh')) return 'subuh';
            if (str_contains($kode, 'dzuhur') || str_contains($nama, 'dzuhur') || str_contains($kode, 'zuhur') || str_contains($nama, 'zuhur')) return 'dzuhur';
            if (str_contains($kode, 'ashar') || str_contains($nama, 'ashar') || str_contains($kode, 'asar') || str_contains($nama, 'asar')) return 'ashar';
            if (str_contains($kode, 'maghrib') || str_contains($nama, 'maghrib')) return 'maghrib';
            if (str_contains($kode, 'isya') || str_contains($nama, 'isya')) return 'isya';

            if (!empty($sesi->jam_mulai)) {
                $jam = substr((string) $sesi->jam_mulai, 0, 5);
                if ($jam >= '03:00' && $jam <= '06:30') return 'subuh';
                if ($jam >= '11:00' && $jam <= '13:30') return 'dzuhur';
                if ($jam >= '14:30' && $jam <= '16:59') return 'ashar';
                if ($jam >= '17:00' && $jam <= '18:44') return 'maghrib';
                if ($jam >= '18:45' && $jam <= '22:00') return 'isya';
            }
        }

        if ($waktuTap) {
            $jam = $waktuTap->format('H:i');
            if ($jam >= '03:30' && $jam <= '06:30') return 'subuh';
            if ($jam >= '11:00' && $jam <= '13:30') return 'dzuhur';
            if ($jam >= '14:30' && $jam <= '16:59') return 'ashar';
            if ($jam >= '17:00' && $jam <= '18:44') return 'maghrib';
            if ($jam >= '18:45' && $jam <= '21:30') return 'isya';
        }

        return null;
    }

    /**
     * Cek data perizinan santri pada tanggal tertentu.
     */
    private function findSantriIzin(Siswa $siswa, string $dateStr, Collection $sakits, Collection $sambangs): ?array
    {
        // 1. Cek Perizinan Sakit
        $sakitMatch = $sakits->first(function (PerizinanSakit $sk) use ($siswa, $dateStr) {
            $same = ($sk->siswa_id && (int) $sk->siswa_id === (int) $siswa->id)
                || (!$sk->siswa_id && mb_strtolower(trim((string) $sk->nama)) === mb_strtolower(trim((string) $siswa->nama_lengkap)));
            if (!$same || !$sk->tanggal_sakit) return false;
            $tgl = $sk->tanggal_sakit->toDateString();
            return $tgl === $dateStr || ($tgl <= $dateStr && $sk->status === 'sakit');
        });

        if ($sakitMatch) {
            return [
                'status' => 'sakit',
                'label'  => 'Sakit: ' . ($sakitMatch->sakit ?: ($sakitMatch->keterangan ?: 'Rawat/Istirahat')),
            ];
        }

        // 2. Cek Perizinan Sambang / Pulang
        $sambangMatch = $sambangs->first(function (PerizinanSambang $sb) use ($siswa, $dateStr) {
            $same = ($sb->siswa_id && (int) $sb->siswa_id === (int) $siswa->id)
                || (!$sb->siswa_id && mb_strtolower(trim((string) $sb->nama)) === mb_strtolower(trim((string) $siswa->nama_lengkap)));
            if (!$same || !$sb->tanggal_mulai || !$sb->tanggal_kembali) return false;
            $mulai = $sb->tanggal_mulai->toDateString();
            $kembali = $sb->tanggal_kembali->toDateString();
            return $dateStr >= $mulai && $dateStr <= $kembali;
        });

        if ($sambangMatch) {
            return [
                'status' => 'izin',
                'label'  => 'Izin: ' . ($sambangMatch->keterangan ?: 'Sambang / Pulang'),
            ];
        }

        return null;
    }
}
