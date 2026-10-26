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

    public function test_yayasan_can_access_master_kelas_index(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();
        $this->assertNotNull($yayasan);

        $response = $this->actingAs($yayasan)->get(route('master.kelas.index'));
        $response->assertStatus(200);
        $response->assertSee('Master Ruang Kelas');
    }

    public function test_yayasan_can_create_new_classroom(): void
    {
        $yayasan = User::where('role', 'yayasan')->first();
        $smp = Lembaga::where('kode', 'smp')->first();

        $response = $this->actingAs($yayasan)->post(route('master.kelas.store'), [
            'lembaga_id' => $smp->id,
            'jenjang'    => 'SMP',
            'nama_kelas' => '7-Testing',
            'tingkat'    => 7,
            'urutan'     => 99,
            'is_active'  => 1,
        ]);

        $response->assertRedirect(route('master.kelas.index'));
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
}
