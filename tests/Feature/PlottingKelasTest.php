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

        // Menampilkan tombol aksi logo mata di tabel kelas dan elemen mobile
        $response->assertSee('pk-btn-eye');
        $response->assertSee('pk-kelas-sub-mobile');
        $response->assertSee('pk-nisn-sub-mobile');

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

    public function test_user_can_create_new_class_and_see_it_in_plotting_table(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-kelas.store-kelas'), [
                'lembaga_id' => $smp->id,
                'jenjang'    => 'SMP',
                'nama_kelas' => 'VII C',
                'tingkat'    => 7,
            ]);

        $response->assertRedirect(route('plotting-kelas.index', ['lembaga' => 'smp', 'tab' => 'kelas']));
        $this->assertDatabaseHas('classrooms', [
            'nama_kelas' => 'VII C',
            'jenjang'    => 'SMP',
            'tingkat'    => 7,
        ]);

        // Verifikasi kelas baru muncul di tabel
        $viewResp = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('plotting-kelas.index'));

        $viewResp->assertStatus(200);
        $viewResp->assertSee('Kelas VII C');
    }

    public function test_user_can_update_class_and_students_are_synchronized(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $classroom = \App\Models\Classroom::create([
            'lembaga_id' => $smp->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => 'VII D',
            'tingkat'    => 7,
            'urutan'     => 10,
            'is_active'  => true,
        ]);

        $siswa = Siswa::create([
            'nama_lengkap' => 'Siswa Kelas VII D',
            'kelas'        => 'VII D',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
            'kamar'        => 'Kamar 1',
        ]);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->put(route('plotting-kelas.update-kelas', $classroom->id), [
                'nama_kelas' => 'VII D Unggulan',
                'tingkat'    => 7,
                'urutan'     => 10,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'id'         => $classroom->id,
            'nama_kelas' => 'VII D Unggulan',
        ]);
        $this->assertDatabaseHas('siswas', [
            'id'    => $siswa->id,
            'kelas' => 'VII D Unggulan',
        ]);
    }

    public function test_user_cannot_delete_class_with_students(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $classroom = \App\Models\Classroom::create([
            'lembaga_id' => $smp->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => 'VII E',
            'tingkat'    => 7,
            'urutan'     => 11,
            'is_active'  => true,
        ]);

        Siswa::create([
            'nama_lengkap' => 'Siswa Kelas VII E',
            'kelas'        => 'VII E',
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
            'kamar'        => 'Kamar 2',
        ]);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->delete(route('plotting-kelas.destroy-kelas', $classroom->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
        ]);
    }

    public function test_user_can_delete_empty_class(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $classroom = \App\Models\Classroom::create([
            'lembaga_id' => $smp->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => 'VII Kosong',
            'tingkat'    => 7,
            'urutan'     => 12,
            'is_active'  => true,
        ]);

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->delete(route('plotting-kelas.destroy-kelas', $classroom->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('classrooms', [
            'id' => $classroom->id,
        ]);
    }

    public function test_bulk_mutasi_unassign_and_bulk_delete_kelas_with_filament_table_layout(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $cEmpty1 = \App\Models\Classroom::create([
            'lembaga_id' => $smp->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => 'VIII Kosong 1',
            'tingkat'    => 8,
            'urutan'     => 20,
            'is_active'  => true,
        ]);
        $cEmpty2 = \App\Models\Classroom::create([
            'lembaga_id' => $smp->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => 'VIII Kosong 2',
            'tingkat'    => 8,
            'urutan'     => 21,
            'is_active'  => true,
        ]);
        $cOccupied = \App\Models\Classroom::create([
            'lembaga_id' => $smp->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => 'VIII Terisi',
            'tingkat'    => 8,
            'urutan'     => 22,
            'is_active'  => true,
        ]);

        $s1 = Siswa::create([
            'nama_lengkap' => 'Bulk Siswa SMP 1',
            'kelas'        => null,
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
        ]);
        $s2 = Siswa::create([
            'nama_lengkap' => 'Bulk Siswa SMP 2',
            'kelas'        => null,
            'jenjang'      => 'SMP',
            'status'       => 'aktif',
        ]);

        // Bulk mutasi 2 siswa sekaligus ke VIII Terisi
        $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-kelas.mutasi'), [
                'siswa_id' => "{$s1->id},{$s2->id}",
                'kelas'    => 'VIII Terisi',
            ])
            ->assertRedirect();

        $this->assertEquals('VIII Terisi', $s1->fresh()->kelas);
        $this->assertEquals('VIII Terisi', $s2->fresh()->kelas);

        // Verifikasi struktur Filament v4 table & hilangnya kebocoran .mobile-card-list
        $viewRes = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('plotting-kelas.index'));
        $viewRes->assertStatus(200);
        $viewRes->assertSee('fi-ta-ctn');
        $viewRes->assertSee('fi-ta-bulk-btn');
        $viewRes->assertSee('Terapkan filter');
        $viewRes->assertDontSee('mobile-card-list');

        // Bulk delete kelas: yang kosong terhapus, yang terisi terlindungi
        $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-kelas.destroy-bulk-kelas'), [
                'kelas_ids' => "{$cEmpty1->id},{$cEmpty2->id},{$cOccupied->id}",
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('classrooms', ['id' => $cEmpty1->id]);
        $this->assertDatabaseMissing('classrooms', ['id' => $cEmpty2->id]);
        $this->assertDatabaseHas('classrooms', ['id' => $cOccupied->id]);

        // Bulk unassign 2 siswa sekaligus
        $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-kelas.unassign'), [
                'siswa_id' => "{$s1->id},{$s2->id}",
            ])
            ->assertRedirect();

        $this->assertNull($s1->fresh()->kelas);
        $this->assertNull($s2->fresh()->kelas);
    }
}
