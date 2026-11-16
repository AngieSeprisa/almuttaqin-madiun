<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\UjianKunci;
use App\Models\UjianHasil;
use App\Models\Classroom;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\User;
use App\Services\Omr\LjkTemplateService;
use App\Services\Omr\OmrScannerService;
use App\Services\Omr\QrCodeGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UjianController extends Controller
{
    /**
     * Tampilkan daftar ujian
     */
    public function index(Request $request)
    {
        $query = Ujian::with(['mapel', 'kelas', 'guru'])->withCount('hasils');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nama_ujian', 'like', "%$s%")
                  ->orWhere('kode_ujian', 'like', "%$s%");
            });
        }

        if ($request->filled('kelas_id')) {
            $query->where('kelas_id', $request->kelas_id);
        }

        if ($request->filled('mapel_id')) {
            $query->where('mapel_id', $request->mapel_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $ujians = $query->latest('tanggal_ujian')->paginate(12)->withQueryString();

        $kelases = Classroom::active()->orderBy('tingkat')->orderBy('nama_kelas')->get();
        $mapels = MataPelajaran::orderBy('mata_pelajaran')->get();

        // Statistik Ringkas
        $totalUjian = Ujian::count();
        $ujianAktif = Ujian::where('status', 'aktif')->count();
        $ujianSelesai = Ujian::where('status', 'selesai')->count();
        $totalKoreksi = UjianHasil::count();

        return view('ujian.index', compact(
            'ujians',
            'kelases',
            'mapels',
            'totalUjian',
            'ujianAktif',
            'ujianSelesai',
            'totalKoreksi'
        ));
    }

    /**
     * Form buat ujian baru
     */
    public function create()
    {
        $kelases = Classroom::active()->orderBy('tingkat')->orderBy('nama_kelas')->get();
        $mapels = MataPelajaran::orderBy('mata_pelajaran')->get();
        $gurus = User::where(function ($q) {
            $q->where('role', 'guru')->orWhere('is_guru_sekolah', true);
        })->where('is_active', true)->orderBy('name')->get();

        // Generate saran kode ujian otomatis (contoh: UJN-202611-001)
        $prefix = 'UJN-' . date('Ym') . '-';
        $last = Ujian::where('kode_ujian', 'like', "$prefix%")->orderBy('id', 'desc')->first();
        $nextNum = $last ? (((int) substr($last->kode_ujian, -3)) + 1) : 1;
        $suggestedKode = $prefix . str_pad($nextNum, 3, '0', STR_PAD_LEFT);

        return view('ujian.form', [
            'ujian' => new Ujian([
                'kode_ujian'    => $suggestedKode,
                'jumlah_soal'   => 40,
                'jumlah_opsi'   => 4,
                'bobot_benar'   => 1.00,
                'tanggal_ujian' => now()->toDateString(),
                'status'        => 'draft',
            ]),
            'isEdit'  => false,
            'kelases' => $kelases,
            'mapels'  => $mapels,
            'gurus'   => $gurus,
        ]);
    }

    /**
     * Simpan ujian baru ke database
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_ujian'    => 'required|string|max:50|unique:ujian,kode_ujian',
            'nama_ujian'    => 'required|string|max:200',
            'mapel_id'      => 'nullable|exists:mata_pelajarans,id',
            'kelas_id'      => 'nullable|exists:classrooms,id',
            'guru_id'       => 'nullable|exists:users,id',
            'jumlah_soal'   => 'required|integer|min:5|max:100',
            'jumlah_opsi'   => 'required|integer|in:4,5',
            'bobot_benar'   => 'required|numeric|min:0.1|max:100',
            'tanggal_ujian' => 'required|date',
            'status'        => 'required|in:draft,aktif,selesai',
        ]);

        $ujian = DB::transaction(function () use ($validated) {
            $ujian = Ujian::create($validated);

            // Inisialisasi Kunci Jawaban Default (A untuk setiap nomor)
            $kuncis = [];
            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $kuncis[] = [
                    'ujian_id'      => $ujian->id,
                    'nomor_soal'    => $no,
                    'kunci_jawaban' => 'A',
                    'bobot'         => $ujian->bobot_benar,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
            UjianKunci::insert($kuncis);

            return $ujian;
        });

        return redirect()->route('ujian.kunci', $ujian->id)
            ->with('success', "Ujian {$ujian->nama_ujian} berhasil dibuat. Silakan lengkapi kunci jawaban di bawah.");
    }

    /**
     * Form edit ujian
     */
    public function edit(Ujian $ujian)
    {
        $kelases = Classroom::active()->orderBy('tingkat')->orderBy('nama_kelas')->get();
        $mapels = MataPelajaran::orderBy('mata_pelajaran')->get();
        $gurus = User::where(function ($q) {
            $q->where('role', 'guru')->orWhere('is_guru_sekolah', true);
        })->where('is_active', true)->orderBy('name')->get();

        return view('ujian.form', [
            'ujian'   => $ujian,
            'isEdit'  => true,
            'kelases' => $kelases,
            'mapels'  => $mapels,
            'gurus'   => $gurus,
        ]);
    }

    /**
     * Update data ujian
     */
    public function update(Request $request, Ujian $ujian)
    {
        $validated = $request->validate([
            'kode_ujian'    => 'required|string|max:50|unique:ujian,kode_ujian,' . $ujian->id,
            'nama_ujian'    => 'required|string|max:200',
            'mapel_id'      => 'nullable|exists:mata_pelajarans,id',
            'kelas_id'      => 'nullable|exists:classrooms,id',
            'guru_id'       => 'nullable|exists:users,id',
            'jumlah_soal'   => 'required|integer|min:5|max:100',
            'jumlah_opsi'   => 'required|integer|in:4,5',
            'bobot_benar'   => 'required|numeric|min:0.1|max:100',
            'tanggal_ujian' => 'required|date',
            'status'        => 'required|in:draft,aktif,selesai',
        ]);

        $prevJumlahSoal = $ujian->jumlah_soal;

        DB::transaction(function () use ($ujian, $validated, $prevJumlahSoal) {
            $ujian->update($validated);

            // Jika jumlah soal bertambah, tambahkan kunci default
            if ($ujian->jumlah_soal > $prevJumlahSoal) {
                $newKunci = [];
                for ($no = $prevJumlahSoal + 1; $no <= $ujian->jumlah_soal; $no++) {
                    $newKunci[] = [
                        'ujian_id'      => $ujian->id,
                        'nomor_soal'    => $no,
                        'kunci_jawaban' => 'A',
                        'bobot'         => $ujian->bobot_benar,
                        'created_at'    => now(),
                        'updated_at'    => now(),
                    ];
                }
                UjianKunci::insert($newKunci);
            } elseif ($ujian->jumlah_soal < $prevJumlahSoal) {
                // Hapus kunci yang melebihi jumlah soal baru
                UjianKunci::where('ujian_id', $ujian->id)
                    ->where('nomor_soal', '>', $ujian->jumlah_soal)
                    ->delete();
            }
        });

        return redirect()->route('ujian.index')
            ->with('success', "Data ujian {$ujian->nama_ujian} berhasil diperbarui.");
    }

    /**
     * Hapus ujian dan seluruh hasil terkait
     */
    public function destroy(Ujian $ujian)
    {
        $nama = $ujian->nama_ujian;
        $ujian->delete();

        return redirect()->route('ujian.index')
            ->with('success', "Ujian {$nama} berhasil dihapus.");
    }

    /**
     * Halaman manajemen Kunci Jawaban interaktif
     */
    public function kunci(Ujian $ujian)
    {
        $ujian->load('kuncis');
        $kuncis = $ujian->kuncis->keyBy('nomor_soal');

        return view('ujian.kunci', compact('ujian', 'kuncis'));
    }

    /**
     * Simpan pembaruan Kunci Jawaban
     */
    public function updateKunci(Request $request, Ujian $ujian)
    {
        $keys = $request->input('kunci', []);
        $bobots = $request->input('bobot', []);

        DB::transaction(function () use ($ujian, $keys, $bobots) {
            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $huruf = strtoupper(trim($keys[$no] ?? 'A'));
                if (!in_array($huruf, ['A', 'B', 'C', 'D', 'E'])) {
                    $huruf = 'A';
                }

                $bobot = isset($bobots[$no]) ? (float)$bobots[$no] : (float)$ujian->bobot_benar;

                UjianKunci::updateOrCreate(
                    [
                        'ujian_id'   => $ujian->id,
                        'nomor_soal' => $no,
                    ],
                    [
                        'kunci_jawaban' => $huruf,
                        'bobot'         => $bobot,
                    ]
                );
            }

            // Hitung ulang nilai seluruh siswa yang sudah pernah dikoreksi
            $this->recalculateAllScores($ujian);
        });

        return redirect()->back()->with('success', 'Kunci jawaban berhasil disimpan dan skor hasil ujian telah disinkronkan.');
    }

    /**
     * Hitung ulang skor semua siswa pada ujian ini
     */
    private function recalculateAllScores(Ujian $ujian): void
    {
        $kunciMap = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $bobotMap = $ujian->kuncis->pluck('bobot', 'nomor_soal')->all();
        $totalSkorMaks = array_sum($bobotMap) ?: (count($kunciMap) * ($ujian->bobot_benar ?: 1.0));

        foreach ($ujian->hasils as $hasil) {
            $jawaban = $hasil->jawaban_siswa ?: [];
            $benar = 0;
            $salah = 0;
            $kosong = 0;
            $totalDidapat = 0.0;

            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $jawab = $jawaban[$no] ?? null;
                $kunci = $kunciMap[$no] ?? null;
                $b = (float)($bobotMap[$no] ?? $ujian->bobot_benar ?? 1.0);

                if ($jawab === null) {
                    $kosong++;
                } elseif ($kunci && strtoupper($jawab) === strtoupper($kunci)) {
                    $benar++;
                    $totalDidapat += $b;
                } else {
                    $salah++;
                }
            }

            $nilai = $totalSkorMaks > 0 ? round(($totalDidapat / $totalSkorMaks) * 100, 2) : 0.0;

            $hasil->update([
                'jumlah_benar'  => $benar,
                'jumlah_salah'  => $salah,
                'jumlah_kosong' => $kosong,
                'nilai_akhir'   => $nilai,
            ]);
        }
    }

    /**
     * Cetak LJK Kelas Siap Print (A4 dengan 4 Corner Fiducial Markers + QR Code + Grid OMR Presisi)
     */
    public function cetakLjk(Request $request, Ujian $ujian)
    {
        $ujian->load(['mapel', 'kelas', 'guru']);

        // Ambil daftar siswa kelas terkait
        $query = Siswa::query();
        if ($ujian->kelas_id && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $query->where(function ($q) use ($namaKelas) {
                $q->where('kelas', $namaKelas)
                  ->orWhereHas('pendaftaran', fn($sq) => $sq->where('kelas', $namaKelas));
            });
        }

        if ($request->filled('siswa_id')) {
            $query->where('id', $request->siswa_id);
        }

        $siswas = $query->where('status', 'aktif')->orderBy('nama_lengkap')->get();

        // Jika kelas kosong / belum ada siswa, buat dummy 1 lembar blank untuk contoh cetak
        if ($siswas->isEmpty()) {
            $siswas = collect([
                new Siswa([
                    'id'           => 0,
                    'nama_lengkap' => '................................................',
                    'kamar'        => '-',
                ]),
            ]);
        }

        // Siapkan QR Code SVG per siswa
        $qrCodes = [];
        foreach ($siswas as $s) {
            $payload = LjkTemplateService::makeQrPayload($ujian, $s);
            $qrCodes[$s->id] = QrCodeGenerator::svg($payload, 110);
        }

        $bubbleCoords = LjkTemplateService::getBubbleCoordinates($ujian->jumlah_soal, $ujian->jumlah_opsi);

        return view('ujian.cetak-ljk', compact('ujian', 'siswas', 'qrCodes', 'bubbleCoords'));
    }

    /**
     * Halaman Upload & Koreksi LJK Scan
     */
    public function uploadScan(Ujian $ujian)
    {
        $ujian->load(['mapel', 'kelas', 'guru'])->loadCount('hasils');
        return view('ujian.upload-scan', compact('ujian'));
    }

    /**
     * Proses Pemindaian File PDF / Gambar yang Diunggah (Sequential Batch Processing)
     */
    public function processScan(Request $request, Ujian $ujian)
    {
        $request->validate([
            'file_scan' => 'required|file|mimes:pdf,jpg,jpeg,png|max:51200', // maks 50MB
        ]);

        $file = $request->file('file_scan');
        $tempPath = $file->getRealPath();

        $scanner = new OmrScannerService();

        try {
            $result = $scanner->processFile($ujian, $tempPath);

            return response()->json([
                'success' => true,
                'message' => "Pemindaian selesai: {$result['total_halaman']} halaman diproses ({$result['berhasil']} siswa berhasil dicocokkan, {$result['perlu_cek']} perlu cek manual).",
                'data'    => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Dashboard Rekap Nilai Ujian
     */
    public function hasil(Ujian $ujian)
    {
        $ujian->load(['mapel', 'kelas', 'guru']);
        $hasils = UjianHasil::with('siswa')
            ->where('ujian_id', $ujian->id)
            ->join('siswas', 'siswas.id', '=', 'ujian_hasil.siswa_id')
            ->orderBy('siswas.nama_lengkap')
            ->select('ujian_hasil.*')
            ->paginate(30);

        $kuncis = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $statistik = $ujian->statistik;

        return view('ujian.hasil', compact('ujian', 'hasils', 'kuncis', 'statistik'));
    }

    /**
     * Update Koreksi Manual oleh Guru jika ada jawaban ganda / samar
     */
    public function updateHasilManual(Request $request, Ujian $ujian, UjianHasil $hasil)
    {
        $validated = $request->validate([
            'nomor_soal' => 'required|integer|min:1|max:' . $ujian->jumlah_soal,
            'jawaban'    => 'nullable|string|in:A,B,C,D,E,GANDA,null',
        ]);

        $nomor = $validated['nomor_soal'];
        $jawabanBaru = $validated['jawaban'] === 'null' ? null : $validated['jawaban'];

        $currentAnswers = $hasil->jawaban_siswa ?? [];
        $currentAnswers[$nomor] = $jawabanBaru;

        // Hitung ulang nilai siswa ini
        $kunciMap = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $bobotMap = $ujian->kuncis->pluck('bobot', 'nomor_soal')->all();
        $totalSkorMaks = array_sum($bobotMap) ?: ($ujian->jumlah_soal * ($ujian->bobot_benar ?: 1.0));

        $benar = 0;
        $salah = 0;
        $kosong = 0;
        $totalDidapat = 0.0;
        $hasGanda = false;

        for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
            $jawab = $currentAnswers[$no] ?? null;
            $kunci = $kunciMap[$no] ?? null;
            $b = (float)($bobotMap[$no] ?? $ujian->bobot_benar ?? 1.0);

            if ($jawab === 'GANDA') {
                $hasGanda = true;
                $salah++;
            } elseif ($jawab === null) {
                $kosong++;
            } elseif ($kunci && strtoupper($jawab) === strtoupper($kunci)) {
                $benar++;
                $totalDidapat += $b;
            } else {
                $salah++;
            }
        }

        $nilai = $totalSkorMaks > 0 ? round(($totalDidapat / $totalSkorMaks) * 100, 2) : 0.0;

        $hasil->update([
            'jawaban_siswa' => $currentAnswers,
            'jumlah_benar'  => $benar,
            'jumlah_salah'  => $salah,
            'jumlah_kosong' => $kosong,
            'nilai_akhir'   => $nilai,
            'status_scan'   => $hasGanda ? 'perlu_cek_manual' : 'valid',
            'catatan_scan'  => $hasGanda ? 'Masih terdapat jawaban ganda.' : 'Telah diverifikasi manual oleh Guru.',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Jawaban nomor $nomor berhasil diperbarui. Nilai akhir: $nilai",
            'data'    => $hasil->fresh(),
        ]);
    }

    /**
     * Export Hasil Nilai ke Excel (.xlsx / .csv)
     */
    public function exportExcel(Request $request, Ujian $ujian)
    {
        $ujian->load(['mapel', 'kelas', 'guru']);
        $hasils = UjianHasil::with('siswa')
            ->where('ujian_id', $ujian->id)
            ->join('siswas', 'siswas.id', '=', 'ujian_hasil.siswa_id')
            ->orderBy('siswas.nama_lengkap')
            ->select('ujian_hasil.*')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Hasil Ujian');

        // Header Dokumen
        $sheet->setCellValue('A1', 'YAYASAN PONDOK PESANTREN AL-MUTTAQIN');
        $sheet->setCellValue('A2', 'REKAPITULASI HASIL UJIAN & KOREKSI LJK');
        $sheet->setCellValue('A4', 'Nama Ujian: ' . $ujian->nama_ujian);
        $sheet->setCellValue('A5', 'Mata Pelajaran: ' . ($ujian->mapel?->mata_pelajaran ?? '-'));
        $sheet->setCellValue('D4', 'Kelas: ' . ($ujian->kelas?->nama_kelas ?? '-'));
        $sheet->setCellValue('D5', 'Tanggal: ' . $ujian->tanggal_ujian?->format('d/m/Y'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A4:D5')->getFont()->setBold(true);

        // Header Kolom Tabel
        $headers = [
            'No',
            'NISN',
            'Nama Siswa',
            'Jumlah Benar',
            'Jumlah Salah',
            'Jumlah Kosong',
            'Nilai Akhir',
            'Status Koreksi',
            'Catatan / Rincian',
        ];

        $col = 'A';
        $row = 7;
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $row, $h);
            $sheet->getStyle($col . $row)->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
            $sheet->getStyle($col . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0052FF');
            $sheet->getStyle($col . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $col++;
        }

        // Isi Data Siswa
        $no = 1;
        $row = 8;
        foreach ($hasils as $h) {
            $sheet->setCellValue('A' . $row, $no);
            $sheet->setCellValue('B' . $row, $h->siswa?->nisn ?? '-');
            $sheet->setCellValue('C' . $row, $h->siswa?->nama_lengkap ?? '-');
            $sheet->setCellValue('D' . $row, $h->jumlah_benar);
            $sheet->setCellValue('E' . $row, $h->jumlah_salah);
            $sheet->setCellValue('F' . $row, $h->jumlah_kosong);
            $sheet->setCellValue('G' . $row, $h->nilai_akhir);
            $sheet->setCellValue('H' . $row, $h->status_scan === 'valid' ? 'Valid' : 'Perlu Cek');
            $sheet->setCellValue('I' . $row, $h->catatan_scan ?? '-');

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $row . ':G' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('H' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Warna status
            if ($h->status_scan !== 'valid') {
                $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('D97706');
            }

            $no++;
            $row++;
        }

        // Auto width
        foreach (range('A', 'I') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }

        $filename = 'Hasil_Ujian_' . str_replace(' ', '_', $ujian->nama_ujian) . '_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

