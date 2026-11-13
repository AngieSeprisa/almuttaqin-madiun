<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\MasterKelasPengajian;
use App\Models\PondokJurnalMengaji;
use App\Models\PondokMateri;
use App\Models\PondokTargetPengajian;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PondokPengajianMenuTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->user = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
    }

    public function test_menu_pondok_dan_tiga_submenu_tampil_di_sidebar(): void
    {
        $response = $this->actingAs($this->user)->get(route('beranda'));
        $response->assertStatus(200);

        $response->assertSee('navGroupPondok', false);
        $response->assertSee('Daftar Materi');
        $response->assertSee('Target Pengajian');
        $response->assertSee('Jurnal Mengaji');
        $response->assertSee(route('pondok.materi.index'), false);
        $response->assertSee(route('pondok.target.index'), false);
        $response->assertSee(route('pondok.jurnal.index'), false);
        $response->assertDontSee(route('pondok.rekap-jurnal'), false);
    }

    public function test_crud_daftar_materi_al_quran_dan_al_hadist(): void
    {
        // 1. Tambah materi Al-Qur'an (menampilkan jumlah halaman)
        $resQuran = $this->actingAs($this->user)->post(route('pondok.materi.store'), [
            'kode_materi'    => 'QUR-01',
            'nama_materi'    => 'Al-Qur\'an Mushaf Madinah',
            'kategori'       => 'al_quran',
            'jumlah_halaman' => 604,
            'keterangan'     => 'Mushaf Standar Madinah',
        ]);
        $resQuran->assertRedirect(route('pondok.materi.index'));

        // 2. Tambah materi Al-Hadist (menampilkan halaman dan baris)
        $resHadist = $this->actingAs($this->user)->post(route('pondok.materi.store'), [
            'kode_materi'    => 'HAD-01',
            'nama_materi'    => 'K. Sholah',
            'kategori'       => 'al_hadist',
            'jumlah_halaman' => 144,
            'jumlah_baris'   => 16,
            'keterangan'     => 'Himpunan Hadist Sholat',
        ]);
        $resHadist->assertRedirect(route('pondok.materi.index'));

        $page = $this->actingAs($this->user)->get(route('pondok.materi.index'));
        $page->assertStatus(200);
        $page->assertSee("Al-Qur'an Mushaf Madinah");
        $page->assertSee('604 Halaman');
        $page->assertSee('K. Sholah');
        $page->assertSee('144 Halaman');
        $page->assertSee('16 Baris');
        $page->assertDontSee('(Opsional)');
    }

    public function test_form_target_pengajian_bersih_tanpa_target_jumlah_halaman_baris_dan_tanpa_deskripsi_kurung(): void
    {
        MasterKelasPengajian::seedStandar();

        PondokMateri::create([
            'kode_materi'    => 'HAD-01',
            'nama_materi'    => 'K. Sholah',
            'kategori'       => 'al_hadist',
            'jumlah_halaman' => 144,
            'jumlah_baris'   => 16,
        ]);

        $page = $this->actingAs($this->user)->get(route('pondok.target.index'));
        $page->assertStatus(200);
        $page->assertDontSee('Target Jumlah Halaman');
        $page->assertDontSee('Target Jumlah Baris');
        $page->assertDontSee('(Opsional)');
        $page->assertDontSee('(Al-Hadist)');
        $page->assertDontSee('Sesi Pengajian');
        $page->assertDontSee('Mushaf Madinah');
        $page->assertSee('Awal Surat');
        $page->assertSee('Akhir Surat');
        $page->assertSee('Ayat');
        $page->assertSee('Halaman');
        $page->assertSee('Baris');
        $page->assertSee('Al-Fatihah');
        $page->assertSee('Al-Baqarah');
        $page->assertSee('An-Nas');
    }

    public function test_target_pengajian_al_quran_mushaf_madinah_dan_al_hadist_serta_indikator_jurnal_mengaji(): void
    {
        MasterKelasPengajian::seedStandar();
        $kelasLambatan = MasterKelasPengajian::where('kode_kelas', 'KLS_LAMBATAN_SMP')->firstOrFail();
        $kelasCepatan  = MasterKelasPengajian::where('kode_kelas', 'KLS_CEPATAN_SMP')->firstOrFail();
        $kelasReguler  = MasterKelasPengajian::where('kode_kelas', 'KLS_REGULER')->firstOrFail();

        $materiQuran = PondokMateri::create([
            'kode_materi'    => 'QUR-01',
            'nama_materi'    => 'Al-Qur\'an',
            'kategori'       => 'al_quran',
            'jumlah_halaman' => 604,
        ]);

        $materiHadist = PondokMateri::create([
            'kode_materi'    => 'HAD-01',
            'nama_materi'    => 'K. Sholah',
            'kategori'       => 'al_hadist',
            'jumlah_halaman' => 144,
            'jumlah_baris'   => 16,
        ]);

        // 1. Buat Target Al-Qur'an (Awal Surat Al-Baqarah Ayat 1 s/d Akhir Surat Al-Baqarah Ayat 25)
        $this->actingAs($this->user)->post(route('pondok.target.store'), [
            'tanggal'            => '2026-11-11',
            'kelas_pengajian_id' => $kelasLambatan->id,
            'pondok_materi_id'   => $materiQuran->id,
            'surat_nomor'        => 2,
            'ayat'               => 1,
            'surat_akhir_nomor'  => 2,
            'ayat_akhir'         => 25,
        ])->assertRedirect(route('pondok.target.index'));

        // 2. Buat Target Al-Qur'an Kelas Cepatan (Surat Al-Baqarah Ayat 26 s/d 50)
        $this->actingAs($this->user)->post(route('pondok.target.store'), [
            'tanggal'            => '2026-11-11',
            'kelas_pengajian_id' => $kelasCepatan->id,
            'pondok_materi_id'   => $materiQuran->id,
            'surat_nomor'        => 2,
            'ayat'               => 26,
            'surat_akhir_nomor'  => 2,
            'ayat_akhir'         => 50,
        ])->assertRedirect(route('pondok.target.index'));

        // 3. Buat Target Al-Hadist Kelas Reguler (K. Sholah Halaman 12 Baris 8)
        $this->actingAs($this->user)->post(route('pondok.target.store'), [
            'tanggal'            => '2026-11-11',
            'kelas_pengajian_id' => $kelasReguler->id,
            'pondok_materi_id'   => $materiHadist->id,
            'halaman'            => 12,
            'baris'              => 8,
        ])->assertRedirect(route('pondok.target.index'));

        $targetLambatan = PondokTargetPengajian::where('kelas_pengajian_id', $kelasLambatan->id)->firstOrFail();
        $targetCepatan  = PondokTargetPengajian::where('kelas_pengajian_id', $kelasCepatan->id)->firstOrFail();
        $targetReguler  = PondokTargetPengajian::where('kelas_pengajian_id', $kelasReguler->id)->firstOrFail();

        $this->assertSame('Al-Baqarah', $targetLambatan->nama_surat);
        $this->assertSame(1, $targetLambatan->ayat);
        $this->assertSame('Al-Baqarah', $targetLambatan->nama_surat_akhir);
        $this->assertSame(25, $targetLambatan->ayat_akhir);
        $this->assertSame(6, $targetLambatan->target_halaman);

        $this->assertSame(12, $targetReguler->target_halaman);
        $this->assertSame(8, $targetReguler->target_baris);

        $targetPage = $this->actingAs($this->user)->get(route('pondok.target.index'));
        $targetPage->assertSee('QS. Al-Baqarah Ayat 1–25');
        $targetPage->assertSee('Hal. 12 Baris 8');

        // 4. Input Jurnal Mengaji Kelas 1 (Al-Qur'an): Surat 2 Ayat 1–25 == Target -> TERCAPAI
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'                   => '2026-11-11',
            'sesi_pengajian'            => 'bada_maghrib',
            'kelas_pengajian_id'        => $kelasLambatan->id,
            'pondok_materi_id'          => $materiQuran->id,
            'target_pengajian_id'       => $targetLambatan->id,
            'capaian_surat_nomor'       => 2,
            'capaian_ayat'              => 1,
            'capaian_surat_akhir_nomor' => 2,
            'capaian_ayat_akhir'        => 25,
            'catatan'                   => 'Bacaan lancar sesuai target',
        ])->assertRedirect(route('pondok.jurnal.index'));

        // 5. Input Jurnal Mengaji Kelas 2 (Al-Qur'an): Surat 2 Ayat 26–30 < Target Ayat 50 -> KURANG
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'                   => '2026-11-11',
            'sesi_pengajian'            => 'malam',
            'kelas_pengajian_id'        => $kelasCepatan->id,
            'pondok_materi_id'          => $materiQuran->id,
            'target_pengajian_id'       => $targetCepatan->id,
            'capaian_surat_nomor'       => 2,
            'capaian_ayat'              => 26,
            'capaian_surat_akhir_nomor' => 2,
            'capaian_ayat_akhir'        => 30,
            'catatan'                   => 'Perlu pengulangan tajwid',
        ])->assertRedirect(route('pondok.jurnal.index'));

        // 6. Input Jurnal Mengaji Kelas 3 (Al-Hadist): Halaman 12 Baris 14 > Target Halaman 12 Baris 8 -> TERLAMPAUI
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'             => '2026-11-11',
            'sesi_pengajian'      => 'bada_subuh',
            'kelas_pengajian_id'  => $kelasReguler->id,
            'pondok_materi_id'    => $materiHadist->id,
            'target_pengajian_id' => $targetReguler->id,
            'capaian_halaman'     => 12,
            'capaian_baris'       => 14,
            'catatan'             => 'Melampaui target baris hadist',
        ])->assertRedirect(route('pondok.jurnal.index'));

        $this->assertDatabaseHas('pondok_jurnal_mengajis', [
            'kelas_pengajian_id'  => $kelasLambatan->id,
            'status_ketercapaian' => PondokJurnalMengaji::STATUS_TERCAPAI,
        ]);
        $this->assertDatabaseHas('pondok_jurnal_mengajis', [
            'kelas_pengajian_id'  => $kelasCepatan->id,
            'status_ketercapaian' => PondokJurnalMengaji::STATUS_KURANG,
        ]);
        $this->assertDatabaseHas('pondok_jurnal_mengajis', [
            'kelas_pengajian_id'  => $kelasReguler->id,
            'status_ketercapaian' => PondokJurnalMengaji::STATUS_TERLAMPAUI,
        ]);

        // 7. Verifikasi tampilan halaman Jurnal Mengaji menampilkan ketiga indikator, Sesi Pengajian, dan capaian
        $jurnalPage = $this->actingAs($this->user)->get(route('pondok.jurnal.index'));
        $jurnalPage->assertStatus(200);
        $jurnalPage->assertSee('Sesi Pengajian');
        $jurnalPage->assertSee('Awal Surat');
        $jurnalPage->assertSee('Akhir Surat');
        $jurnalPage->assertSee('✓ Tercapai');
        $jurnalPage->assertSee('! Kurang');
        $jurnalPage->assertSee('↑ Terlampaui');
        $jurnalPage->assertSee('QS. Al-Baqarah Ayat 1–25');
        $jurnalPage->assertSee('Hal. 12 Baris 14');
    }

    public function test_muat_standar_materi_dan_sinkronisasi_absensi_jurnal_ke_rekap_absensi_pengajian(): void
    {
        MasterKelasPengajian::seedStandar();
        $kelasLambatan = MasterKelasPengajian::where('kode_kelas', 'KLS_LAMBATAN_SMP')->firstOrFail();

        // 1. Muat materi standar (3 paket Al-Qur'an + 25 kitab Al-Hadist)
        $this->actingAs($this->user)
            ->post(route('pondok.materi.muat-standar'))
            ->assertRedirect(route('pondok.materi.index'));

        $this->assertDatabaseHas('pondok_materis', [
            'kode_materi'    => 'QUR-CPT',
            'paket_quran'    => 'cepatan',
            'halaman_awal'   => 1,
            'halaman_akhir'  => 341,
            'jumlah_halaman' => 341,
        ]);
        $this->assertDatabaseHas('pondok_materis', [
            'kode_materi'    => 'QUR-LMB',
            'paket_quran'    => 'lambatan',
            'halaman_awal'   => 342,
            'halaman_akhir'  => 604,
            'jumlah_halaman' => 263,
        ]);
        $this->assertDatabaseHas('pondok_materis', [
            'kode_materi'    => 'QUR-REG',
            'paket_quran'    => 'reguler',
            'halaman_awal'   => 1,
            'halaman_akhir'  => 604,
            'jumlah_halaman' => 604,
        ]);
        $this->assertDatabaseHas('pondok_materis', [
            'kode_materi' => 'HAD-01',
            'nama_materi' => 'K. Sholah',
            'kategori'    => 'al_hadist',
        ]);

        // 2. Buat 3 santri di Kelas Lambatan SMP
        $lembagaSmp = Lembaga::where('jenis', 'smp')->first() ?? Lembaga::first();
        $santri1 = Siswa::create([
            'lembaga_id'         => $lembagaSmp?->id,
            'nama_lengkap'       => 'Ahmad Fauzi',
            'nisn'               => '10000001',
            'jenis_kelamin'      => 'L',
            'kelas'              => '7A',
            'kamar'              => 'Abu Bakar 1',
            'kelas_pengajian_id' => $kelasLambatan->id,
        ]);
        $santri2 = Siswa::create([
            'lembaga_id'         => $lembagaSmp?->id,
            'nama_lengkap'       => 'Budi Santoso',
            'nisn'               => '10000002',
            'jenis_kelamin'      => 'L',
            'kelas'              => '7A',
            'kamar'              => 'Abu Bakar 1',
            'kelas_pengajian_id' => $kelasLambatan->id,
        ]);
        $santri3 = Siswa::create([
            'lembaga_id'         => $lembagaSmp?->id,
            'nama_lengkap'       => 'Chandra Wijaya',
            'nisn'               => '10000003',
            'jenis_kelamin'      => 'L',
            'kelas'              => '7A',
            'kamar'              => 'Abu Bakar 1',
            'kelas_pengajian_id' => $kelasLambatan->id,
        ]);

        $materiLambatan = PondokMateri::where('paket_quran', 'lambatan')->firstOrFail();

        // 3. Input Jurnal Mengaji + Absensi Santri (Santri 1 Hadir, Santri 2 Izin, Santri 3 Alpa)
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'             => '2026-11-11',
            'sesi_pengajian'      => 'bada_maghrib',
            'kelas_pengajian_id'  => $kelasLambatan->id,
            'pondok_materi_id'    => $materiLambatan->id,
            'target_surat_nomor'  => 23,
            'target_ayat'         => 15,
            'capaian_surat_nomor' => 23,
            'capaian_ayat'        => 15,
            'absensi'             => [
                $santri1->id => 'hadir',
                $santri2->id => 'izin',
                $santri3->id => 'alpa',
            ],
        ])->assertRedirect(route('pondok.jurnal.index'));

        $jurnal = PondokJurnalMengaji::where('kelas_pengajian_id', $kelasLambatan->id)->firstOrFail();
        $this->assertSame(3, $jurnal->total_santri);
        $this->assertSame(1, $jurnal->total_hadir);
        $this->assertSame(1, $jurnal->total_izin);
        $this->assertSame(1, $jurnal->total_alpa);

        // 4. Pastikan akses route rekap-jurnal diarahkan (redirect) ke Jurnal Mengaji dan data kehadiran "H: 1/3" tampil di Jurnal Mengaji
        $rekapJurnalRedirect = $this->actingAs($this->user)->get(route('pondok.rekap-jurnal'));
        $rekapJurnalRedirect->assertRedirect(route('pondok.jurnal.index'));

        $jurnalPage = $this->actingAs($this->user)->get(route('pondok.jurnal.index'));
        $jurnalPage->assertStatus(200);
        $jurnalPage->assertSee('H: 1/3');

        // 5. Pastikan absensi santri dari Jurnal Mengaji tersinkron ke Rekap Absensi Pengajian
        $rekapAbsensiPage = $this->actingAs($this->user)->get(route('pondok.rekap-absensi', [
            'bulan'       => 11,
            'tahun'       => 2026,
            'kelas_ngaji' => $kelasLambatan->id,
        ]));
        $rekapAbsensiPage->assertStatus(200);
        $rekapAbsensiPage->assertSee('Ahmad Fauzi');
        $rekapAbsensiPage->assertSee('Budi Santoso');
        $rekapAbsensiPage->assertSee('Chandra Wijaya');
    }

    public function test_target_dan_jurnal_hadist_range_serta_opsi_khatam_lanjut_hadist_berikutnya(): void
    {
        MasterKelasPengajian::seedStandar();
        $kelas = MasterKelasPengajian::where('kode_kelas', 'KLS_REGULER')->firstOrFail();

        $kitab1 = PondokMateri::create([
            'kode_materi'    => 'HAD-01',
            'nama_materi'    => 'Kitabus Sholah',
            'kategori'       => 'al_hadist',
            'jumlah_halaman' => 144,
            'jumlah_baris'   => 16,
        ]);

        $kitab2 = PondokMateri::create([
            'kode_materi'    => 'HAD-02',
            'nama_materi'    => 'Kitabus Shaum',
            'kategori'       => 'al_hadist',
            'jumlah_halaman' => 88,
            'jumlah_baris'   => 16,
        ]);

        // 1. Buat Target Hadist dengan range: Hal 140 Baris 1 s/d Hal 144 Baris 16 (khatam target)
        $this->actingAs($this->user)->post(route('pondok.target.store'), [
            'tanggal'            => '2026-11-13',
            'kelas_pengajian_id' => $kelas->id,
            'pondok_materi_id'   => $kitab1->id,
            'halaman_awal'       => 140,
            'baris_awal'         => 1,
            'halaman_akhir'      => 144,
            'baris_akhir'        => 16,
            'keterangan'         => 'Target khatam K. Sholah',
        ])->assertRedirect(route('pondok.target.index'));

        $target = PondokTargetPengajian::where('kelas_pengajian_id', $kelas->id)->latest('id')->firstOrFail();
        $this->assertSame(140, $target->halaman_awal);
        $this->assertSame(1, $target->baris_awal);
        $this->assertSame(144, $target->halaman_akhir);
        $this->assertSame(16, $target->baris_akhir);
        $this->assertStringContainsString('Hal. 140 Baris 1 – Hal. 144 Baris 16', $target->target_label);

        // 2. Buat Jurnal dengan kondisi: Khatam Kitabus Sholah dan lanjut Kitabus Shaum Hal 1 Baris 1 s/d Hal 2 Baris 8
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'                => '2026-11-13',
            'sesi_pengajian'         => 'bada_maghrib',
            'kelas_pengajian_id'     => $kelas->id,
            'pondok_materi_id'       => $kitab1->id,
            'target_pengajian_id'    => $target->id,
            'halaman_awal'           => 140,
            'baris_awal'             => 1,
            'halaman_akhir'          => 144,
            'baris_akhir'            => 16,
            'is_khatam'              => 1,
            'lanjutan_materi_id'     => $kitab2->id,
            'lanjutan_halaman_akhir' => 2,
            'lanjutan_baris_akhir'   => 8,
            'catatan'                => 'Alhamdulillah khatam kitab sholah dan masuk kitab shaum',
        ])->assertRedirect(route('pondok.jurnal.index'));

        $jurnal = PondokJurnalMengaji::where('kelas_pengajian_id', $kelas->id)->latest('id')->firstOrFail();
        $this->assertTrue((bool) $jurnal->is_khatam);
        $this->assertSame($kitab2->id, $jurnal->lanjutan_materi_id);
        $this->assertSame(1, $jurnal->lanjutan_halaman_awal);
        $this->assertSame(1, $jurnal->lanjutan_baris_awal);
        $this->assertSame(PondokJurnalMengaji::STATUS_TERLAMPAUI, $jurnal->status_ketercapaian);
        $this->assertStringContainsString('Lanjut Kitabus Shaum s/d Hal. 2 Baris 8', $jurnal->capaian_label);

        // 3. Verifikasi halaman jurnal mengaji menampilkan badge Khatam, toggle lanjut materi, dan materi lanjutan
        $response = $this->actingAs($this->user)->get(route('pondok.jurnal.index'));
        $response->assertStatus(200);
        $response->assertSee('jm-khatam-badge', false);
        $response->assertSee('Kitabus Sholah');
        $response->assertSee('Kitabus Shaum');
        $response->assertSee('Halaman Awal');
        $response->assertSee('Halaman Akhir');
        $response->assertSee('Lanjut materi berikutnya');
        $response->assertSee('Materi Berikutnya');
        $response->assertDontSee('Halaman Awal Lanjutan');
        $response->assertDontSee('Khatam Hadist saat ini', false);
    }
}
