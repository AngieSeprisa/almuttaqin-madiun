<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\Classroom;
use App\Models\MataPelajaran;
use App\Models\Siswa;
use App\Models\Ujian;
use App\Models\UjianHasil;
use App\Models\UjianKunci;
use App\Models\User;
use App\Services\Omr\LjkTemplateService;
use App\Services\Omr\OmrScannerService;
use App\Services\Omr\QrCodeGenerator;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UjianOmrTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Classroom $kelas;
    private MataPelajaran $mapel;
    private Siswa $siswa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);

        $this->admin = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $lembaga = \App\Models\Lembaga::where('kode', 'smp')->first() ?: \App\Models\Lembaga::first();

        $this->kelas = Classroom::create([
            'lembaga_id' => $lembaga->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => '7A',
            'tingkat'    => 7,
            'is_active'  => true,
        ]);

        $this->mapel = MataPelajaran::first() ?: MataPelajaran::create([
            'lembaga_id'     => $lembaga->id,
            'kode'           => 'MTK-7',
            'mata_pelajaran' => 'Matematika',
        ]);

        $pendaftaran = \App\Models\Pendaftaran::create([
            'nama_lengkap'  => 'Ahmad Santri Uji',
            'nisn'          => '1234567890',
            'nik'           => '3500000000000001',
            'jenjang'       => 'SMP',
            'kelas'         => '7A',
            'jenis_kelamin' => 'Laki-laki',
        ]);

        $this->siswa = Siswa::create([
            'nama_lengkap'   => 'Ahmad Santri Uji',
            'pendaftaran_id' => $pendaftaran->id,
            'kelas'          => '7A',
            'kamar'          => 'Abu Bakar',
            'jenis_kelamin'  => 'L',
            'status'         => 'aktif',
        ]);
    }

    public function test_menu_ujian_terdaftar_di_sidebar_dan_matriks_hak_akses(): void
    {
        // 1. Menu Ujian terdaftar di matriks hak akses dengan 5 sub menunya
        $menuGroups = UserController::systemMenuGroups();
        $this->assertArrayHasKey('Ujian', $menuGroups);
        $this->assertArrayHasKey('ujian_soal', $menuGroups['Ujian']);
        $this->assertArrayHasKey('ujian_ljk', $menuGroups['Ujian']);
        $this->assertArrayHasKey('ujian_sesi', $menuGroups['Ujian']);
        $this->assertArrayHasKey('ujian_koreksi', $menuGroups['Ujian']);
        $this->assertArrayHasKey('ujian_nilai', $menuGroups['Ujian']);

        // 2. Tampil di sidebar sebagai menu utama tersendiri dengan 5 sub menu
        $res = $this->actingAs($this->admin)->get(route('beranda'));
        $res->assertStatus(200);
        $res->assertSee('Soal Ujian');
        $res->assertSee('Sesi Ujian');
        $res->assertSee('Koreksi Ujian');
        $res->assertSee('Nilai Ujian');
        $res->assertSee(route('ujian.soal'));
    }

    public function test_crud_ujian_dan_inisialisasi_kunci_jawaban_otomatis(): void
    {
        // 1. Akses halaman index ujian
        $resIndex = $this->actingAs($this->admin)->get(route('ujian.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('+ Buat Ujian Baru');

        // 2. Buka form create
        $resCreate = $this->actingAs($this->admin)->get(route('ujian.create'));
        $resCreate->assertStatus(200);
        $resCreate->assertSee('Buat Ujian Baru');

        // 3. Simpan ujian baru dengan 20 butir soal
        $postData = [
            'kode_ujian'    => 'UAS-MTK-7A',
            'nama_ujian'    => 'Penilaian Akhir Semester Matematika 7A',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 20,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 1.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ];

        $resStore = $this->actingAs($this->admin)->post(route('ujian.store'), $postData);
        $ujian = Ujian::where('kode_ujian', 'UAS-MTK-7A')->firstOrFail();
        $resStore->assertRedirect(route('ujian.kunci', $ujian->id));

        // Verifikasi tabel ujian_kunci otomatis dibuat sebanyak 20 butir nomor 1-20
        $this->assertCount(20, $ujian->kuncis);
        $this->assertEquals(1, $ujian->kuncis->first()->nomor_soal);
        $this->assertEquals(20, $ujian->kuncis->last()->nomor_soal);
    }

    public function test_kunci_jawaban_interaktif_dan_rekap_otomatis(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'PAS-IPA-7',
            'nama_ujian'    => 'PAS IPA Terpadu Kelas 7',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 5,
            'jumlah_opsi'   => 5,
            'bobot_benar'   => 2.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        // Akses halaman kunci
        $resKunci = $this->actingAs($this->admin)->get(route('ujian.kunci', $ujian->id));
        $resKunci->assertStatus(200);
        $resKunci->assertSee('Kunci Jawaban: PAS IPA Terpadu Kelas 7');
        $resKunci->assertSee('+ Tempel Kunci Cepat');

        // Update kunci jawaban nomor 1 s.d 5: 1:B, 2:C, 3:A, 4:D, 5:E
        $resUpdate = $this->actingAs($this->admin)->post(route('ujian.update-kunci', $ujian->id), [
            'kunci' => [
                1 => 'B',
                2 => 'C',
                3 => 'A',
                4 => 'D',
                5 => 'E',
            ],
            'bobot' => [
                1 => 2.0,
                2 => 2.0,
                3 => 2.0,
                4 => 2.0,
                5 => 2.0,
            ],
        ]);

        $resUpdate->assertRedirect();
        $this->assertDatabaseHas('ujian_kunci', [
            'ujian_id'      => $ujian->id,
            'nomor_soal'    => 1,
            'kunci_jawaban' => 'B',
        ]);
        $this->assertDatabaseHas('ujian_kunci', [
            'ujian_id'      => $ujian->id,
            'nomor_soal'    => 5,
            'kunci_jawaban' => 'E',
        ]);

        // Verifikasi form submit dengan method PUT (seperti pada form HTML blade) tidak menyebabkan error 405
        $resPut = $this->actingAs($this->admin)->put(route('ujian.update-kunci', $ujian->id), [
            'kunci' => [1 => 'C', 2 => 'D'],
            'bobot' => [1 => 2.0, 2 => 2.0],
        ]);
        $resPut->assertRedirect();
        $this->assertDatabaseHas('ujian_kunci', [
            'ujian_id'      => $ujian->id,
            'nomor_soal'    => 1,
            'kunci_jawaban' => 'C',
        ]);
    }

    public function test_cetak_ljk_a4_memiliki_4_fiducial_markers_dan_qr_code(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'LJK-TEST-01',
            'nama_ujian'    => 'Uji Cetak LJK',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 40,
            'jumlah_opsi'   => 5,
            'bobot_benar'   => 1.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        $res = $this->actingAs($this->admin)->get(route('ujian.cetak-ljk', $ujian->id));
        $res->assertStatus(200);

        // Verifikasi elemen cetak LJK A4
        $res->assertSee('Ahmad Santri Uji');
        $res->assertSee('1234567890');
        $res->assertSee('fiducial-marker marker-tl', false);
        $res->assertSee('fiducial-marker marker-tr', false);
        $res->assertSee('fiducial-marker marker-bl', false);
        $res->assertSee('fiducial-marker marker-br', false);
        $res->assertSee('<svg class="qr-svg"', false);
    }

    public function test_omr_scanner_engine_bilinear_and_scoring(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'OMR-TEST-02',
            'nama_ujian'    => 'Simulasi Ujian OMR',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 5,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 1.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        // Inisialisasi Kunci: 1:A, 2:B, 3:C, 4:D, 5:A
        foreach ([1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D', 5 => 'A'] as $no => $k) {
            UjianKunci::create([
                'ujian_id'      => $ujian->id,
                'nomor_soal'    => $no,
                'kunci_jawaban' => $k,
                'bobot'         => 1.0,
            ]);
        }

        // Buat file gambar LJK tiruan sintetis (800x1131 px)
        $w = 800;
        $h = 1131;
        $img = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefilledrectangle($img, 0, 0, $w, $h, $white);

        // Gambar 4 Fiducial Markers pada rasio millimeter template (12mm margin, 10mm size)
        $mLeft = (int)round((12.0 / 210.0) * $w);
        $mTop = (int)round((12.0 / 297.0) * $h);
        $mRight = (int)round(((210.0 - 12.0) / 210.0) * $w);
        $mBottom = (int)round(((297.0 - 12.0) / 297.0) * $h);
        $mSizeW = (int)round((10.0 / 210.0) * $w);
        $mSizeH = (int)round((10.0 / 297.0) * $h);

        // TL, TR, BL, BR
        imagefilledrectangle($img, $mLeft, $mTop, $mLeft + $mSizeW, $mTop + $mSizeH, $black);
        imagefilledrectangle($img, $mRight - $mSizeW, $mTop, $mRight, $mTop + $mSizeH, $black);
        imagefilledrectangle($img, $mLeft, $mBottom - $mSizeH, $mLeft + $mSizeW, $mBottom, $black);
        imagefilledrectangle($img, $mRight - $mSizeW, $mBottom - $mSizeH, $mRight, $mBottom, $black);

        // Ambil koordinat bulatan untuk 5 soal
        $bubbleCoords = LjkTemplateService::getBubbleCoordinates(5, 4);

        // Pusat titik hitam marker untuk transformasi
        $tlX = $mLeft + ($mSizeW / 2);
        $tlY = $mTop + ($mSizeH / 2);
        $trX = $mRight - ($mSizeW / 2);
        $blY = $mBottom - ($mSizeH / 2);

        // Isi jawaban siswa secara buatan:
        // Soal 1 -> A (Benar)
        // Soal 2 -> B (Benar)
        // Soal 3 -> A (Salah, kunci C)
        // Soal 4 -> Kosong
        // Soal 5 -> Isi A dan B (GANDA)
        $answersToFill = [
            1 => ['A'],
            2 => ['B'],
            3 => ['A'],
            4 => [],
            5 => ['A', 'B'], // Ganda
        ];

        foreach ($answersToFill as $no => $letters) {
            foreach ($letters as $letter) {
                if (isset($bubbleCoords[$no][$letter])) {
                    $norm = $bubbleCoords[$no][$letter];
                    $px = (int)round($tlX + ($norm['u'] * ($trX - $tlX)));
                    $py = (int)round($tlY + ($norm['v'] * ($blY - $tlY)));
                    imagefilledellipse($img, $px, $py, 22, 22, $black);
                }
            }
        }

        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_omr_' . uniqid() . '.png';
        imagepng($img, $tempPath);
        imagedestroy($img);

        $this->assertFileExists($tempPath);

        // Jalankan OMR Scanner Service
        $scanner = new OmrScannerService();
        $result = $scanner->processFile($ujian, $tempPath);

        // Hapus file temporary
        if (file_exists($tempPath)) {
            @unlink($tempPath);
        }

        $this->assertTrue($result['total_halaman'] >= 1);
        $this->assertEquals(1, count($result['rincian']));

        $rincian = $result['rincian'][0];
        $this->assertEquals(2, $rincian['jumlah_benar']); // Soal 1 & 2
        $this->assertEquals(2, $rincian['jumlah_salah']); // Soal 3 (salah) & Soal 5 (ganda)
        $this->assertEquals(1, $rincian['jumlah_kosong']); // Soal 4 (kosong)
        $this->assertEquals('perlu_cek_manual', $rincian['status_scan']); // Karena soal 5 GANDA
        $this->assertStringContainsString('ganda', strtolower($rincian['catatan_scan']));
    }

    public function test_koreksi_manual_oleh_guru_via_modal(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'MANUAL-TEST-03',
            'nama_ujian'    => 'Koreksi Manual Test',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 4,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 25.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        foreach ([1 => 'A', 2 => 'B', 3 => 'C', 4 => 'D'] as $no => $k) {
            UjianKunci::create([
                'ujian_id'      => $ujian->id,
                'nomor_soal'    => $no,
                'kunci_jawaban' => $k,
                'bobot'         => 25.0,
            ]);
        }

        $hasil = UjianHasil::create([
            'ujian_id'      => $ujian->id,
            'siswa_id'      => $this->siswa->id,
            'jawaban_siswa' => [1 => 'A', 2 => 'B', 3 => 'GANDA', 4 => null],
            'jumlah_benar'  => 2,
            'jumlah_salah'  => 1,
            'jumlah_kosong' => 1,
            'nilai_akhir'   => 50.00,
            'status_scan'   => 'perlu_cek_manual',
            'catatan_scan'  => 'Soal 3 terdeteksi ganda',
            'waktu_scan'    => now(),
        ]);

        // Guru melakukan koreksi manual nomor 3 menjadi 'C' (Benar)
        $res = $this->actingAs($this->admin)->postJson(
            route('ujian.update-hasil-manual', [$ujian->id, $hasil->id]),
            [
                'nomor_soal' => 3,
                'jawaban'    => 'C',
            ]
        );

        $res->assertStatus(200);
        $res->assertJson(['success' => true]);

        $hasil->refresh();
        $this->assertEquals('C', $hasil->jawaban_siswa[3]);
        $this->assertEquals(3, $hasil->jumlah_benar);
        $this->assertEquals(0, $hasil->jumlah_salah);
        $this->assertEquals(1, $hasil->jumlah_kosong);
        $this->assertEquals(75.00, (float)$hasil->nilai_akhir);
        $this->assertEquals('valid', $hasil->status_scan);
    }

    public function test_ekspor_hasil_ujian_ke_excel(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'EXP-TEST-04',
            'nama_ujian'    => 'Ujian Ekspor Excel',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 10,
            'jumlah_opsi'   => 5,
            'bobot_benar'   => 10.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        UjianHasil::create([
            'ujian_id'      => $ujian->id,
            'siswa_id'      => $this->siswa->id,
            'jawaban_siswa' => [1 => 'A'],
            'jumlah_benar'  => 10,
            'jumlah_salah'  => 0,
            'jumlah_kosong' => 0,
            'nilai_akhir'   => 100.00,
            'status_scan'   => 'valid',
            'catatan_scan'  => 'Sempurna',
            'waktu_scan'    => now(),
        ]);

        $res = $this->actingAs($this->admin)->get(route('ujian.export-excel', $ujian->id));
        $res->assertStatus(200);
        $this->assertStringContainsString('spreadsheetml.sheet', $res->headers->get('content-type'));
    }

    public function test_submenu_soal_ujian_dan_template_download(): void
    {
        // 1. Halaman Soal Ujian
        $res = $this->actingAs($this->admin)->get(route('ujian.soal'));
        $res->assertStatus(200);
        $res->assertSee('Soal Ujian');
        $res->assertSee('Unduh Template Word (.docx)');

        // 2. Unduh template Word (.docx)
        $resTemplate = $this->actingAs($this->admin)->get(route('ujian.download-template'));
        $resTemplate->assertStatus(200);
        $this->assertStringContainsString('wordprocessingml.document', $resTemplate->headers->get('content-type'));
    }

    public function test_submenu_ljk_dan_peraturan(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'LJK-SUB-01',
            'nama_ujian'    => 'Uji Submenu LJK',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 25,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 1.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        // 1. Halaman LJK
        $res = $this->actingAs($this->admin)->get(route('ujian.ljk'));
        $res->assertStatus(200);
        $res->assertSee('Cetak Lembar Jawab Komputer (LJK)');

        // 2. Update Peraturan Ujian
        $resPeraturan = $this->actingAs($this->admin)->post(route('ujian.update-peraturan', $ujian->id), [
            'peraturan_ujian' => "1. Berdoalah sebelum mengerjakan.\n2. Dilarang berbuat curang.",
        ]);
        $resPeraturan->assertRedirect();
        $this->assertDatabaseHas('ujian', [
            'id'              => $ujian->id,
            'peraturan_ujian' => "1. Berdoalah sebelum mengerjakan.\n2. Dilarang berbuat curang.",
        ]);
    }

    public function test_submenu_sesi_ujian_dan_proyektor_fullscreen(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'SESI-SUB-01',
            'nama_ujian'    => 'Uji Sesi Proyektor',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 5,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 1.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        // 1. Halaman pemilihan sesi ujian
        $res = $this->actingAs($this->admin)->get(route('ujian.sesi'));
        $res->assertStatus(200);
        $res->assertSee('Persiapan Sesi Ujian di Kelas');
        $res->assertSee('Mulai Sesi Ujian');

        // 2. Mode Proyektor Full Screen
        $resProyektor = $this->actingAs($this->admin)->get(route('ujian.proyektor', $ujian->id));
        $resProyektor->assertStatus(200);
        $resProyektor->assertSee('Uji Sesi Proyektor');
    }

    public function test_submenu_nilai_dan_analisis_butir_soal(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'NILAI-SUB-01',
            'nama_ujian'    => 'Uji Analisis Soal',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 2,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 50.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        UjianKunci::create(['ujian_id' => $ujian->id, 'nomor_soal' => 1, 'kunci_jawaban' => 'A', 'bobot' => 50.0]);
        UjianKunci::create(['ujian_id' => $ujian->id, 'nomor_soal' => 2, 'kunci_jawaban' => 'B', 'bobot' => 50.0]);

        UjianHasil::create([
            'ujian_id'      => $ujian->id,
            'siswa_id'      => $this->siswa->id,
            'jawaban_siswa' => [1 => 'A', 2 => 'C'],
            'jumlah_benar'  => 1,
            'jumlah_salah'  => 1,
            'jumlah_kosong' => 0,
            'nilai_akhir'   => 50.00,
            'status_scan'   => 'valid',
            'waktu_scan'    => now(),
        ]);

        // Akses menu nilai
        $resNilai = $this->actingAs($this->admin)->get(route('ujian.nilai', ['ujian_id' => $ujian->id]));
        $resNilai->assertStatus(200);
        $resNilai->assertSee('Daftar Nilai Siswa');
        $resNilai->assertSee('Analisis Butir Soal');

        // Analisis butir soal accessor
        $analisis = $ujian->analisis_soal;
        $this->assertEquals(1, $analisis[1]['jumlah_benar']);
        $this->assertEquals(0, $analisis[1]['jumlah_salah']);
        $this->assertEquals(100.0, $analisis[1]['persen_benar']);
        $this->assertEquals('Mudah', $analisis[1]['tingkat_kesukaran']);

        $this->assertEquals(0, $analisis[2]['jumlah_benar']);
        $this->assertEquals(1, $analisis[2]['jumlah_salah']);
        $this->assertEquals(0.0, $analisis[2]['persen_benar']);
        $this->assertEquals('Sukar', $analisis[2]['tingkat_kesukaran']);
    }

    public function test_process_scan_per_halaman_dengan_qr_payload_dan_kalibrasi_header_dinamis(): void
    {
        // Buat siswa kedua yang secara alfabetis berada di urutan belakang (memastikan QR payload diprioritaskan)
        $siswaKedua = Siswa::create([
            'nama_lengkap'  => 'Zaki Nova Mutthalib',
            'nisn'          => '9998887776',
            'jenis_kelamin' => 'L',
            'kelas'         => $this->kelas->nama_kelas,
            'lembaga'       => 'SMP',
        ]);

        $ujian = Ujian::create([
            'kode_ujian'    => 'UJN-202611-099',
            'nama_ujian'    => 'SAS Informatika Kalibrasi',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 10,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 10.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        for ($no = 1; $no <= 10; $no++) {
            UjianKunci::create([
                'ujian_id'      => $ujian->id,
                'nomor_soal'    => $no,
                'kunci_jawaban' => 'C',
                'bobot'         => 10.0,
            ]);
        }

        // Render LJK simulasi A4 (1240 x 1754 px) dengan marker abu-abu gelap & bar header hitam bergeser +8mm ke bawah
        $w = 1240;
        $h = 1754;
        $img = imagecreatetruecolor($w, $h);
        $paper = imagecolorallocate($img, 245, 245, 245);
        $markerColor = imagecolorallocate($img, 65, 65, 65); // Abu-abu gelap seperti hasil scan printer laser
        $headerBlack = imagecolorallocate($img, 30, 30, 30);
        $pencilFill = imagecolorallocate($img, 55, 55, 55);
        $bubbleRing = imagecolorallocate($img, 140, 140, 140);
        imagefilledrectangle($img, 0, 0, $w, $h, $paper);

        // 4 Fiducial Markers (10x10mm pada margin 12mm)
        $mmToPxX = $w / 210.0;
        $mmToPxY = $h / 297.0;
        $mSizeW = (int) round(10.0 * $mmToPxX);
        $mSizeH = (int) round(10.0 * $mmToPxY);
        $tlLeft = (int) round(12.0 * $mmToPxX);
        $tlTop  = (int) round(12.0 * $mmToPxY);
        $trLeft = (int) round(188.0 * $mmToPxX);
        $blTop  = (int) round(275.0 * $mmToPxY);

        imagefilledrectangle($img, $tlLeft, $tlTop, $tlLeft + $mSizeW, $tlTop + $mSizeH, $markerColor);
        imagefilledrectangle($img, $trLeft, $tlTop, $trLeft + $mSizeW, $tlTop + $mSizeH, $markerColor);
        imagefilledrectangle($img, $tlLeft, $blTop, $tlLeft + $mSizeW, $blTop + $mSizeH, $markerColor);
        imagefilledrectangle($img, $trLeft, $blTop, $trLeft + $mSizeW, $blTop + $mSizeH, $markerColor);

        $tlCx = $tlLeft + ($mSizeW / 2.0);
        $tlCy = $tlTop + ($mSizeH / 2.0);
        $refW = ($trLeft + ($mSizeW / 2.0)) - $tlCx;
        $refH = ($blTop + ($mSizeH / 2.0)) - $tlCy;

        // Pergeseran vertikal +8mm (simulasi kotak peraturan ujian yang lebih panjang)
        $shiftMm = 8.0;
        $deltaV = $shiftMm / LjkTemplateService::REF_HEIGHT_MM;

        // Gambar bar hitam header kolom (.omr-col-header tinggi 5mm) di atas grid yang bergeser +8mm
        $headerBottomV = LjkTemplateService::getNominalHeaderBottomV() + $deltaV;
        $headerTopV = $headerBottomV - (5.0 / LjkTemplateService::REF_HEIGHT_MM);
        foreach (LjkTemplateService::getColumnHeaderBands(10) as $band) {
            $hx1 = (int) round($tlCx + $band['u1'] * $refW);
            $hx2 = (int) round($tlCx + $band['u2'] * $refW);
            $hy1 = (int) round($tlCy + $headerTopV * $refH);
            $hy2 = (int) round($tlCy + $headerBottomV * $refH);
            imagefilledrectangle($img, $hx1, $hy1, $hx2, $hy2, $headerBlack);
        }

        // Gambar bulatan kosong untuk semua opsi dan hitamkan opsi C untuk seluruh 10 soal
        $bubbleCoords = LjkTemplateService::getBubbleCoordinates(10, 4);
        $diam = (int) round(4.5 * $mmToPxX);
        foreach ($bubbleCoords as $no => $opsiList) {
            foreach ($opsiList as $opsi => $coord) {
                $px = (int) round($tlCx + ($coord['u'] * $refW));
                $py = (int) round($tlCy + (($coord['v'] + $deltaV) * $refH));
                imageellipse($img, $px, $py, $diam, $diam, $bubbleRing);
                if ($opsi === 'C') {
                    imagefilledellipse($img, $px, $py, $diam - 4, $diam - 4, $pencilFill);
                }
            }
        }

        $tmpJpg = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_scan_ljk_' . uniqid() . '.jpg';
        imagejpeg($img, $tmpJpg, 85);
        imagedestroy($img);

        $uploadedFile = new \Illuminate\Http\UploadedFile($tmpJpg, 'ljk_page_1.jpg', 'image/jpeg', null, true);
        $qrPayload = LjkTemplateService::makeQrPayload($ujian, $siswaKedua);

        $response = $this->actingAs($this->admin)->postJson(route('ujian.process-scan', $ujian->id), [
            'file_scan'   => $uploadedFile,
            'page_number' => 1,
            'qr_payload'  => $qrPayload,
        ]);

        @unlink($tmpJpg);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.total_halaman', 1)
            ->assertJsonPath('data.berhasil', 1)
            ->assertJsonPath('data.rincian.0.siswa_id', $siswaKedua->id)
            ->assertJsonPath('data.rincian.0.nama_siswa', 'Zaki Nova Mutthalib')
            ->assertJsonPath('data.rincian.0.jumlah_benar', 10)
            ->assertJsonPath('data.rincian.0.nilai_akhir', 100);
    }

    public function test_process_scan_tanpa_qr_tidak_keliru_menetapkan_siswa_pertama_abjad_dan_bisa_diassign_manual(): void
    {
        $siswaPertama = Siswa::create([
            'nama_lengkap' => 'Abdillah Wakhidul Akhir',
            'kelas'        => $this->kelas->nama_kelas,
        ]);
        $siswaNova = Siswa::create([
            'nama_lengkap' => 'Nova Abdul Mutthalib',
            'kelas'        => $this->kelas->nama_kelas,
        ]);

        $ujian = Ujian::create([
            'kode_ujian'    => 'UJN-TEST-NOVA',
            'nama_ujian'    => 'Ujian Tanpa Fallback Abjad',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 5,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 20.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        for ($no = 1; $no <= 5; $no++) {
            UjianKunci::create([
                'ujian_id'      => $ujian->id,
                'nomor_soal'    => $no,
                'kunci_jawaban' => 'A',
                'bobot'         => 20.0,
            ]);
        }

        $w = 1200; $h = 1700;
        $img = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        // 4 Fiducial markers
        imagefilledrectangle($img, 65, 65, 125, 125, $black);
        imagefilledrectangle($img, $w - 125, 65, $w - 65, 125, $black);
        imagefilledrectangle($img, 65, $h - 125, 125, $h - 65, $black);
        imagefilledrectangle($img, $w - 125, $h - 125, $w - 65, $h - 65, $black);

        $tmpJpg = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_nova_ljk_' . uniqid() . '.jpg';
        imagejpeg($img, $tmpJpg, 85);
        imagedestroy($img);

        $uploadedFile = new \Illuminate\Http\UploadedFile($tmpJpg, 'ljk_nova.jpg', 'image/jpeg', null, true);

        // Scan tanpa QR payload (misal QR tidak terbaca)
        $response = $this->actingAs($this->admin)->postJson(route('ujian.process-scan', $ujian->id), [
            'file_scan'   => $uploadedFile,
            'page_number' => 1,
            'qr_payload'  => null,
        ]);

        @unlink($tmpJpg);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rincian.0.siswa_id', null) // Tidak boleh otomatis mengambil Abdillah Wakhidul Akhir
            ->assertJsonPath('data.rincian.0.status_scan', 'perlu_cek_manual');

        // Pastikan Abdillah TIDAK mendapat record hasil ujian palsu
        $this->assertDatabaseMissing('ujian_hasil', [
            'ujian_id' => $ujian->id,
            'siswa_id' => $siswaPertama->id,
        ]);

        // Guru melakukan penetapan nama siswa manual ke Nova Abdul Mutthalib
        $assignRes = $this->actingAs($this->admin)->postJson(route('ujian.assign-hasil-siswa', $ujian->id), [
            'siswa_id' => $siswaNova->id,
            'jawaban'  => [1 => 'A', 2 => 'A', 3 => 'A', 4 => 'A', 5 => 'A'],
        ]);

        $assignRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.siswa_id', $siswaNova->id)
            ->assertJsonPath('data.nama_siswa', 'Nova Abdul Mutthalib');

        $this->assertDatabaseHas('ujian_hasil', [
            'ujian_id' => $ujian->id,
            'siswa_id' => $siswaNova->id,
        ]);
    }

    public function test_pilihan_cetak_ljk_qrcode_atau_barcode_1d_dan_deteksi_barcode_serta_ocr(): void
    {
        $siswaPertama = Siswa::create([
            'nama_lengkap' => 'Abdillah Wakhidul Akhir',
            'kelas'        => $this->kelas->nama_kelas,
            'status'       => 'aktif',
        ]);
        $siswaNova = Siswa::create([
            'nama_lengkap' => 'NOVA ABDUL MUTTHALIB',
            'kelas'        => $this->kelas->nama_kelas,
            'status'       => 'aktif',
        ]);

        $ujian = Ujian::create([
            'kode_ujian'    => 'UJN-202611-001',
            'nama_ujian'    => 'SAS Informatika',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 5,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 20.0,
            'tanggal_ujian' => now()->format('Y-m-d'),
            'status'        => 'aktif',
        ]);

        for ($no = 1; $no <= 5; $no++) {
            UjianKunci::create([
                'ujian_id'      => $ujian->id,
                'nomor_soal'    => $no,
                'kunci_jawaban' => 'A',
                'bobot'         => 20.0,
            ]);
        }

        // 1. Uji cetak LJK mode QR Code (Barcode disembunyikan)
        $resQr = $this->actingAs($this->admin)->get(route('ujian.cetak-ljk', ['ujian' => $ujian->id, 'tipe_kode' => 'qr']));
        $resQr->assertStatus(200);
        $resQr->assertSee('code-view-qr" style="width:28mm; height:28mm; display:flex;', false);
        $resQr->assertSee('code-view-barcode" style="width:42mm; height:20mm; display:none;', false);

        // 2. Uji cetak LJK mode Barcode 1D (QR Code disembunyikan)
        $resBc = $this->actingAs($this->admin)->get(route('ujian.cetak-ljk', ['ujian' => $ujian->id, 'tipe_kode' => 'barcode']));
        $resBc->assertStatus(200);
        $resBc->assertSee('code-view-qr" style="width:28mm; height:28mm; display:none;', false);
        $resBc->assertSee('code-view-barcode" style="width:42mm; height:20mm; display:flex;', false);
        $resBc->assertSee('<svg class="barcode-svg"', false);

        // 3. Uji pemindaian lembar LJK yang memiliki Barcode 1D (tanpa client qr_payload)
        $w = 1400; $h = 1925;
        $img = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        imagefill($img, 0, 0, $white);

        // 4 Fiducial markers (TL: 72,104; TR: 1215,104; BL: 72,1802; BR: 1215,1802)
        imagefilledrectangle($img, 42, 74, 102, 134, $black);
        imagefilledrectangle($img, 1185, 74, 1245, 134, $black);
        imagefilledrectangle($img, 42, 1772, 102, 1832, $black);
        imagefilledrectangle($img, 1185, 1772, 1245, 1832, $black);

        // Gambar Barcode 1D (Code 128B) di area kotak identitas kanan atas (u: 0.72..0.92, v: 0.02..0.08)
        $barcodePayload = LjkTemplateService::makeBarcodePayload($ujian, $siswaNova);
        $bars = \App\Services\Omr\QrCodeGenerator::encodeCode128B($barcodePayload);
        $bcStartX = 910;
        $bcStartY = 145;
        $bcHeight = 70;
        $scaleBar = 1;
        foreach ($bars as $bIdx => $isDark) {
            if ($isDark) {
                imagefilledrectangle($img, $bcStartX + ($bIdx * $scaleBar), $bcStartY, $bcStartX + (($bIdx + 1) * $scaleBar) - 1, $bcStartY + $bcHeight, $black);
            }
        }

        $tmpBcJpg = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test_bc_ljk_' . uniqid() . '.jpg';
        imagejpeg($img, $tmpBcJpg, 92);
        imagedestroy($img);

        $uploadedBcFile = new \Illuminate\Http\UploadedFile($tmpBcJpg, 'ljk_barcode.jpg', 'image/jpeg', null, true);
        $scanRes = $this->actingAs($this->admin)->postJson(route('ujian.process-scan', $ujian->id), [
            'file_scan'   => $uploadedBcFile,
            'page_number' => 1,
            'qr_payload'  => null,
        ]);
        @unlink($tmpBcJpg);

        $scanRes->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rincian.0.siswa_id', $siswaNova->id)
            ->assertJsonPath('data.rincian.0.nama_siswa', 'NOVA ABDUL MUTTHALIB');
    }

    public function test_ujian_pg_dan_essay_bobot_persen_dan_validasi(): void
    {
        // 1. Gagal jika total bobot PG + Essay != 100%
        $badRes = $this->actingAs($this->admin)->post(route('ujian.store'), [
            'kode_ujian'          => 'UJN-ESSAY-BAD',
            'nama_ujian'          => 'Ujian Bobot Salah',
            'mapel_id'            => $this->mapel->id,
            'kelas_id'            => $this->kelas->id,
            'guru_id'             => $this->admin->id,
            'jumlah_soal'         => 25,
            'jumlah_soal_essay'   => 5,
            'jumlah_opsi'         => 4,
            'bobot_benar'         => 1,
            'bobot_pg'            => 60,
            'bobot_essay'         => 20,
            'timer_default'       => 90,
            'timer_essay_default' => 180,
            'tanggal_ujian'       => now()->toDateString(),
            'status'              => 'draft',
        ]);
        $badRes->assertSessionHasErrors('bobot_essay');

        // 2. Sukses buat ujian PG + Essay dengan bobot 70% / 30%
        $okRes = $this->actingAs($this->admin)->post(route('ujian.store'), [
            'kode_ujian'          => 'UJN-ESSAY-001',
            'nama_ujian'          => 'PAS Fiqih PG & Essay',
            'mapel_id'            => $this->mapel->id,
            'kelas_id'            => $this->kelas->id,
            'guru_id'             => $this->admin->id,
            'jumlah_soal'         => 25,
            'jumlah_soal_essay'   => 5,
            'jumlah_opsi'         => 4,
            'bobot_benar'         => 1,
            'bobot_pg'            => 70,
            'bobot_essay'         => 30,
            'timer_default'       => 90,
            'timer_essay_default' => 240,
            'tanggal_ujian'       => now()->toDateString(),
            'status'              => 'aktif',
        ]);
        $okRes->assertRedirect();

        $ujian = Ujian::where('kode_ujian', 'UJN-ESSAY-001')->firstOrFail();
        $this->assertSame(25, $ujian->jumlah_soal);
        $this->assertSame(5, $ujian->jumlah_soal_essay);
        $this->assertEquals(70.0, (float) $ujian->bobot_pg);
        $this->assertEquals(30.0, (float) $ujian->bobot_essay);
        $this->assertSame(240, $ujian->timer_essay_default);
        $this->assertSame(25, $ujian->kuncis()->count());

        // 3. Sukses buat ujian khusus Essay saja (0 PG + 5 Essay => otomatis bobot 0 / 100)
        $essayOnlyRes = $this->actingAs($this->admin)->post(route('ujian.store'), [
            'kode_ujian'          => 'UJN-ESSAY-ONLY',
            'nama_ujian'          => 'Ujian Uraian Khusus',
            'mapel_id'            => $this->mapel->id,
            'kelas_id'            => $this->kelas->id,
            'guru_id'             => $this->admin->id,
            'jumlah_soal'         => 0,
            'jumlah_soal_essay'   => 5,
            'jumlah_opsi'         => 4,
            'bobot_benar'         => 1,
            'tanggal_ujian'       => now()->toDateString(),
            'status'              => 'aktif',
        ]);
        $essayOnlyRes->assertRedirect(route('ujian.soal'));

        $ujianOnly = Ujian::where('kode_ujian', 'UJN-ESSAY-ONLY')->firstOrFail();
        $this->assertEquals(0.0, (float) $ujianOnly->bobot_pg);
        $this->assertEquals(100.0, (float) $ujianOnly->bobot_essay);
        $this->assertSame(0, $ujianOnly->kuncis()->count());
    }

    public function test_upload_word_gabungan_pg_dan_essay_serta_proyektor_per_soal(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'UJN-WORD-ESSAY',
            'nama_ujian'    => 'Ujian Word PG + Essay',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 10,
            'jumlah_opsi'   => 5,
            'bobot_benar'   => 1,
            'tanggal_ujian' => now()->toDateString(),
            'status'        => 'aktif',
        ]);

        // Template Word resmi kini berisi 3 soal PG + 2 soal Essay ([ESSAY])
        $parser = new \App\Services\Omr\WordQuestionParserService();
        $docxPath = $parser->generateTemplateDocx();
        $uploadedDocx = new \Illuminate\Http\UploadedFile(
            $docxPath,
            'template_pg_essay.docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            null,
            true
        );

        $res = $this->actingAs($this->admin)->post(route('ujian.upload-word', $ujian->id), [
            'file_word'      => $uploadedDocx,
            'timer_per_soal' => 75,
            'timer_essay'    => 200,
        ]);
        @unlink($docxPath);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $ujian->refresh();
        $this->assertSame(3, $ujian->jumlah_soal);
        $this->assertSame(2, $ujian->jumlah_soal_essay);
        $this->assertEquals(70.0, (float) $ujian->bobot_pg);
        $this->assertEquals(30.0, (float) $ujian->bobot_essay);

        $pgSoals = $ujian->soalsPg()->get();
        $essaySoals = $ujian->soalsEssay()->get();
        $this->assertCount(3, $pgSoals);
        $this->assertCount(2, $essaySoals);
        $this->assertStringContainsString('hikmah menuntut ilmu', $essaySoals[0]->pertanyaan);
        $this->assertNotEmpty($essaySoals[0]->pedoman_jawaban);
        $this->assertSame(180, $essaySoals[0]->timer_detik); // dari baris "Timer: 180 detik" pada template

        // Proyektor harus memuat 3 PG lalu 2 Essay secara berurutan per soal
        $proyektorRes = $this->actingAs($this->admin)->get(route('ujian.proyektor', $ujian->id));
        $proyektorRes->assertStatus(200);
        $proyektorRes->assertSee('"tipe_soal":"pg"', false);
        $proyektorRes->assertSee('"tipe_soal":"essay"', false);
        $proyektorRes->assertSee('SOAL ESSAY NOMOR', false);
    }

    public function test_cetak_ljk_essay_1_halaman_dan_otomatis_tambah_halaman_belakang(): void
    {
        Siswa::create([
            'nama_lengkap' => 'Ahmad Fauzi',
            'nisn'         => '1234567890',
            'kelas'        => $this->kelas->nama_kelas,
            'status'       => 'aktif',
        ]);

        // Kasus A: 25 PG + 4 Essay => muat dalam 1 halaman A4 (tanpa halaman belakang)
        $ujianSatuHal = Ujian::create([
            'kode_ujian'        => 'UJN-LJK-1HAL',
            'nama_ujian'        => 'Ujian 1 Halaman',
            'mapel_id'          => $this->mapel->id,
            'kelas_id'          => $this->kelas->id,
            'guru_id'           => $this->admin->id,
            'jumlah_soal'       => 25,
            'jumlah_soal_essay' => 4,
            'jumlah_opsi'       => 4,
            'bobot_benar'       => 1,
            'bobot_pg'          => 70,
            'bobot_essay'       => 30,
            'tanggal_ujian'     => now()->toDateString(),
            'status'            => 'aktif',
        ]);

        $layout1 = LjkTemplateService::calculateEssayLayout(25, 4);
        $this->assertSame(1, $layout1['total_pages_per_siswa']);
        $this->assertSame([1, 2, 3, 4], $layout1['page1_numbers']);
        $this->assertEmpty($layout1['extra_pages']);

        $res1 = $this->actingAs($this->admin)->get(route('ujian.cetak-ljk', $ujianSatuHal->id));
        $res1->assertStatus(200);
        $res1->assertSee('BAGIAN II — JAWABAN ESSAY / URAIAN');
        $res1->assertDontSee('LANJUTAN JAWABAN ESSAY / URAIAN');

        // Kasus B: 40 PG + 8 Essay => Halaman 1 muat 1..4, nomor 5..8 otomatis tambah halaman belakang!
        $ujianDuaHal = Ujian::create([
            'kode_ujian'        => 'UJN-LJK-2HAL',
            'nama_ujian'        => 'Ujian 2 Halaman Otomatis',
            'mapel_id'          => $this->mapel->id,
            'kelas_id'          => $this->kelas->id,
            'guru_id'           => $this->admin->id,
            'jumlah_soal'       => 40,
            'jumlah_soal_essay' => 8,
            'jumlah_opsi'       => 4,
            'bobot_benar'       => 1,
            'bobot_pg'          => 60,
            'bobot_essay'       => 40,
            'tanggal_ujian'     => now()->toDateString(),
            'status'            => 'aktif',
        ]);

        $layout2 = LjkTemplateService::calculateEssayLayout(40, 8);
        $this->assertSame(2, $layout2['total_pages_per_siswa']);
        $this->assertSame([1, 2, 3, 4], $layout2['page1_numbers']);
        $this->assertCount(1, $layout2['extra_pages']);
        $this->assertSame([5, 6, 7, 8], $layout2['extra_pages'][0]['numbers']);

        $res2 = $this->actingAs($this->admin)->get(route('ujian.cetak-ljk', $ujianDuaHal->id));
        $res2->assertStatus(200);
        $res2->assertSee('BAGIAN II — JAWABAN ESSAY / URAIAN');
        $res2->assertSee('LANJUTAN JAWABAN ESSAY / URAIAN');
        $res2->assertSee('Halaman 2 dari 2');
    }

    public function test_input_manual_dan_upload_excel_nilai_essay_dengan_bobot_persen(): void
    {
        $siswa1 = Siswa::create([
            'nama_lengkap' => 'Budi Santoso',
            'nisn'         => '0011223344',
            'kelas'        => $this->kelas->nama_kelas,
            'status'       => 'aktif',
        ]);
        $siswa2 = Siswa::create([
            'nama_lengkap' => 'Siti Aminah',
            'nisn'         => '0055667788',
            'kelas'        => $this->kelas->nama_kelas,
            'status'       => 'aktif',
        ]);

        $ujian = Ujian::create([
            'kode_ujian'        => 'UJN-SCORE-ESSAY',
            'nama_ujian'        => 'Ujian Gabungan Matematika',
            'mapel_id'          => $this->mapel->id,
            'kelas_id'          => $this->kelas->id,
            'guru_id'           => $this->admin->id,
            'jumlah_soal'       => 5,
            'jumlah_soal_essay' => 3,
            'jumlah_opsi'       => 4,
            'bobot_benar'       => 20,
            'bobot_pg'          => 70,
            'bobot_essay'       => 30,
            'tanggal_ujian'     => now()->toDateString(),
            'status'            => 'aktif',
        ]);

        for ($no = 1; $no <= 5; $no++) {
            UjianKunci::create([
                'ujian_id'      => $ujian->id,
                'nomor_soal'    => $no,
                'kunci_jawaban' => 'A',
                'bobot'         => 20,
            ]);
        }

        // Siswa 1 sudah punya hasil scan OMR PG = 80 (4 benar dari 5)
        $hasil1 = UjianHasil::create([
            'ujian_id'      => $ujian->id,
            'siswa_id'      => $siswa1->id,
            'jawaban_siswa' => [1 => 'A', 2 => 'A', 3 => 'A', 4 => 'A', 5 => 'B'],
            'jumlah_benar'  => 4,
            'jumlah_salah'  => 1,
            'jumlah_kosong' => 0,
            'nilai_pg'      => 80.00,
            'nilai_essay'   => null,
            'nilai_akhir'   => 56.00,
            'status_scan'   => 'valid',
            'waktu_scan'    => now(),
        ]);

        // 1. Input langsung Nilai Essay oleh Guru (siswa1 = 90 => 80*0.7 + 90*0.3 = 83.0)
        $saveRes = $this->actingAs($this->admin)->post(route('ujian.save-essay', $ujian->id), [
            'nilai_essay' => [
                $siswa1->id => '90',
                $siswa2->id => '',
            ],
        ]);
        $saveRes->assertRedirect(route('ujian.upload-scan', ['ujian' => $ujian->id, 'tab' => 'essay']));
        $saveRes->assertSessionHas('success');

        $hasil1->refresh();
        $this->assertEquals(80.0, (float) $hasil1->nilai_pg);
        $this->assertEquals(90.0, (float) $hasil1->nilai_essay);
        $this->assertEquals(83.0, (float) $hasil1->nilai_akhir);

        // 2. Unduh Template Excel Nilai Essay
        $tplRes = $this->actingAs($this->admin)->get(route('ujian.template-essay', $ujian->id));
        $tplRes->assertStatus(200);

        // 3. Upload Excel Nilai Essay (.xlsx): siswa1 = 100, siswa2 = 80
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['ID Siswa', 'NISN', 'Nama Siswa', 'Nilai PG', 'Nilai Essay (0-100)'],
            [$siswa1->id, $siswa1->nisn, $siswa1->nama_lengkap, 80, 100],
            [$siswa2->id, $siswa2->nisn, $siswa2->nama_lengkap, '', 80],
        ], null, 'A1');

        $tmpXlsx = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'upload_essay_' . uniqid() . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($tmpXlsx);

        $uploadedXlsx = new \Illuminate\Http\UploadedFile(
            $tmpXlsx,
            'nilai_essay.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $uploadRes = $this->actingAs($this->admin)->post(route('ujian.upload-essay', $ujian->id), [
            'file_essay' => $uploadedXlsx,
        ]);
        @unlink($tmpXlsx);

        $uploadRes->assertRedirect(route('ujian.upload-scan', ['ujian' => $ujian->id, 'tab' => 'essay']));
        $uploadRes->assertSessionHas('success');

        $hasil1->refresh();
        $this->assertEquals(80.0, (float) $hasil1->nilai_pg);
        $this->assertEquals(100.0, (float) $hasil1->nilai_essay);
        $this->assertEquals(86.0, (float) $hasil1->nilai_akhir); // 80*0.7 + 100*0.3

        $hasil2 = UjianHasil::where('ujian_id', $ujian->id)->where('siswa_id', $siswa2->id)->firstOrFail();
        $this->assertEquals(0.0, (float) $hasil2->nilai_pg);
        $this->assertEquals(80.0, (float) $hasil2->nilai_essay);
        $this->assertEquals(24.0, (float) $hasil2->nilai_akhir); // 0*0.7 + 80*0.3

        // 4. Koreksi manual PG (ubah no. 5 jadi 'A' => PG jadi 100) TIDAK menghapus nilai essay (tetap 100) => Nilai Akhir = 100
        $manualRes = $this->actingAs($this->admin)->post(route('ujian.update-hasil-manual', [$ujian->id, $hasil1->id]), [
            'siswa_id' => $siswa1->id,
            'jawaban'  => [1 => 'A', 2 => 'A', 3 => 'A', 4 => 'A', 5 => 'A'],
        ]);
        $manualRes->assertRedirect(route('ujian.hasil', $ujian->id));

        $hasil1->refresh();
        $this->assertEquals(100.0, (float) $hasil1->nilai_pg);
        $this->assertEquals(100.0, (float) $hasil1->nilai_essay);
        $this->assertEquals(100.0, (float) $hasil1->nilai_akhir);

        // 5. Rekap Nilai & Export Excel berjalan mulus
        $hasilPage = $this->actingAs($this->admin)->get(route('ujian.hasil', $ujian->id));
        $hasilPage->assertStatus(200);
        $hasilPage->assertSee('Nilai PG (70%)');
        $hasilPage->assertSee('Nilai Essay (30%)');

        $exportRes = $this->actingAs($this->admin)->get(route('ujian.export-excel', $ujian->id));
        $exportRes->assertStatus(200);
    }

    public function test_upload_word_mime_zip_dan_autonumbering_word_serta_shift_enter(): void
    {
        $ujian = Ujian::create([
            'kode_ujian'    => 'UJN-WORD-REAL',
            'nama_ujian'    => 'PAS "Ganjil" Bahasa Indonesia',
            'mapel_id'      => $this->mapel->id,
            'kelas_id'      => $this->kelas->id,
            'guru_id'       => $this->admin->id,
            'jumlah_soal'   => 2,
            'jumlah_opsi'   => 4,
            'bobot_benar'   => 1,
            'tanggal_ujian' => now()->toDateString(),
            'status'        => 'aktif',
        ]);

        // Bangun .docx realistis ala Microsoft Word:
        // - Memiliki word/numbering.xml (auto-numbering 1, 2 & auto-lettering A, B, C, D tanpa teks "1."/"A." di <w:t>)
        // - Memiliki self-closing <w:p/> dan <w:br/> (Shift+Enter)
        // - Memiliki angka desimal "3.5" dan "1.500" di dalam soal agar tidak terpotong menjadi nomor soal palsu
        // - Memiliki bagian [ESSAY] dengan auto-numbering
        $tmpDocx = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'word_autonum_' . uniqid() . '.docx';
        $zip = new \ZipArchive();
        $zip->open($tmpDocx, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
    <Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>
</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>');
        $zip->addFromString('word/numbering.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:abstractNum w:abstractNumId="1">
        <w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="decimal"/></w:lvl>
        <w:lvl w:ilvl="1"><w:start w:val="1"/><w:numFmt w:val="upperLetter"/></w:lvl>
    </w:abstractNum>
    <w:num w:numId="1"><w:abstractNumId w:val="1"/></w:num>
</w:numbering>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p/>
        <w:p>
            <w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr>
            <w:r><w:t>Harga sebuah buku adalah Rp 1.500 dan pensil 3.5 kali lipatnya.</w:t><w:br/><w:t>Berapakah nilai yang benar?</w:t></w:r>
        </w:p>
        <w:p><w:pPr><w:numPr><w:ilvl w:val="1"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>Pilihan Pertama</w:t></w:r></w:p>
        <w:p><w:pPr><w:numPr><w:ilvl w:val="1"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>Pilihan Kedua</w:t></w:r></w:p>
        <w:p><w:pPr><w:numPr><w:ilvl w:val="1"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>Pilihan Ketiga</w:t></w:r></w:p>
        <w:p><w:pPr><w:numPr><w:ilvl w:val="1"/><w:numId w:val="1"/></w:numPr></w:pPr><w:r><w:t>Pilihan Keempat</w:t></w:r></w:p>
        <w:p><w:r><w:t>Kunci: B</w:t></w:r></w:p>
        <w:p/>
        <w:p>
            <w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr>
            <w:r><w:t>Ibukota Provinsi Jawa Timur adalah...</w:t><w:br/><w:t>A. Bandung</w:t><w:br/><w:t>B. Surabaya</w:t><w:br/><w:t>C. Medan</w:t><w:br/><w:t>D. Semarang</w:t><w:br/><w:t>Kunci Jawaban: B</w:t></w:r>
        </w:p>
        <w:p><w:r><w:t>[ESSAY]</w:t></w:r></w:p>
        <w:p>
            <w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr></w:pPr>
            <w:r><w:t>Jelaskan manfaat membaca buku setiap hari!</w:t></w:r>
        </w:p>
    </w:body>
</w:document>');
        $zip->close();

        // Simulasikan MIME type application/zip (sering terjadi di server Linux libmagic)
        $uploadedZipMime = new \Illuminate\Http\UploadedFile(
            $tmpDocx,
            'soal_real_word.docx',
            'application/zip',
            null,
            true
        );

        $res = $this->actingAs($this->admin)->post(route('ujian.upload-word', $ujian->id), [
            'file_word'      => $uploadedZipMime,
            'timer_per_soal' => 90,
            'timer_essay'    => 180,
        ]);
        @unlink($tmpDocx);

        $res->assertRedirect();
        $res->assertSessionHas('success');

        $ujian->refresh();
        $this->assertSame(2, $ujian->jumlah_soal);
        $this->assertSame(1, $ujian->jumlah_soal_essay);

        $pg = $ujian->soalsPg()->get();
        $this->assertCount(2, $pg);
        $this->assertSame('Pilihan Pertama', $pg[0]->opsi_a);
        $this->assertSame('Pilihan Kedua', $pg[0]->opsi_b);
        $this->assertSame('B', $pg[0]->kunci_jawaban);
        $this->assertSame('Surabaya', $pg[1]->opsi_b);
        $this->assertSame('B', $pg[1]->kunci_jawaban);

        $essay = $ujian->soalsEssay()->get();
        $this->assertCount(1, $essay);
        $this->assertStringContainsString('manfaat membaca buku', $essay[0]->pertanyaan);

        // Pastikan file .doc (format lama) menghasilkan pesan error informatif di session('error')
        $fakeDoc = \Illuminate\Http\UploadedFile::fake()->create('soal_lama.doc', 20, 'application/msword');
        $docRes = $this->actingAs($this->admin)->post(route('ujian.upload-word', $ujian->id), [
            'file_word' => $fakeDoc,
        ]);
        $docRes->assertRedirect();
        $docRes->assertSessionHas('error');
    }

    public function test_pengaturan_jam_sesi_ujian_cetak_nomor_meja_dan_perbaikan_sesi_ujian(): void
    {
        // Buat data TimeSlot dummy (seperti di produksi yang menyebabkan Jam Ke-0 ( - ) berulang)
        \App\Models\TimeSlot::create([
            'jenjang'       => 'SMP',
            'hari'          => 'Senin',
            'jam_ke'        => 0,
            'waktu_mulai'   => '07:00',
            'waktu_selesai' => '07:30',
            'is_break'      => true,
            'is_active'     => true,
        ]);

        // Siswa aktif pada kelas ini & kelas lain
        Siswa::create([
            'nama_lengkap' => 'Zaki Pratama',
            'nisn'         => '1111222233',
            'kelas'        => $this->kelas->nama_kelas,
            'status'       => 'aktif',
        ]);
        Siswa::create([
            'nama_lengkap' => 'Aisyah Putri',
            'nisn'         => '4444555566',
            'kelas'        => $this->kelas->nama_kelas,
            'status'       => 'aktif',
        ]);
        Siswa::create([
            'nama_lengkap' => 'Siswa Kelas Lain',
            'nisn'         => '9999888877',
            'kelas'        => '9Z',
            'status'       => 'aktif',
        ]);

        // 1. Halaman Soal Ujian menampilkan tab Pengaturan
        $soalRes = $this->actingAs($this->admin)->get(route('ujian.soal'));
        $soalRes->assertStatus(200);
        $soalRes->assertSee(route('ujian.pengaturan'), false);
        $soalRes->assertSee('Pengaturan');

        // 2. Buka halaman Pengaturan Ujian & tambah Jam Sesi Ujian
        $pengaturanRes = $this->actingAs($this->admin)->get(route('ujian.pengaturan'));
        $pengaturanRes->assertStatus(200);
        $pengaturanRes->assertSee('Pengaturan Jam Sesi Ujian');
        $pengaturanRes->assertSee('Cetak Nomor Meja Ujian per Kelas');

        $storeJamRes = $this->actingAs($this->admin)->post(route('ujian.sesi-jam.store'), [
            'nama_sesi'   => 'Sesi Pagi 1',
            'jam_mulai'   => '07:30',
            'jam_selesai' => '09:00',
            'urutan'      => 1,
        ]);
        $storeJamRes->assertRedirect(route('ujian.pengaturan'));
        $storeJamRes->assertSessionHas('success');

        $sesiJam = \App\Models\UjianSesiJam::where('nama_sesi', 'Sesi Pagi 1')->firstOrFail();

        // Update jam sesi
        $updateJamRes = $this->actingAs($this->admin)->put(route('ujian.sesi-jam.update', $sesiJam->id), [
            'nama_sesi'   => 'Sesi 1',
            'jam_mulai'   => '07:30',
            'jam_selesai' => '09:15',
            'urutan'      => 1,
            'is_active'   => 1,
        ]);
        $updateJamRes->assertRedirect(route('ujian.pengaturan'));
        $this->assertSame('09:15', $sesiJam->fresh()->jam_selesai);

        // 3. Halaman Sesi Ujian menggunakan Jam Sesi Ujian yang benar & TIDAK menampilkan "Jam Ke-0 ( - )"
        $sesiRes = $this->actingAs($this->admin)->get(route('ujian.sesi'));
        $sesiRes->assertStatus(200);
        $sesiRes->assertSee('Sesi 1 (07:30 - 09:15)');
        $sesiRes->assertDontSee('Jam Ke-0 ( - )');

        // 4. Cetak Nomor Meja per kelas (urut abjad: Aisyah Putri = 7A-01, Zaki Pratama = 7A-02, Siswa Kelas Lain tidak ikut)
        $cetakMejaRes = $this->actingAs($this->admin)->get(route('ujian.cetak-nomor-meja', [
            'kelas_id'    => $this->kelas->id,
            'judul_ujian' => 'PAS Ganjil 2026',
            'ruang'       => 'Ruang 01',
            'awalan'      => '7A-',
        ]));
        $cetakMejaRes->assertStatus(200);
        $cetakMejaRes->assertSee('PAS Ganjil 2026');
        $cetakMejaRes->assertSee('Ruang 01');
        $cetakMejaRes->assertSee('7A-01');
        $cetakMejaRes->assertSee('7A-02');
        $cetakMejaRes->assertSee('Aisyah Putri');
        $cetakMejaRes->assertSee('Zaki Pratama');
        $cetakMejaRes->assertDontSee('Siswa Kelas Lain');

        // 5. Hapus jam sesi berjalan lancar
        $delJamRes = $this->actingAs($this->admin)->delete(route('ujian.sesi-jam.destroy', $sesiJam->id));
        $delJamRes->assertRedirect(route('ujian.pengaturan'));
        $this->assertDatabaseMissing('ujian_sesi_jams', ['id' => $sesiJam->id]);
    }
}
