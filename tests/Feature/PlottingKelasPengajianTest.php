<?php

namespace Tests\Feature;

use App\Models\MasterKelasPengajian;
use App\Models\Pendaftaran;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Database\Seeders\MasterKelasPengajianSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlottingKelasPengajianTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->seed(MasterKelasPengajianSeeder::class);
    }

    public function test_halaman_plotting_pengajian_dapat_dibuka_oleh_yayasan_dan_auto_seed_standar(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($yayasan)->get(route('plotting-pengajian.index'));

        $response->assertStatus(200);
        $response->assertSee('Plotting Kelas Pengajian');
        $response->assertSee('Kelas Lambatan SMP');
        $response->assertSee('Kelas Cepatan SMP');
        $response->assertSee('Kelas Pegon Bacaan SMP');
        $response->assertSee('Kelas Reguler');

        $this->assertSame(4, MasterKelasPengajian::count());
    }

    public function test_dapat_menambah_master_kelas_pengajian_baru(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($yayasan)->post(route('plotting-pengajian.store-kelas'), [
            'nama_kelas' => 'Kelas Nahwu Shorof Tingkat Lanjut',
            'kode_kelas' => 'KLS_NAHWU_LANJUT',
            'jenjang'    => 'smp',
        ]);

        $response->assertRedirect(route('plotting-pengajian.index', ['tab' => 'kelas']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('master_kelas_pengajians', [
            'nama_kelas' => 'Kelas Nahwu Shorof Tingkat Lanjut',
            'kode_kelas' => 'KLS_NAHWU_LANJUT',
            'jenjang'    => 'smp',
        ]);
    }

    public function test_dapat_menempatkan_santri_ke_kelas_pengajian_secara_massal(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $pendaftaran1 = Pendaftaran::create([
            'nama_lengkap'  => 'Santri Pengajian Satu',
            'jenis_kelamin' => 'laki-laki',
            'jenjang'       => 'SMP',
            'status'        => 'Diterima',
        ]);
        $pendaftaran2 = Pendaftaran::create([
            'nama_lengkap'  => 'Santri Pengajian Dua',
            'jenis_kelamin' => 'perempuan',
            'jenjang'       => 'SMP',
            'status'        => 'Diterima',
        ]);

        $siswa1 = Siswa::create([
            'nama_lengkap'   => 'Santri Pengajian Satu',
            'pendaftaran_id' => $pendaftaran1->id,
            'kamar'          => 'Kamar Abu Bakar',
            'jenjang'        => 'SMP',
            'kelas'          => 'VII A',
            'status'         => 'aktif',
        ]);

        $siswa2 = Siswa::create([
            'nama_lengkap'   => 'Santri Pengajian Dua',
            'pendaftaran_id' => $pendaftaran2->id,
            'kamar'          => 'Kamar Khadijah',
            'jenjang'        => 'SMP',
            'kelas'          => 'VII B',
            'status'         => 'aktif',
        ]);

        $kelasCepatan = MasterKelasPengajian::where('kode_kelas', 'KLS_CEPATAN_SMP')->firstOrFail();

        $response = $this->actingAs($yayasan)->post(route('plotting-pengajian.mutasi'), [
            'siswa_id'           => [$siswa1->id, $siswa2->id],
            'kelas_pengajian_id' => $kelasCepatan->id,
        ]);

        $response->assertSessionHas('success');

        $this->assertDatabaseHas('siswas', [
            'id'                 => $siswa1->id,
            'kelas_pengajian_id' => $kelasCepatan->id,
        ]);
        $this->assertDatabaseHas('siswas', [
            'id'                 => $siswa2->id,
            'kelas_pengajian_id' => $kelasCepatan->id,
        ]);
    }

    public function test_dapat_mengeluarkan_santri_dari_kelas_pengajian_unassign(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $kelas = MasterKelasPengajian::where('kode_kelas', 'KLS_LAMBATAN_SMP')->firstOrFail();

        $siswa = Siswa::create([
            'nama_lengkap'       => 'Santri Yang Mau Dikeluarkan',
            'kamar'              => 'Reguler',
            'status'             => 'aktif',
            'kelas_pengajian_id' => $kelas->id,
        ]);

        $this->assertNotNull($siswa->kelas_pengajian_id);

        // 1. Unassign via route unassign
        $response = $this->actingAs($yayasan)->post(route('plotting-pengajian.unassign'), [
            'siswa_id' => $siswa->id,
        ]);

        $response->assertSessionHas('success');
        $siswa->refresh();
        $this->assertNull($siswa->kelas_pengajian_id);

        // 2. Assign kembali, lalu unassign via mutasi payload 'unassign'
        $siswa->update(['kelas_pengajian_id' => $kelas->id]);
        $this->assertSame($kelas->id, $siswa->fresh()->kelas_pengajian_id);

        $responseMutasi = $this->actingAs($yayasan)->post(route('plotting-pengajian.mutasi'), [
            'siswa_id'           => [$siswa->id],
            'kelas_pengajian_id' => 'unassign',
        ]);

        $responseMutasi->assertSessionHas('success');
        $this->assertNull($siswa->fresh()->kelas_pengajian_id);
    }

    public function test_proteksi_hapus_kelas_pengajian_jika_masih_memiliki_santri_aktif(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $kelas = MasterKelasPengajian::where('kode_kelas', 'KLS_PEGON_SMP')->firstOrFail();

        $siswa = Siswa::create([
            'nama_lengkap'       => 'Santri Pegon',
            'status'             => 'aktif',
            'kelas_pengajian_id' => $kelas->id,
        ]);

        // Percobaan 1: Hapus saat masih ada santri -> harus ditolak
        $delResp1 = $this->actingAs($yayasan)->delete(route('plotting-pengajian.destroy-kelas', $kelas->id));
        $delResp1->assertSessionHas('error');
        $this->assertDatabaseHas('master_kelas_pengajians', ['id' => $kelas->id]);

        // Percobaan 2: Pindahkan santri terlebih dahulu -> hapus berhasil
        $siswa->update(['kelas_pengajian_id' => null]);

        $delResp2 = $this->actingAs($yayasan)->delete(route('plotting-pengajian.destroy-kelas', $kelas->id));
        $delResp2->assertSessionHas('success');
        $this->assertDatabaseMissing('master_kelas_pengajians', ['id' => $kelas->id]);
    }

    public function test_anggota_kelas_pengajian_dan_tab_belum_terdaftar(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $kelas = MasterKelasPengajian::where('kode_kelas', 'KLS_REGULER')->firstOrFail();

        $pPutra = Pendaftaran::create([
            'nama_lengkap'  => 'Santri Ahmad Putra',
            'jenis_kelamin' => 'laki-laki',
            'jenjang'       => 'SMP',
            'status'        => 'Diterima',
        ]);
        $pPutri = Pendaftaran::create([
            'nama_lengkap'  => 'Santri Aisyah Putri',
            'jenis_kelamin' => 'perempuan',
            'jenjang'       => 'SMP',
            'status'        => 'Diterima',
        ]);

        $siswaPutra = Siswa::create([
            'nama_lengkap'       => 'Santri Ahmad Putra',
            'pendaftaran_id'     => $pPutra->id,
            'status'             => 'aktif',
            'kelas_pengajian_id' => $kelas->id,
        ]);

        $siswaPutri = Siswa::create([
            'nama_lengkap'       => 'Santri Aisyah Putri',
            'pendaftaran_id'     => $pPutri->id,
            'status'             => 'aktif',
            'kelas_pengajian_id' => null,
        ]);

        // 1. Santri belum terdaftar muncul di tab belum_terdaftar
        $respBelum = $this->actingAs($yayasan)->get(route('plotting-pengajian.index', ['tab' => 'belum_terdaftar']));
        $respBelum->assertStatus(200);
        $respBelum->assertSee('Santri Aisyah Putri');
        $respBelum->assertDontSee('Santri Ahmad Putra');

        // 2. Santri yang sudah diplot muncul di endpoint anggota kelas untuk modal
        $respAnggota = $this->actingAs($yayasan)->getJson(route('plotting-pengajian.anggota', $kelas->id));
        $respAnggota->assertStatus(200);
        $respAnggota->assertJsonFragment(['nama_lengkap' => 'Santri Ahmad Putra']);
    }
}

