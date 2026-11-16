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
        // 1. Menu Ujian terdaftar di matriks hak akses
        $menuGroups = UserController::systemMenuGroups();
        $this->assertArrayHasKey('Sekolah', $menuGroups);
        $this->assertArrayHasKey('ujian', $menuGroups['Sekolah']);

        // 2. Tampil di sidebar
        $res = $this->actingAs($this->admin)->get(route('beranda'));
        $res->assertStatus(200);
        $res->assertSee('Ujian & LJK', false);
        $res->assertSee(route('ujian.index'));
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
}
