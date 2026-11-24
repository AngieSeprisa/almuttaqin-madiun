<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Lembaga;
use App\Models\TimeSlot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
        $this->artisan('db:seed', ['--class' => 'LembagaAndUserSeeder']);
    }

    public function test_master_kelas_route_redirects_to_plotting_kelas(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();
        $this->assertNotNull($yayasan);

        $response = $this->actingAs($yayasan)->get(route('master.kelas.index'));
        $response->assertRedirect(route('plotting-kelas.index'));

        $followed = $this->actingAs($yayasan)->get(route('plotting-kelas.index'));
        $followed->assertStatus(200);
        $followed->assertSee('Plotting Kelas');
        $followed->assertSee('Tambah Kelas');
    }

    public function test_yayasan_can_create_new_classroom(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();
        $smp = Lembaga::where('kode', 'smp')->first();

        $response = $this->actingAs($yayasan)->post(route('plotting-kelas.store-kelas'), [
            'lembaga_id' => $smp->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => '7-Testing',
            'tingkat'    => 7,
            'urutan'     => 99,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classrooms', [
            'nama_kelas' => '7-Testing',
            'jenjang'    => 'SMP',
            'tingkat'    => 7,
        ]);
    }

    public function test_yayasan_can_access_master_jam_pelajaran(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();

        $response = $this->actingAs($yayasan)->get(route('master.jam-pelajaran.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Jam Pelajaran');
    }

    public function test_yayasan_can_create_new_time_slot(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();

        $response = $this->actingAs($yayasan)->post(route('master.jam-pelajaran.store'), [
            'hari_list'     => ['Senin', 'Selasa'],
            'jenjang'       => 'Semua',
            'jam_ke'        => 9,
            'waktu_mulai'   => '15:15',
            'waktu_selesai' => '16:00',
            'tipe_kegiatan' => 'KBM',
            'label'         => 'Jam Tambahan',
            'is_active'     => 1,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('time_slots', [
            'hari'          => 'Senin',
            'jam_ke'        => 9,
            'label'         => 'Jam Tambahan',
            'waktu_mulai'   => '15:15',
        ]);
        $this->assertDatabaseHas('time_slots', [
            'hari'          => 'Selasa',
            'jam_ke'        => 9,
            'label'         => 'Jam Tambahan',
            'waktu_mulai'   => '15:15',
        ]);
    }

    public function test_yayasan_can_access_master_lembaga(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();

        $response = $this->actingAs($yayasan)->get(route('master.lembaga.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Lembaga');
        $response->assertSee('SMP AL MUTTAQIN');
    }

    public function test_cannot_delete_core_lembaga(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();
        $smp = Lembaga::where('kode', 'smp')->first();

        $response = $this->actingAs($yayasan)->delete(route('master.lembaga.destroy', $smp->id));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('lembagas', ['kode' => 'smp']);
    }

    public function test_yayasan_can_create_new_lembaga_with_normalized_kode_and_blank_email_dash(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();

        $response = $this->actingAs($yayasan)->post(route('master.lembaga.store'), [
            '_form_mode' => 'tambah',
            'kode'       => '  KB TK  ',
            'singkatan'  => 'KB TK',
            'nama'       => 'KB TK AL MUTTAQIN',
            'kategori'   => 'formal',
            'alamat'     => 'Jl. Nogososro No. 26',
            'telepon'    => '081234567890',
            'email'      => '-',
            'is_active'  => '1',
        ]);

        $response->assertRedirect(route('master.lembaga.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('lembagas', [
            'kode'      => 'kb-tk',
            'singkatan' => 'KB TK',
            'nama'      => 'KB TK AL MUTTAQIN',
            'kategori'  => 'formal',
            'email'     => null,
            'is_active' => true,
        ]);

        // Cek lembaga baru tampil di halaman Master Lembaga
        $index = $this->actingAs($yayasan)->get(route('master.lembaga.index'));
        $index->assertStatus(200);
        $index->assertSee('KB TK AL MUTTAQIN');
        $index->assertSee('kode: kb-tk');
    }

    public function test_create_lembaga_duplicate_kode_displays_validation_error(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();

        $response = $this->actingAs($yayasan)
            ->from(route('master.lembaga.index'))
            ->post(route('master.lembaga.store'), [
                '_form_mode' => 'tambah',
                'kode'       => 'SMP',
                'singkatan'  => 'SMP 2',
                'nama'       => 'SMP Duplikat',
                'kategori'   => 'formal',
                'is_active'  => '1',
            ]);

        $response->assertRedirect(route('master.lembaga.index'));
        $response->assertSessionHasErrors('kode');

        $follow = $this->actingAs($yayasan)->get(route('master.lembaga.index'));
        $follow->assertStatus(200);
        $follow->assertSee('Gagal menyimpan data lembaga:');
    }

    public function test_non_yayasan_with_master_lembaga_create_permission_can_add_and_see_new_lembaga(): void
    {
        $adminSmp = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        \App\Models\UserPermission::updateOrCreate(
            ['user_id' => $adminSmp->id, 'menu_code' => 'master_lembaga'],
            ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => false]
        );

        $response = $this->actingAs($adminSmp)->post(route('master.lembaga.store'), [
            '_form_mode' => 'tambah',
            'kode'       => 'tpq',
            'singkatan'  => 'TPQ',
            'nama'       => 'TPQ AL MUTTAQIN',
            'kategori'   => 'pesantren',
            'is_active'  => '1',
        ]);

        $response->assertRedirect(route('master.lembaga.index'));
        $this->assertDatabaseHas('lembagas', ['kode' => 'tpq', 'nama' => 'TPQ AL MUTTAQIN']);

        $index = $this->actingAs($adminSmp)->get(route('master.lembaga.index'));
        $index->assertStatus(200);
        $index->assertSee('TPQ AL MUTTAQIN');
    }
}
