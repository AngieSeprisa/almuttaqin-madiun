<?php

namespace App\Http\Controllers;

use App\Models\Ujian;
use App\Models\UjianKunci;
use App\Models\UjianHasil;
use App\Models\UjianSoal;
use App\Models\Classroom;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\TimeSlot;
use App\Models\User;
use App\Services\Omr\LjkTemplateService;
use App\Services\Omr\OmrScannerService;
use App\Services\Omr\QrCodeGenerator;
use App\Services\Omr\WordQuestionParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UjianController extends Controller
{
    /**
     * Redirect default /ujian ke sub menu Soal Ujian
     */
    public function index(Request $request)
    {
        return $this->soalIndex($request);
    }

    // =========================================================================
    // 1. SUB MENU: SOAL UJIAN
    // =========================================================================

    /**
     * Sub menu 1: Daftar Soal Ujian
     */
    public function soalIndex(Request $request)
    {
        $query = Ujian::with(['mapel', 'kelas', 'guru'])
            ->withCount(['soals', 'kuncis']);

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

        $ujians = $query->latest('tanggal_ujian')->paginate(12)->withQueryString();
        $kelases = Classroom::active()->orderBy('tingkat')->orderBy('nama_kelas')->get();
        $mapels = MataPelajaran::orderBy('mata_pelajaran')->get();

        return view('ujian.soal-index', compact('ujians', 'kelases', 'mapels'));
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
                'timer_default' => 90,
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
     * Simpan ujian baru
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_ujian'    => 'required|string|max:50|unique:ujian,kode_ujian',
            'nama_ujian'    => 'required|string|max:200',
            'mapel_id'      => 'nullable|exists:mata_pelajarans,id',
            'kelas_id'      => 'nullable|exists:classrooms,id',
            'guru_id'       => 'nullable|exists:users,id',
            'jumlah_soal'   => 'required|integer|min:1|max:100',
            'jumlah_opsi'   => 'required|integer|in:4,5',
            'bobot_benar'   => 'required|numeric|min:0.1|max:100',
            'timer_default' => 'nullable|integer|min:10|max:1800',
            'tanggal_ujian' => 'required|date',
            'status'        => 'required|in:draft,aktif,selesai',
            'file_word'     => 'nullable|file|mimes:docx|max:10240',
        ]);

        $timerDefault = $validated['timer_default'] ?? 90;

        $ujian = DB::transaction(function () use ($validated, $timerDefault, $request) {
            $ujian = Ujian::create([
                'kode_ujian'    => $validated['kode_ujian'],
                'nama_ujian'    => $validated['nama_ujian'],
                'mapel_id'      => $validated['mapel_id'] ?? null,
                'kelas_id'      => $validated['kelas_id'] ?? null,
                'guru_id'       => $validated['guru_id'] ?? null,
                'jumlah_soal'   => $validated['jumlah_soal'],
                'jumlah_opsi'   => $validated['jumlah_opsi'],
                'bobot_benar'   => $validated['bobot_benar'],
                'timer_default' => $timerDefault,
                'tanggal_ujian' => $validated['tanggal_ujian'],
                'status'        => $validated['status'],
            ]);

            // Inisialisasi kunci jawaban default jika belum ada naskah Word
            $kunciData = [];
            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $kunciData[] = [
                    'ujian_id'      => $ujian->id,
                    'nomor_soal'    => $no,
                    'kunci_jawaban' => 'A',
                    'bobot'         => $ujian->bobot_benar,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];
            }
            UjianKunci::insert($kunciData);

            // Jika ada file Word (.docx) diunggah langsung saat pembuatan
            if ($request->hasFile('file_word')) {
                $this->processUploadedWordFile($request->file('file_word'), $ujian);
            }

            return $ujian;
        });

        return redirect()->route('ujian.kunci', $ujian->id)
            ->with('success', "Ujian {$ujian->nama_ujian} berhasil dibuat. Silakan atur kunci jawaban atau unggah naskah soal.");
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
            'jumlah_soal'   => 'required|integer|min:1|max:100',
            'jumlah_opsi'   => 'required|integer|in:4,5',
            'bobot_benar'   => 'required|numeric|min:0.1|max:100',
            'timer_default' => 'nullable|integer|min:10|max:1800',
            'tanggal_ujian' => 'required|date',
            'status'        => 'required|in:draft,aktif,selesai',
        ]);

        $prevJumlahSoal = $ujian->jumlah_soal;

        DB::transaction(function () use ($ujian, $validated, $prevJumlahSoal) {
            $ujian->update($validated);

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
                UjianKunci::where('ujian_id', $ujian->id)
                    ->where('nomor_soal', '>', $ujian->jumlah_soal)
                    ->delete();
            }
        });

        return redirect()->route('ujian.soal')
            ->with('success', "Data ujian {$ujian->nama_ujian} berhasil diperbarui.");
    }

    /**
     * Hapus ujian
     */
    public function destroy(Ujian $ujian)
    {
        $nama = $ujian->nama_ujian;
        $ujian->delete();

        return redirect()->route('ujian.soal')
            ->with('success', "Ujian {$nama} berhasil dihapus.");
    }

    /**
     * Unggah naskah soal Word (.docx) dan ekstrak butir soal otomatis
     */
    public function uploadWordSoal(Request $request, Ujian $ujian)
    {
        $request->validate([
            'file_word'     => 'required|file|mimes:docx|max:15360', // max 15MB
            'timer_per_soal'=> 'nullable|integer|min:10|max:1800',
        ]);

        $file = $request->file('file_word');
        $timer = (int) ($request->input('timer_per_soal') ?: ($ujian->timer_default ?: 90));

        try {
            $parsed = $this->processUploadedWordFile($file, $ujian, $timer);

            return redirect()->back()->with('success', "Naskah soal Word berhasil diproses! {$parsed['total_soal']} butir soal terbaca dan kunci jawaban telah disinkronkan.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal memproses file Word: ' . $e->getMessage());
        }
    }

    /**
     * Helper pemrosesan file Word (.docx)
     */
    private function processUploadedWordFile($uploadedFile, Ujian $ujian, int $timer = 90): array
    {
        $parser = new WordQuestionParserService();
        $parsed = $parser->parse($uploadedFile->getRealPath());

        if (empty($parsed['soals'])) {
            throw new \Exception("Tidak ada butir soal yang berhasil terdeteksi dalam file Word. Pastikan format penomoran sesuai template (misal: 1. Pertanyaan, A. Pilihan, B. Pilihan...).");
        }

        // Simpan file asli ke storage
        $storedPath = $uploadedFile->store('naskah_ujian', 'public');

        DB::transaction(function () use ($ujian, $parsed, $storedPath, $timer) {
            // Hapus soal lama
            UjianSoal::where('ujian_id', $ujian->id)->delete();

            $soalInserts = [];
            $kunciMap = [];

            foreach ($parsed['soals'] as $item) {
                $no = (int) $item['nomor'];
                $soalInserts[] = [
                    'ujian_id'      => $ujian->id,
                    'nomor_soal'    => $no,
                    'pertanyaan'    => $item['pertanyaan'],
                    'opsi_a'        => $item['opsi_a'],
                    'opsi_b'        => $item['opsi_b'],
                    'opsi_c'        => $item['opsi_c'],
                    'opsi_d'        => $item['opsi_d'],
                    'opsi_e'        => $item['opsi_e'],
                    'kunci_jawaban' => $item['kunci'] ?? 'A',
                    'timer_detik'   => $timer,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                $kunciMap[$no] = strtoupper($item['kunci'] ?? 'A');
            }

            UjianSoal::insert($soalInserts);

            // Update informasi ujian
            $ujian->update([
                'file_soal'     => $storedPath,
                'jumlah_soal'   => count($parsed['soals']),
                'jumlah_opsi'   => $parsed['jumlah_opsi'] ?? $ujian->jumlah_opsi,
                'timer_default' => $timer,
            ]);

            // Sinkronkan ke tabel ujian_kunci
            foreach ($kunciMap as $no => $kunciLetter) {
                UjianKunci::updateOrCreate(
                    ['ujian_id' => $ujian->id, 'nomor_soal' => $no],
                    ['kunci_jawaban' => $kunciLetter, 'bobot' => $ujian->bobot_benar]
                );
            }

            // Hapus kunci yang melebihi jumlah soal baru
            UjianKunci::where('ujian_id', $ujian->id)
                ->where('nomor_soal', '>', count($parsed['soals']))
                ->delete();
        });

        return $parsed;
    }

    /**
     * Unduh Template Naskah Soal Word (.docx)
     */
    public function downloadTemplate()
    {
        $parser = new WordQuestionParserService();
        $tempFile = $parser->generateTemplateDocx();

        return response()->download($tempFile, 'Template_Naskah_Soal_Ujian.docx')->deleteFileAfterSend(true);
    }

    /**
     * Halaman Kunci Jawaban
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

            $this->recalculateAllScores($ujian);
        });

        return redirect()->back()->with('success', 'Kunci jawaban berhasil disimpan dan skor hasil ujian telah disinkronkan.');
    }

    /**
     * Update timer per soal
     */
    public function updateTimer(Request $request, Ujian $ujian)
    {
        $validated = $request->validate([
            'timer_default'  => 'required|integer|min:10|max:1800',
            'soal_timer'     => 'nullable|array',
            'soal_timer.*'   => 'integer|min:10|max:1800',
        ]);

        DB::transaction(function () use ($ujian, $validated) {
            $ujian->update(['timer_default' => $validated['timer_default']]);

            if (!empty($validated['soal_timer'])) {
                foreach ($validated['soal_timer'] as $soalId => $detik) {
                    UjianSoal::where('id', $soalId)
                        ->where('ujian_id', $ujian->id)
                        ->update(['timer_detik' => $detik]);
                }
            } else {
                UjianSoal::where('ujian_id', $ujian->id)
                    ->update(['timer_detik' => $validated['timer_default']]);
            }
        });

        return redirect()->back()->with('success', 'Waktu hitung mundur soal berhasil diperbarui.');
    }

    // =========================================================================
    // 2. SUB MENU: LJK (LEMBAR JAWAB KOMPUTER)
    // =========================================================================

    /**
     * Sub menu 2: Halaman Manajemen & Cetak LJK
     */
    public function ljkIndex(Request $request)
    {
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])
            ->latest('tanggal_ujian')
            ->get();

        $selectedUjianId = $request->input('ujian_id') ?: ($ujians->first()?->id ?? null);
        $ujian = $selectedUjianId ? Ujian::with(['mapel', 'kelas', 'guru'])->find($selectedUjianId) : null;

        $siswas = collect();
        if ($ujian && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $siswas = Siswa::where(function ($q) use ($namaKelas) {
                $q->where('kelas', $namaKelas)
                  ->orWhereHas('pendaftaran', fn($sq) => $sq->where('kelas', $namaKelas));
            })->where('status', 'aktif')->orderBy('nama_lengkap')->get();
        }

        return view('ujian.ljk-index', compact('ujians', 'ujian', 'siswas'));
    }

    /**
     * Simpan teks peraturan ujian
     */
    public function updatePeraturan(Request $request, Ujian $ujian)
    {
        $validated = $request->validate([
            'peraturan_ujian' => 'nullable|string|max:2000',
        ]);

        $ujian->update([
            'peraturan_ujian' => $validated['peraturan_ujian'],
        ]);

        return redirect()->back()->with('success', 'Peraturan dan tata tertib LJK berhasil diperbarui.');
    }

    /**
     * Cetak LJK Kelas Siap Print (A4 dengan 4 Corner Fiducial Markers + QR Code + Grid OMR Presisi)
     */
    public function cetakLjk(Request $request, Ujian $ujian)
    {
        $ujian->load(['mapel', 'kelas', 'guru']);

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

        if ($siswas->isEmpty()) {
            $siswas = collect([
                new Siswa([
                    'id'           => 0,
                    'nama_lengkap' => '................................................',
                    'kamar'        => '-',
                ]),
            ]);
        }

        $qrCodes = [];
        foreach ($siswas as $s) {
            $payload = LjkTemplateService::makeQrPayload($ujian, $s);
            $qrCodes[$s->id] = QrCodeGenerator::svg($payload, 110);
        }

        $bubbleCoords = LjkTemplateService::getBubbleCoordinates($ujian->jumlah_soal, $ujian->jumlah_opsi);

        return view('ujian.cetak-ljk', compact('ujian', 'siswas', 'qrCodes', 'bubbleCoords'));
    }

    // =========================================================================
    // 3. SUB MENU: SESI UJIAN (MODE FULL SCREEN PROYEKTOR)
    // =========================================================================

    /**
     * Sub menu 3: Form Pengaturan Sesi Ujian (Pilih Kelas, Mapel, Guru Auto, Jam)
     */
    public function sesiIndex(Request $request)
    {
        $kelases = Classroom::active()->orderBy('tingkat')->orderBy('nama_kelas')->get();
        $mapels = MataPelajaran::with('lembaga')->orderBy('mata_pelajaran')->get();
        $gurus = User::where(function ($q) {
            $q->where('role', 'guru')->orWhere('is_guru_sekolah', true);
        })->where('is_active', true)->orderBy('name')->get();

        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])
            ->withCount('soals')
            ->latest('tanggal_ujian')
            ->get();

        $timeSlots = TimeSlot::active()->orderBy('jam_ke')->get();

        return view('ujian.sesi-index', compact('kelases', 'mapels', 'gurus', 'ujians', 'timeSlots'));
    }

    /**
     * Tampilan Mode Layar Penuh (Proyektor Kelas) dengan Timer per Nomor Soal
     */
    public function proyektor(Request $request, Ujian $ujian)
    {
        $ujian->load(['mapel', 'kelas', 'guru', 'soals']);

        $soals = $ujian->soals;

        // Jika belum ada butir soal di tabel ujian_soal, buat representasi fallback
        if ($soals->isEmpty()) {
            $dummy = [];
            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $dummy[] = new UjianSoal([
                    'id'          => $no,
                    'nomor_soal'  => $no,
                    'pertanyaan'  => "Soal nomor {$no}. Silakan kerjakan dan hitamkan bulatan jawaban pada lembar jawab LJK Anda.",
                    'opsi_a'      => 'Pilihan A',
                    'opsi_b'      => 'Pilihan B',
                    'opsi_c'      => 'Pilihan C',
                    'opsi_d'      => 'Pilihan D',
                    'opsi_e'      => $ujian->jumlah_opsi == 5 ? 'Pilihan E' : null,
                    'timer_detik' => $ujian->timer_default ?: 90,
                ]);
            }
            $soals = collect($dummy);
        }

        $activeSlot = $request->input('jam_ujian', 'Jam Sesi Ujian');

        return view('ujian.proyektor', compact('ujian', 'soals', 'activeSlot'));
    }

    // =========================================================================
    // 4. SUB MENU: KOREKSI UJIAN (OMR + QR CODE BATCH SCAN)
    // =========================================================================

    /**
     * Sub menu 4: Halaman Koreksi LJK
     */
    public function koreksiIndex(Request $request)
    {
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])
            ->withCount('hasils')
            ->latest('tanggal_ujian')
            ->get();

        $selectedUjianId = $request->input('ujian_id') ?: ($ujians->first()?->id ?? null);
        $ujian = $selectedUjianId ? Ujian::with(['mapel', 'kelas', 'guru'])->withCount('hasils')->find($selectedUjianId) : null;

        $siswasKelas = collect();
        if ($ujian && $ujian->kelas_id && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $siswasKelas = Siswa::where(function ($q) use ($namaKelas) {
                $q->where('kelas', $namaKelas)
                  ->orWhereHas('pendaftaran', fn($sq) => $sq->where('kelas', $namaKelas));
            })->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']);
        }
        if ($siswasKelas->isEmpty()) {
            $siswasKelas = Siswa::orderBy('nama_lengkap')->take(100)->get(['id', 'nama_lengkap']);
        }

        return view('ujian.upload-scan', compact('ujians', 'ujian', 'siswasKelas'));
    }

    /**
     * Upload scan LJK untuk satu ujian
     */
    public function uploadScan(Ujian $ujian)
    {
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])->withCount('hasils')->latest('tanggal_ujian')->get();

        $siswasKelas = collect();
        if ($ujian && $ujian->kelas_id && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $siswasKelas = Siswa::where(function ($q) use ($namaKelas) {
                $q->where('kelas', $namaKelas)
                  ->orWhereHas('pendaftaran', fn($sq) => $sq->where('kelas', $namaKelas));
            })->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']);
        }
        if ($siswasKelas->isEmpty()) {
            $siswasKelas = Siswa::orderBy('nama_lengkap')->take(100)->get(['id', 'nama_lengkap']);
        }

        return view('ujian.upload-scan', compact('ujians', 'ujian', 'siswasKelas'));
    }

    /**
     * Proses Pemindaian File PDF / Gambar (Sequential Batch Processing & Per-Page Client-Rendered JPEG)
     */
    public function processScan(Request $request, Ujian $ujian)
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(120);
        }

        // Tangani kasus ukuran file melebihi batas upload PHP server sebelum validasi Laravel
        $rawError = $_FILES['file_scan']['error'] ?? UPLOAD_ERR_OK;
        if (in_array($rawError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $maxUpload = ini_get('upload_max_filesize') ?: '2M';
            return response()->json([
                'success' => false,
                'message' => "Ukuran file melebihi batas upload server ({$maxUpload}). Silakan muat ulang halaman (Ctrl+F5) agar fitur kompresi otomatis per halaman aktif.",
            ], 422);
        }

        $request->validate([
            'file_scan'   => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:51200',
            'page_number' => 'nullable|integer|min:1|max:500',
            'qr_payload'  => 'nullable|string|max:1000',
        ], [
            'file_scan.required' => 'Berkas hasil scan wajib dipilih.',
            'file_scan.uploaded' => 'Berkas gagal diunggah ke server. Pastikan ukuran berkas tidak melebihi batas upload server (' . (ini_get('upload_max_filesize') ?: '2M') . ').',
            'file_scan.mimes'    => 'Format berkas harus berupa PDF, JPG, JPEG, atau PNG.',
            'file_scan.max'      => 'Ukuran berkas maksimal 50 MB.',
        ]);

        $file = $request->file('file_scan');
        $tempPath = $file->getRealPath();
        $pageNumber = max(1, (int) $request->input('page_number', 1));
        $qrPayload = $request->input('qr_payload');

        $scanner = new OmrScannerService();

        try {
            $result = $scanner->processFile($ujian, $tempPath, null, $pageNumber, is_string($qrPayload) ? $qrPayload : null);

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

    // =========================================================================
    // 5. SUB MENU: NILAI UJIAN & ANALISIS BUTIR SOAL
    // =========================================================================

    /**
     * Sub menu 5: Rekapitulasi Nilai & Analisis Butir Soal
     */
    public function nilaiIndex(Request $request)
    {
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])
            ->withCount('hasils')
            ->latest('tanggal_ujian')
            ->get();

        $selectedUjianId = $request->input('ujian_id') ?: ($ujians->first()?->id ?? null);
        $ujian = $selectedUjianId ? Ujian::with(['mapel', 'kelas', 'guru', 'kuncis', 'hasils'])->find($selectedUjianId) : null;

        if (!$ujian && $ujians->isNotEmpty()) {
            $ujian = $ujians->first();
        }

        if (!$ujian) {
            return view('ujian.hasil-empty', compact('ujians'));
        }

        return $this->hasil($ujian);
    }

    /**
     * Dashboard Rekap Nilai Ujian Tertentu
     */
    public function hasil(Ujian $ujian)
    {
        $ujians = Ujian::with(['mapel', 'kelas', 'guru'])->withCount('hasils')->latest('tanggal_ujian')->get();
        $ujian->load(['mapel', 'kelas', 'guru']);

        $hasils = UjianHasil::with('siswa')
            ->where('ujian_id', $ujian->id)
            ->join('siswas', 'siswas.id', '=', 'ujian_hasil.siswa_id')
            ->orderBy('siswas.nama_lengkap')
            ->select('ujian_hasil.*')
            ->paginate(30);

        $kuncis = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $statistik = $ujian->statistik;
        $analisis = $ujian->analisis_soal;

        $siswaList = collect();
        if ($ujian->kelas_id && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $siswaList = Siswa::where(function ($q) use ($namaKelas) {
                $q->where('kelas', $namaKelas)
                  ->orWhereHas('pendaftaran', fn($sq) => $sq->where('kelas', $namaKelas));
            })->orderBy('nama_lengkap')->get(['id', 'nama_lengkap']);
        }
        if ($siswaList->isEmpty()) {
            $siswaList = Siswa::orderBy('nama_lengkap')->take(100)->get(['id', 'nama_lengkap']);
        }

        return view('ujian.hasil', compact('ujians', 'ujian', 'hasils', 'kuncis', 'statistik', 'analisis', 'siswaList'));
    }

    /**
     * Update Koreksi Manual oleh Guru
     */
    public function updateHasilManual(Request $request, Ujian $ujian, UjianHasil $hasil)
    {
        if ($request->filled('siswa_id')) {
            $newSiswaId = (int) $request->input('siswa_id');
            if ($newSiswaId > 0 && $newSiswaId !== (int) $hasil->siswa_id) {
                UjianHasil::where('ujian_id', $ujian->id)
                    ->where('siswa_id', $newSiswaId)
                    ->where('id', '!=', $hasil->id)
                    ->delete();
                $hasil->siswa_id = $newSiswaId;
            }
        }

        $currentAnswers = $hasil->jawaban_siswa ?? [];

        if ($request->has('jawaban') && is_array($request->input('jawaban'))) {
            $inputAnswers = $request->input('jawaban');
            foreach ($inputAnswers as $no => $ans) {
                $noInt = (int)$no;
                if ($noInt >= 1 && $noInt <= $ujian->jumlah_soal) {
                    $ansClean = trim((string)$ans);
                    $currentAnswers[$noInt] = $ansClean !== '' ? strtoupper($ansClean) : null;
                }
            }
        } else {
            $validated = $request->validate([
                'nomor_soal' => 'required|integer|min:1|max:' . $ujian->jumlah_soal,
                'jawaban'    => 'nullable|string|in:A,B,C,D,E,GANDA,null',
            ]);

            $nomor = $validated['nomor_soal'];
            $jawabanBaru = $validated['jawaban'] === 'null' ? null : $validated['jawaban'];
            $currentAnswers[$nomor] = $jawabanBaru;
        }

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
            'siswa_id'      => $hasil->siswa_id,
            'jawaban_siswa' => $currentAnswers,
            'jumlah_benar'  => $benar,
            'jumlah_salah'  => $salah,
            'jumlah_kosong' => $kosong,
            'nilai_akhir'   => $nilai,
            'status_scan'   => $hasGanda ? 'perlu_cek_manual' : 'valid',
            'catatan_scan'  => $hasGanda ? 'Masih terdapat jawaban ganda.' : 'Telah diverifikasi manual oleh Guru.',
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => "Jawaban berhasil diperbarui. Nilai akhir: $nilai",
                'data'    => $hasil->fresh(),
            ]);
        }

        return redirect()->route('ujian.hasil', $ujian->id)->with('success', 'Hasil koreksi siswa berhasil diperbarui.');
    }

    /**
     * Hubungkan atau ubah siswa hasil scan secara manual
     */
    public function assignHasilSiswa(Request $request, Ujian $ujian)
    {
        $validated = $request->validate([
            'hasil_id'      => 'nullable|integer',
            'siswa_id'      => 'required|exists:siswas,id',
            'jawaban'       => 'nullable|array',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $hasil = null;

        if (!empty($validated['hasil_id'])) {
            $hasil = UjianHasil::where('ujian_id', $ujian->id)->find($validated['hasil_id']);
        }

        if ($hasil) {
            UjianHasil::where('ujian_id', $ujian->id)
                ->where('siswa_id', $siswa->id)
                ->where('id', '!=', $hasil->id)
                ->delete();

            $hasil->siswa_id = $siswa->id;
            $hasil->status_scan = 'valid';
            $hasil->catatan_scan = 'Telah diverifikasi manual oleh Guru (' . $siswa->nama_lengkap . ')';
            $hasil->save();
        } else {
            $currentAnswers = $validated['jawaban'] ?? [];
            $kunciMap = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
            $bobotMap = $ujian->kuncis->pluck('bobot', 'nomor_soal')->all();
            $totalSkorMaks = array_sum($bobotMap) ?: ($ujian->jumlah_soal * ($ujian->bobot_benar ?: 1.0));

            $benar = 0; $salah = 0; $kosong = 0; $totalDidapat = 0.0;
            for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
                $ans = $currentAnswers[$no] ?? null;
                $k = $kunciMap[$no] ?? null;
                $w = (float)($bobotMap[$no] ?? $ujian->bobot_benar ?? 1.0);
                if ($ans === null) {
                    $kosong++;
                } elseif ($k && strtoupper($ans) === strtoupper($k)) {
                    $benar++;
                    $totalDidapat += $w;
                } else {
                    $salah++;
                }
            }
            $nilai = $totalSkorMaks > 0 ? round(($totalDidapat / $totalSkorMaks) * 100, 2) : 0.0;

            $hasil = UjianHasil::updateOrCreate(
                [
                    'ujian_id' => $ujian->id,
                    'siswa_id' => $siswa->id,
                ],
                [
                    'jawaban_siswa' => $currentAnswers,
                    'jumlah_benar'  => $benar,
                    'jumlah_salah'  => $salah,
                    'jumlah_kosong' => $kosong,
                    'nilai_akhir'   => $nilai,
                    'status_scan'   => 'valid',
                    'catatan_scan'  => 'Telah diverifikasi manual oleh Guru (' . $siswa->nama_lengkap . ')',
                    'waktu_scan'    => now(),
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Siswa berhasil dihubungkan: ' . $siswa->nama_lengkap,
            'data'    => [
                'hasil_id'    => $hasil->id,
                'siswa_id'    => $siswa->id,
                'nama_siswa'  => $siswa->nama_lengkap,
                'status_scan' => 'valid',
                'nilai_akhir' => $hasil->nilai_akhir,
            ],
        ]);
    }

    /**
     * Hapus data hasil ujian seorang siswa
     */
    public function destroyHasil(Ujian $ujian, UjianHasil $hasil)
    {
        if ($hasil->ujian_id !== $ujian->id) {
            abort(404);
        }
        $hasil->delete();

        return redirect()->route('ujian.hasil', $ujian->id)
            ->with('success', 'Data hasil scan siswa berhasil dihapus.');
    }

    /**
     * Export Hasil Nilai ke Excel (.xlsx)
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

        $sheet->setCellValue('A1', 'YAYASAN PONDOK PESANTREN AL-MUTTAQIN');
        $sheet->setCellValue('A2', 'REKAPITULASI HASIL UJIAN & KOREKSI LJK');
        $sheet->setCellValue('A4', 'Nama Ujian: ' . $ujian->nama_ujian);
        $sheet->setCellValue('A5', 'Mata Pelajaran: ' . ($ujian->mapel?->mata_pelajaran ?? '-'));
        $sheet->setCellValue('D4', 'Kelas: ' . ($ujian->kelas?->nama_kelas ?? '-'));
        $sheet->setCellValue('D5', 'Tanggal: ' . $ujian->tanggal_ujian?->format('d/m/Y'));

        $sheet->getStyle('A1:A2')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A4:D5')->getFont()->setBold(true);

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

            if ($h->status_scan !== 'valid') {
                $sheet->getStyle('H' . $row)->getFont()->getColor()->setRGB('D97706');
            }

            $no++;
            $row++;
        }

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

    /**
     * Hitung ulang skor seluruh siswa
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
}
