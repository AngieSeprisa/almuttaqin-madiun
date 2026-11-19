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
}
