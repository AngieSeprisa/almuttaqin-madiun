<?php

namespace App\Http\Controllers;

use App\Models\Kamar;
use App\Models\MasterDevice;
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
     * Konfigurasi standar 5 waktu sholat.
     */
    public const SHOLAT_SLOTS = [
        'subuh' => [
            'key'       => 'subuh',
            'label'     => 'Subuh',
            'jam_mulai' => '04:00',
            'jam_akhir' => '06:30',
        ],
        'dzuhur' => [
            'key'       => 'dzuhur',
            'label'     => 'Dzuhur',
            'jam_mulai' => '11:30',
            'jam_akhir' => '13:30',
        ],
        'ashar' => [
            'key'       => 'ashar',
            'label'     => 'Ashar',
            'jam_mulai' => '14:45',
            'jam_akhir' => '17:00',
        ],
        'maghrib' => [
            'key'       => 'maghrib',
            'label'     => 'Maghrib',
            'jam_mulai' => '17:30',
            'jam_akhir' => '18:45',
        ],
        'isya' => [
            'key'       => 'isya',
            'label'     => 'Isya',
            'jam_mulai' => '18:45',
            'jam_akhir' => '21:30',
        ],
    ];

    /**
     * Halaman utama Rekap Presensi Sholat.
     */
    public function index(Request $request)
    {
        $data = $this->buildRekapSholatData($request);

        return view('rekap-absensi.pondok', $data);
    }

    /**
     * Ekspor Rekap Presensi Sholat ke CSV/Excel atau Cetak PDF/Print View.
     */
    public function export(Request $request)
    {
        $data = $this->buildRekapSholatData($request);
        $format = strtolower((string) $request->get('format', 'excel'));

        if (in_array($format, ['pdf', 'print', 'cetak'], true)) {
            return view('rekap-absensi.pondok-cetak', $data);
        }

        $rentang = $data['rentang'];
        $tanggal = $data['tanggal'];
        $fileName = sprintf('rekap_presensi_sholat_%s_%s.csv', $rentang, str_replace('-', '', $tanggal));

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
                'Nama Santri',
                'Kamar',
                'Jenjang',
                'Subuh',
                'Dzuhur',
                'Ashar',
                'Maghrib',
                'Isya',
                'Total H',
                'Total T',
                'Total I',
                'Total A',
            ]);

            foreach ($data['rows'] as $idx => $r) {
                fputcsv($handle, [
                    $idx + 1,
                    $r['nisn'],
                    $r['nama_lengkap'],
                    $r['kamar'],
                    $r['jenjang'],
                    $r['sholat']['subuh']['display'] ?? '-',
                    $r['sholat']['dzuhur']['display'] ?? '-',
                    $r['sholat']['ashar']['display'] ?? '-',
                    $r['sholat']['maghrib']['display'] ?? '-',
                    $r['sholat']['isya']['display'] ?? '-',
                    $r['total_h'],
                    $r['total_t'],
                    $r['total_i'],
                    $r['total_a'],
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Membangun data presensi sholat sesuai filter.
     */
    private function buildRekapSholatData(Request $request): array
    {
        // 1. Parameter Filter
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

        $filterSesiSholat = strtolower(trim((string) $request->get('sesi_sholat', $request->get('sesi_id', 'semua'))));
        if (!array_key_exists($filterSesiSholat, self::SHOLAT_SLOTS)) {
            $filterSesiSholat = 'semua';
        }

        $filterKamar = trim((string) $request->get('kamar', ''));
        $filterJenjang = strtolower(trim((string) $request->get('jenjang', 'semua')));
        $search = trim((string) $request->get('search', ''));

        // 2. Tentukan Rentang Tanggal Evaluasi
        if ($rentang === 'bulanan') {
            $startDate = Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();
            $endDate = (clone $startDate)->endOfMonth();
        } elseif ($rentang === 'mingguan') {
            $startDate = (clone $tanggalCarbon)->startOfWeek(Carbon::MONDAY);
            $endDate = (clone $startDate)->addDays(6);
        } else {
            // Harian
            $startDate = clone $tanggalCarbon;
            $endDate = clone $tanggalCarbon;
        }

        $startDateStr = $startDate->toDateString();
        $endDateStr = $endDate->toDateString();
        $todayStr = Carbon::today()->toDateString();
        $nowTimeStr = Carbon::now()->format('H:i');

        // Buat daftar tanggal yang dievaluasi
        $evalDates = [];
        $currDate = clone $startDate;
        while ($currDate->lte($endDate)) {
            $evalDates[] = $currDate->toDateString();
            $currDate->addDay();
        }

        // 3. Ambil Master Sesi Sholat & Map Slot
        $sesiSholatList = MasterSesi::where(function ($q) {
                $q->where('kategori', 'sholat')
                  ->orWhere('kode_sesi', 'LIKE', '%SUBUH%')
                  ->orWhere('kode_sesi', 'LIKE', '%DZUHUR%')
                  ->orWhere('kode_sesi', 'LIKE', '%ASHAR%')
                  ->orWhere('kode_sesi', 'LIKE', '%MAGHRIB%')
                  ->orWhere('kode_sesi', 'LIKE', '%ISYA%')
                  ->orWhere('nama_sesi', 'LIKE', '%sholat%')
                  ->orWhere('nama_sesi', 'LIKE', '%subuh%')
                  ->orWhere('nama_sesi', 'LIKE', '%dzuhur%')
                  ->orWhere('nama_sesi', 'LIKE', '%ashar%')
                  ->orWhere('nama_sesi', 'LIKE', '%maghrib%')
                  ->orWhere('nama_sesi', 'LIKE', '%isya%');
            })
            ->get();

        $sesiSlotMap = [];
        foreach ($sesiSholatList as $s) {
            $sesiSlotMap[$s->id] = self::detectSlotSholat($s);
        }

        // Master Kamar List untuk Filter
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

        // 4. Ambil Santri Aktif & Terapkan Filter (Kamar, Jenjang, Search)
        $allActiveSantri = Siswa::with(['pendaftaran'])
            ->where('status', 'aktif')
            ->orderBy('nama_lengkap')
            ->get();

        $filteredSantris = $allActiveSantri->filter(function (Siswa $siswa) use ($filterKamar, $filterJenjang, $search) {
            // Filter Kamar
            if ($filterKamar !== '') {
                if (stripos((string) $siswa->kamar, $filterKamar) === false) {
                    return false;
                }
            }

            // Filter Jenjang (SMP / SMA / Reguler)
            if ($filterJenjang !== 'semua' && $filterJenjang !== '') {
                $jenjangSantri = strtolower(trim((string) ($siswa->jenjang ?: ($siswa->pendaftaran?->jenjang ?: ''))));
                $kamarSantri = strtolower(trim((string) $siswa->kamar));

                if ($filterJenjang === 'smp') {
                    if (!str_contains($jenjangSantri, 'smp')) {
                        return false;
                    }
                } elseif ($filterJenjang === 'sma') {
                    if (!str_contains($jenjangSantri, 'sma')) {
                        return false;
                    }
                } elseif ($filterJenjang === 'reguler') {
                    $isReguler = str_contains($jenjangSantri, 'reguler')
                        || str_contains($kamarSantri, 'reguler')
                        || (!str_contains($jenjangSantri, 'smp') && !str_contains($jenjangSantri, 'sma'));
                    if (!$isReguler) {
                        return false;
                    }
                }
            }

            // Filter Pencarian Nama / NISN
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
        $presensisBySantriDateSlot = [];
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

                // Tentukan slot sholat
                $slotKey = $sesiSlotMap[$p->sesi_id] ?? self::detectSlotSholat($p->sesi, $p->waktu_tap);
                if (!$slotKey) continue;

                if (!isset($presensisBySantriDateSlot[$p->entitas_id][$dStr][$slotKey])) {
                    $presensisBySantriDateSlot[$p->entitas_id][$dStr][$slotKey] = $p;
                }
            }
        }

        // Ambil Data Perizinan pada Rentang Tanggal
        $sakits = PerizinanSakit::whereDate('tanggal_sakit', '<=', $endDateStr)
            ->where(function ($q) use ($startDateStr) {
                $q->whereDate('tanggal_sakit', '>=', $startDateStr)
                  ->orWhere('status', 'sakit');
            })
            ->get();

        $sambangs = PerizinanSambang::whereDate('tanggal_mulai', '<=', $endDateStr)
            ->whereDate('tanggal_kembali', '>=', $startDateStr)
            ->get();

        // 6. Evaluasi Setiap Santri & Setiap Slot Sholat
        $rows = [];
        $grandTotalH = 0;
        $grandTotalT = 0;
        $grandTotalI = 0;
        $grandTotalA = 0;

        foreach ($filteredSantris as $siswa) {
            $siswaRow = [
                'siswa_id'     => $siswa->id,
                'nisn'         => $siswa->pendaftaran?->nisn ?: ($siswa->nisn ?: '-'),
                'nama_lengkap' => $siswa->nama_lengkap,
                'kamar'        => $siswa->kamar ?: '-',
                'jenjang'      => $siswa->jenjang ?: ($siswa->pendaftaran?->jenjang ?: 'Reguler'),
                'sholat'       => [],
                'total_h'      => 0,
                'total_t'      => 0,
                'total_i'      => 0,
                'total_a'      => 0,
            ];

            // Inisialisasi slot sholat untuk santri ini
            foreach (self::SHOLAT_SLOTS as $slotKey => $slotMeta) {
                $siswaRow['sholat'][$slotKey] = [
                    'key'     => $slotKey,
                    'label'   => $slotMeta['label'],
                    'code'    => '-',
                    'display' => '-',
                    'jam'     => null,
                    'status'  => 'belum',
                    'count_h' => 0,
                    'count_t' => 0,
                    'count_i' => 0,
                    'count_a' => 0,
                ];
            }

            // Iterasi seluruh tanggal dalam rentang
            foreach ($evalDates as $dStr) {
                // Cek izin santri pada tanggal ini
                $izinInfo = $this->findSantriIzin($siswa, $dStr, $sakits, $sambangs);

                foreach (self::SHOLAT_SLOTS as $slotKey => $slotMeta) {
                    $tap = $presensisBySantriDateSlot[$siswa->id][$dStr][$slotKey] ?? null;

                    if ($tap) {
                        // Santri tap RFID pada sesi sholat ini
                        $isLate = ($tap->status_kehadiran === 'terlambat');
                        if (!$isLate && $tap->sesi && !empty($tap->sesi->jam_mulai)) {
                            try {
                                $jamMulaiDt = Carbon::parse($dStr . ' ' . $tap->sesi->jam_mulai);
                                $toleransi = (int) ($tap->sesi->toleransi_menit ?? 15);
                                $batasDt = (clone $jamMulaiDt)->addMinutes($toleransi);
                                if ($tap->waktu_tap && $tap->waktu_tap->gt($batasDt)) {
                                    $isLate = true;
                                }
                            } catch (\Throwable $e) {}
                        }

                        if ($isLate) {
                            $siswaRow['sholat'][$slotKey]['count_t']++;
                            $siswaRow['total_t']++;
                            $status = 'terlambat';
                            $code = 'T';
                        } else {
                            $siswaRow['sholat'][$slotKey]['count_h']++;
                            $siswaRow['total_h']++;
                            $status = 'hadir';
                            $code = 'H';
                        }

                        $jamTap = $tap->waktu_tap ? $tap->waktu_tap->format('H:i') : null;
                        if ($rentang === 'harian') {
                            $siswaRow['sholat'][$slotKey]['status'] = $status;
                            $siswaRow['sholat'][$slotKey]['code'] = $code;
                            $siswaRow['sholat'][$slotKey]['jam'] = $jamTap;
                            $siswaRow['sholat'][$slotKey]['display'] = $jamTap ?: $code;
                        }
                    } elseif ($izinInfo) {
                        // Tidak tap, tapi ada surat izin / sakit
                        $siswaRow['sholat'][$slotKey]['count_i']++;
                        $siswaRow['total_i']++;

                        if ($rentang === 'harian') {
                            $siswaRow['sholat'][$slotKey]['status'] = 'izin';
                            $siswaRow['sholat'][$slotKey]['code'] = 'I';
                            $siswaRow['sholat'][$slotKey]['display'] = 'I';
                        }
                    } else {
                        // Tidak tap dan tidak ada izin -> Cek apakah waktu sholat sudah lewat
                        $sudahLewat = false;
                        if ($dStr < $todayStr) {
                            $sudahLewat = true;
                        } elseif ($dStr === $todayStr) {
                            $jamAkhir = $slotMeta['jam_akhir'];
                            if ($nowTimeStr >= $jamAkhir) {
                                $sudahLewat = true;
                            }
                        }

                        if ($sudahLewat) {
                            $siswaRow['sholat'][$slotKey]['count_a']++;
                            $siswaRow['total_a']++;

                            if ($rentang === 'harian') {
                                $siswaRow['sholat'][$slotKey]['status'] = 'alfa';
                                $siswaRow['sholat'][$slotKey]['code'] = 'A';
                                $siswaRow['sholat'][$slotKey]['display'] = 'A';
                            }
                        } else {
                            // Belum berlangsung
                            if ($rentang === 'harian') {
                                $siswaRow['sholat'][$slotKey]['status'] = 'belum';
                                $siswaRow['sholat'][$slotKey]['code'] = '-';
                                $siswaRow['sholat'][$slotKey]['display'] = '-';
                            }
                        }
                    }
                }
            }

            // Jika mode mingguan atau bulanan, susun tampilan ringkasan per slot sholat
            if ($rentang !== 'harian') {
                foreach (self::SHOLAT_SLOTS as $slotKey => $slotMeta) {
                    $cH = $siswaRow['sholat'][$slotKey]['count_h'];
                    $cT = $siswaRow['sholat'][$slotKey]['count_t'];
                    $cI = $siswaRow['sholat'][$slotKey]['count_i'];
                    $cA = $siswaRow['sholat'][$slotKey]['count_a'];

                    $totHadir = $cH + $cT;
                    $parts = [];
                    if ($cH > 0) $parts[] = "{$cH}H";
                    if ($cT > 0) $parts[] = "{$cT}T";
                    if ($cI > 0) $parts[] = "{$cI}I";
                    if ($cA > 0) $parts[] = "{$cA}A";

                    $siswaRow['sholat'][$slotKey]['display'] = !empty($parts) ? implode(' ', $parts) : '-';
                    $siswaRow['sholat'][$slotKey]['code'] = $totHadir > 0 ? 'H' : ($cA > 0 ? 'A' : ($cI > 0 ? 'I' : '-'));
                }
            }

            $grandTotalH += $siswaRow['total_h'];
            $grandTotalT += $siswaRow['total_t'];
            $grandTotalI += $siswaRow['total_i'];
            $grandTotalA += $siswaRow['total_a'];

            $rows[] = $siswaRow;
        }

        $stats = [
            'total_target' => $filteredSantris->count(),
            'hadir'        => $grandTotalH,
            'terlambat'    => $grandTotalT,
            'izin_sakit'   => $grandTotalI,
            'alfa'         => $grandTotalA,
        ];

        $namaBulanList = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        return [
            'rentang'          => $rentang,
            'mode'             => $rentang, // alias kompatibilitas
            'tanggal'          => $tanggal,
            'tanggalCarbon'    => $tanggalCarbon,
            'startDate'        => $startDate,
            'endDate'          => $endDate,
            'bulan'            => $bulan,
            'tahun'            => $tahun,
            'namaBulan'        => $namaBulanList[$bulan] ?? '',
            'namaBulanList'    => $namaBulanList,
            'filterSesiSholat' => $filterSesiSholat,
            'filterKamar'      => $filterKamar,
            'filterJenjang'    => $filterJenjang,
            'search'           => $search,
            'kamarList'        => $kamarList,
            'sholatSlots'      => self::SHOLAT_SLOTS,
            'stats'            => $stats,
            'rows'             => $rows,
            // Kompatibilitas untuk view lama jika masih ada referensi
            'dailyRows'        => $rows,
            'matriksRows'      => $rows,
            'daysInMonth'      => $startDate->daysInMonth,
            'selectedSesi'     => null,
            'kategori'         => 'sholat',
        ];
    }

    /**
     * Deteksi slot sholat (subuh, dzuhur, ashar, maghrib, isya) dari MasterSesi atau waktu tap.
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
                'label'  => 'Sakit: ' . ($sakitMatch->sakit ?: 'Rawat/Istirahat'),
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
