<?php

namespace Tests\Feature;

use App\Models\MasterKelasPengajian;
use App\Models\PondokJurnalMengaji;
use App\Models\PondokMateri;
use App\Models\PondokTargetPengajian;
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
        $page->assertSee('Nama Surat');
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

        // 1. Buat Target Al-Qur'an (Surat Al-Baqarah Ayat 25 -> Hal. 5 Mushaf Madinah)
        $this->actingAs($this->user)->post(route('pondok.target.store'), [
            'tanggal'            => '2026-11-11',
            'kelas_pengajian_id' => $kelasLambatan->id,
            'pondok_materi_id'   => $materiQuran->id,
            'surat_nomor'        => 2,
            'ayat'               => 25,
        ])->assertRedirect(route('pondok.target.index'));

        // 2. Buat Target Al-Qur'an Kelas Cepatan (Surat Al-Baqarah Ayat 50)
        $this->actingAs($this->user)->post(route('pondok.target.store'), [
            'tanggal'            => '2026-11-11',
            'kelas_pengajian_id' => $kelasCepatan->id,
            'pondok_materi_id'   => $materiQuran->id,
            'surat_nomor'        => 2,
            'ayat'               => 50,
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
        $this->assertSame(25, $targetLambatan->ayat);
        $this->assertSame(6, $targetLambatan->target_halaman);

        $this->assertSame(12, $targetReguler->target_halaman);
        $this->assertSame(8, $targetReguler->target_baris);

        $targetPage = $this->actingAs($this->user)->get(route('pondok.target.index'));
        $targetPage->assertSee('QS. Al-Baqarah Ayat 25');
        $targetPage->assertSee('Halaman 12 • Baris 8');

        // 4. Input Jurnal Mengaji Kelas 1 (Al-Qur'an): Surat 2 Ayat 25 == Target -> TERCAPAI
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'             => '2026-11-11',
            'kelas_pengajian_id'  => $kelasLambatan->id,
            'pondok_materi_id'    => $materiQuran->id,
            'target_pengajian_id' => $targetLambatan->id,
            'capaian_surat_nomor' => 2,
            'capaian_ayat'        => 25,
            'catatan'             => 'Bacaan lancar sesuai target',
        ])->assertRedirect(route('pondok.jurnal.index'));

        // 5. Input Jurnal Mengaji Kelas 2 (Al-Qur'an): Surat 2 Ayat 30 < Target Ayat 50 -> KURANG
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'             => '2026-11-11',
            'kelas_pengajian_id'  => $kelasCepatan->id,
            'pondok_materi_id'    => $materiQuran->id,
            'target_pengajian_id' => $targetCepatan->id,
            'capaian_surat_nomor' => 2,
            'capaian_ayat'        => 30,
            'catatan'             => 'Perlu pengulangan tajwid',
        ])->assertRedirect(route('pondok.jurnal.index'));

        // 6. Input Jurnal Mengaji Kelas 3 (Al-Hadist): Halaman 12 Baris 14 > Target Halaman 12 Baris 8 -> TERLAMPAUI
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'             => '2026-11-11',
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

        // 7. Verifikasi tampilan halaman Jurnal Mengaji menampilkan ketiga indikator dan capaian
        $jurnalPage = $this->actingAs($this->user)->get(route('pondok.jurnal.index'));
        $jurnalPage->assertStatus(200);
        $jurnalPage->assertSee('✓ Tercapai');
        $jurnalPage->assertSee('! Kurang');
        $jurnalPage->assertSee('↑ Terlampaui');
        $jurnalPage->assertSee('QS. Al-Baqarah Ayat 25');
        $jurnalPage->assertSee('Halaman 12 • Baris 14');
    }
}
