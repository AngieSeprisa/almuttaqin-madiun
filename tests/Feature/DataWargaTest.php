<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataWargaTest extends TestCase
{
    use RefreshDatabase;

    protected User $yayasan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
    }

    public function test_sidebar_displays_menu_data_warga_with_four_submenus(): void
    {
        $res = $this->actingAs($this->yayasan)->get(route('beranda'));
        $res->assertStatus(200);
        $res->assertSee('navGroupDataWarga');
        $res->assertSee('Data Warga');
        $res->assertSee(route('siswa'));
        $res->assertSee(route('data-warga.guru'));
        $res->assertSee(route('data-warga.lulus'));
        $res->assertSee(route('data-warga.mutasi'));
    }

    public function test_menu_guru_only_displays_registered_teachers_and_excludes_unregistered_accounts(): void
    {
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        // 1. Akun yang sudah didaftarkan di menu Guru lembaga
        $guruTerdaftar = User::create([
            'name'               => 'Ustadz Ahmad Terdaftar',
            'email'              => 'ahmad.terdaftar@almuttaqin.id',
            'password'           => bcrypt('password'),
            'role'               => 'guru',
            'is_active'          => true,
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $smp->id,
            'mata_pelajaran'     => 'Matematika',
            'mapel_status'       => 'disetujui',
        ]);

        // 2. Akun yang baru dibuat di menu Pengguna namun belum didaftarkan di menu Guru lembaga
        $akunBaru = User::create([
            'name'               => 'Budi Akun Baru Belum Terdaftar',
            'email'              => 'budi.baru@almuttaqin.id',
            'password'           => bcrypt('password'),
            'role'               => 'guru',
            'is_active'          => true,
            'is_guru_sekolah'    => false,
            'primary_lembaga_id' => $smp->id,
            'mapel_status'       => 'belum_mengajukan',
        ]);

        $res = $this->actingAs($this->yayasan)->get(route('data-warga.guru'));
        $res->assertStatus(200);
        $res->assertSee('Ustadz Ahmad Terdaftar');
        $res->assertSee('Matematika');
        $res->assertDontSee('Budi Akun Baru Belum Terdaftar');
    }

    public function test_menu_lulus_and_mutasi_flows(): void
    {
        $siswa1 = Siswa::create([
            'nama_lengkap' => 'Santri Lulus Pondok',
            'jenjang'      => 'SMA',
            'kamar'        => 'Kamar A1',
            'status'       => 'aktif',
        ]);

        $siswa2 = Siswa::create([
            'nama_lengkap' => 'Santri Pindah Mutasi',
            'jenjang'      => 'SMP',
            'kamar'        => 'Kamar B2',
            'status'       => 'aktif',
        ]);

        // Simpan ke daftar Lulus
        $resLulus = $this->actingAs($this->yayasan)->post(route('data-warga.lulus.store'), [
            'siswa_id'          => $siswa1->id,
            'kategori_lulus'    => 'Pondok & Sekolah',
            'tahun_lulus'       => '2026',
            'tanggal_lulus'     => '2026-06-15',
            'keterangan_status' => 'Lulus Terbaik',
        ]);
        $resLulus->assertRedirect(route('data-warga.lulus'));

        $this->assertDatabaseHas('siswas', [
            'id'             => $siswa1->id,
            'status'         => 'lulus',
            'kategori_lulus' => 'Pondok & Sekolah',
            'tahun_lulus'    => '2026',
        ]);

        // Simpan ke daftar Mutasi
        $resMutasi = $this->actingAs($this->yayasan)->post(route('data-warga.mutasi.store'), [
            'siswa_id'          => $siswa2->id,
            'tanggal_mutasi'    => '2026-07-01',
            'tujuan_mutasi'     => 'MAN 2 Kota Madiun',
            'keterangan_status' => 'Pindah domisili orang tua',
        ]);
        $resMutasi->assertRedirect(route('data-warga.mutasi'));

        $this->assertDatabaseHas('siswas', [
            'id'            => $siswa2->id,
            'status'        => 'mutasi',
            'tujuan_mutasi' => 'MAN 2 Kota Madiun',
        ]);

        // Pastikan halaman Lulus dan Mutasi menampilkan masing-masing siswa
        $this->actingAs($this->yayasan)->get(route('data-warga.lulus'))
            ->assertStatus(200)
            ->assertSee('Santri Lulus Pondok')
            ->assertSee('Pondok & Sekolah');

        $this->actingAs($this->yayasan)->get(route('data-warga.mutasi'))
            ->assertStatus(200)
            ->assertSee('Santri Pindah Mutasi')
            ->assertSee('MAN 2 Kota Madiun');

        // Pastikan siswa lulus dan mutasi tidak tampil di daftar Siswa aktif
        $this->actingAs($this->yayasan)->get(route('siswa'))
            ->assertStatus(200)
            ->assertDontSee('Santri Lulus Pondok')
            ->assertDontSee('Santri Pindah Mutasi');
    }
}

