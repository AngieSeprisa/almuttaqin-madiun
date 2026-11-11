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
            'nama_materi'    => 'Al-Qur\'an Juz 1',
            'kategori'       => 'al_quran',
            'jumlah_halaman' => 20,
            'keterangan'     => 'Mushaf Standar',
        ]);
        $resQuran->assertRedirect(route('pondok.materi.index'));

        // 2. Tambah materi Al-Hadist (menampilkan halaman dan baris)
        $resHadist = $this->actingAs($this->user)->post(route('pondok.materi.store'), [
            'kode_materi'    => 'HAD-01',
            'nama_materi'    => 'Kitab Sholat',
            'kategori'       => 'al_hadist',
            'jumlah_halaman' => 48,
            'jumlah_baris'   => 720,
            'keterangan'     => 'Himpunan Hadist Sholat',
        ]);
        $resHadist->assertRedirect(route('pondok.materi.index'));

        $page = $this->actingAs($this->user)->get(route('pondok.materi.index'));
        $page->assertStatus(200);
        $page->assertSee("Al-Qur'an Juz 1");
        $page->assertSee('20 Halaman');
        $page->assertSee('Kitab Sholat');
        $page->assertSee('48 Halaman');
        $page->assertSee('720 Baris');
    }

    public function test_target_pengajian_per_tanggal_dan_jurnal_mengaji_indikator_tercapai_kurang_terlampaui(): void
    {
        MasterKelasPengajian::seedStandar();
        $kelasLambatan = MasterKelasPengajian::where('kode_kelas', 'KLS_LAMBATAN_SMP')->firstOrFail();
        $kelasCepatan  = MasterKelasPengajian::where('kode_kelas', 'KLS_CEPATAN_SMP')->firstOrFail();
        $kelasReguler  = MasterKelasPengajian::where('kode_kelas', 'KLS_REGULER')->firstOrFail();

        $materiQuran = PondokMateri::create([
            'kode_materi'    => 'QUR-30',
            'nama_materi'    => 'Juz Amma',
            'kategori'       => 'al_quran',
            'jumlah_halaman' => 23,
        ]);

        $materiHadist = PondokMateri::create([
            'kode_materi'    => 'HAD-02',
            'nama_materi'    => 'Kitab Nawawi',
            'kategori'       => 'al_hadist',
            'jumlah_halaman' => 60,
            'jumlah_baris'   => 900,
        ]);

        // 1. Buat Target Pengajian per tanggal untuk masing-masing kelas
        $this->actingAs($this->user)->post(route('pondok.target.store'), [
            'tanggal'            => '2026-11-11',
            'kelas_pengajian_id' => $kelasLambatan->id,
            'pondok_materi_id'   => $materiQuran->id,
            'target_halaman'     => 3,
        ])->assertRedirect(route('pondok.target.index'));

        $this->actingAs($this->user)->post(route('pondok.target.store'), [
            'tanggal'            => '2026-11-11',
            'kelas_pengajian_id' => $kelasCepatan->id,
            'pondok_materi_id'   => $materiQuran->id,
            'target_halaman'     => 5,
        ])->assertRedirect(route('pondok.target.index'));

        $this->actingAs($this->user)->post(route('pondok.target.store'), [
            'tanggal'            => '2026-11-11',
            'kelas_pengajian_id' => $kelasReguler->id,
            'pondok_materi_id'   => $materiHadist->id,
            'target_halaman'     => 2,
            'target_baris'       => 30,
        ])->assertRedirect(route('pondok.target.index'));

        $targetLambatan = PondokTargetPengajian::where('kelas_pengajian_id', $kelasLambatan->id)->firstOrFail();
        $targetCepatan  = PondokTargetPengajian::where('kelas_pengajian_id', $kelasCepatan->id)->firstOrFail();
        $targetReguler  = PondokTargetPengajian::where('kelas_pengajian_id', $kelasReguler->id)->firstOrFail();

        // 2. Input Jurnal Mengaji Kelas 1: Pencapaian == Target (3 Hal vs 3 Hal) -> TERCAPAI
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'             => '2026-11-11',
            'kelas_pengajian_id'  => $kelasLambatan->id,
            'pondok_materi_id'    => $materiQuran->id,
            'target_pengajian_id' => $targetLambatan->id,
            'capaian_halaman'     => 3,
            'catatan'             => 'Bacaan lancar sesuai target',
        ])->assertRedirect(route('pondok.jurnal.index'));

        // 3. Input Jurnal Mengaji Kelas 2: Pencapaian < Target (3 Hal vs 5 Hal) -> KURANG
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'             => '2026-11-11',
            'kelas_pengajian_id'  => $kelasCepatan->id,
            'pondok_materi_id'    => $materiQuran->id,
            'target_pengajian_id' => $targetCepatan->id,
            'capaian_halaman'     => 3,
            'catatan'             => 'Perlu pengulangan tajwid',
        ])->assertRedirect(route('pondok.jurnal.index'));

        // 4. Input Jurnal Mengaji Kelas 3 (Al-Hadist): Pencapaian > Target (2 Hal 45 Baris vs 2 Hal 30 Baris) -> TERLAMPAUI
        $this->actingAs($this->user)->post(route('pondok.jurnal.store'), [
            'tanggal'             => '2026-11-11',
            'kelas_pengajian_id'  => $kelasReguler->id,
            'pondok_materi_id'    => $materiHadist->id,
            'target_pengajian_id' => $targetReguler->id,
            'capaian_halaman'     => 2,
            'capaian_baris'       => 45,
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

        // 5. Verifikasi tampilan halaman Jurnal Mengaji menampilkan ketiga indikator
        $jurnalPage = $this->actingAs($this->user)->get(route('pondok.jurnal.index'));
        $jurnalPage->assertStatus(200);
        $jurnalPage->assertSee('✓ Tercapai');
        $jurnalPage->assertSee('! Kurang');
        $jurnalPage->assertSee('↑ Terlampaui');
        $jurnalPage->assertSee('2 Halaman • 45 Baris');
    }
}

