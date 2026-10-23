<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlottingKelasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_sidebar_pengaturan_sekolah_has_plotting_kelas_submenu(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('beranda'));

        $response->assertStatus(200);
        $response->assertSee('navGroupPengaturan');
        $response->assertSee('Plotting Kelas');
        $response->assertSee(route('plotting-kelas.index'));
    }

    public function test_admin_smp_can_access_plotting_kelas_and_see_smp_classes(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        // Buat data siswa SMP terplotting dan belum terplotting
        Siswa::create([
            'nama_lengkap' => 'Siswa SMP Terdaftar',
            'kelas'        => 'VII A',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
            'kamar'        => 'Abu Bakar',
        ]);
        Siswa::create([
            'nama_lengkap' => 'Siswa SMP Belum Terdaftar',
            'kelas'        => null,
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
            'kamar'        => 'Umar',
        ]);

        // Buat data siswa Reguler (tidak boleh masuk ke data sekolah)
        Siswa::create([
            'nama_lengkap' => 'Santri Reguler Murni',
            'kelas'        => null,
            'jenjang'      => 'Reguler',
            'status'       => 'aktif',
            'kamar'        => 'Utsman',
        ]);

        // Buat data siswa SMA (tidak boleh masuk ke SMP)
        Siswa::create([
            'nama_lengkap' => 'Siswa SMA Khusus',
            'kelas'        => 'X A',
            'jenjang'      => 'SMA',
            'status'       => 'aktif',
            'kamar'        => 'Ali',
        ]);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('plotting-kelas.index'));

        $response->assertStatus(200);

        // Menampilkan kelas SMP (VII A, VII B, dst.)
        $response->assertSee('Kelas VII A');
        $response->assertSee('Kelas VII B');
        $response->assertSee('Kelas VIII A');

        // Menampilkan tombol aksi logo mata di tabel kelas
        $response->assertSee('pk-btn-eye');

        // Siswa Reguler dan SMA TIDAK BOLEH tampil di data SMP
        $response->assertDontSee('Santri Reguler Murni');
        $response->assertDontSee('Siswa SMA Khusus');

        // Menampilkan tab Belum Terdaftar
        $response->assertSee('Belum Terdaftar');
        $response->assertSee('Siswa SMP Belum Terdaftar');
        $response->assertSee('pk-btn-mutasi-row');
    }

    public function test_reguler_students_are_strictly_excluded_from_school_plotting(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        Siswa::create([
            'nama_lengkap' => 'Santri Pondok Reguler A',
            'kelas'        => 'VII A',
            'jenjang'      => 'Reguler',
            'status'       => 'aktif',
            'kamar'        => 'Asrama 1',
        ]);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('plotting-kelas.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Santri Pondok Reguler A');
    }

    public function test_user_can_mutate_student_to_class(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $siswa = Siswa::create([
            'nama_lengkap' => 'Calon Siswa Baru VII B',
            'kelas'        => null,
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
            'kamar'        => 'Asrama 2',
        ]);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-kelas.mutasi'), [
                'siswa_id' => $siswa->id,
                'kelas'    => 'VII B',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('siswas', [
            'id'    => $siswa->id,
            'kelas' => 'VII B',
        ]);
    }

    public function test_user_can_unassign_student_from_class(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $siswa = Siswa::create([
            'nama_lengkap' => 'Siswa Ingin Pindah VII A',
            'kelas'        => 'VII A',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
            'kamar'        => 'Asrama 2',
        ]);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-kelas.unassign'), [
                'siswa_id' => $siswa->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('siswas', [
            'id'    => $siswa->id,
            'kelas' => null,
        ]);
    }

    public function test_endpoint_anggota_kelas_returns_class_students(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $siswa = Siswa::create([
            'nama_lengkap' => 'Siswa Anggota Kelas VII A',
            'kelas'        => 'VII A',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
            'kamar'        => 'Asrama 3',
        ]);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('plotting-kelas.anggota', ['kelas' => 'VII A', 'jenjang' => 'SMP']));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'kelas'   => 'VII A',
        ]);
        $response->assertJsonFragment([
            'nama_lengkap' => 'Siswa Anggota Kelas VII A',
        ]);
    }

    public function test_pondok_user_cannot_access_plotting_kelas(): void
    {
        $adminPondok = User::where('email', 'admin.pondok@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($adminPondok)
            ->withSession(['active_lembaga' => 'pondok'])
            ->get(route('plotting-kelas.index'));

        $response->assertStatus(403);
    }
}
