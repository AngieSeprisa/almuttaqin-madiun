<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\MasterKelasPengajian;
use App\Models\PondokMateri;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruPondokTest extends TestCase
{
    use RefreshDatabase;

    protected User $yayasan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
    }

    public function test_sidebar_menampilkan_plotting_guru_pondok_di_menu_pengaturan_pondok(): void
    {
        $beranda = $this->actingAs($this->yayasan)->get(route('beranda'));
        $beranda->assertStatus(200);
        $beranda->assertSee('navGroupPengaturanPondok');
        $beranda->assertSee('Pengaturan Pondok');
        $beranda->assertSee(route('guru-pondok.index'));
        $beranda->assertSee('Plotting Guru Pondok');

        $index = $this->actingAs($this->yayasan)->get(route('guru-pondok.index'));
        $index->assertStatus(200);
        $index->assertSee('Plotting Guru Pondok');
        $index->assertSee('Tambah Guru Pondok');
        $index->assertSee('Pengaturan Pondok');
        $index->assertSee('nav-group open', false);
        $index->assertSee('subnav-item active', false);
    }

    public function test_crud_plotting_guru_pondok_filter_dan_sinkronisasi(): void
    {
        MasterKelasPengajian::seedStandar();
        $kelasList = MasterKelasPengajian::orderBy('id')->get();
        $kelas1 = $kelasList->first();
        $kelas2 = $kelasList->skip(1)->first();

        $materi1 = PondokMateri::create([
            'kode_materi'    => 'QUR-01',
            'nama_materi'    => "Al-Qur'an Cepatan",
            'kategori'       => PondokMateri::KATEGORI_AL_QURAN,
            'jumlah_halaman' => 341,
            'is_active'      => true,
        ]);

        $materi2 = PondokMateri::create([
            'kode_materi'    => 'HAD-01',
            'nama_materi'    => 'Kitab Sholah',
            'kategori'       => PondokMateri::KATEGORI_AL_HADIST,
            'jumlah_halaman' => 80,
            'jumlah_baris'   => 1200,
            'is_active'      => true,
        ]);

        $smp = Lembaga::where('kode', 'smp')->firstOrFail();
        $pondok = Lembaga::where('kode', 'pondok')->firstOrFail();

        // Akun guru yang juga mengajar di sekolah (tidak boleh menimpa status guru sekolah)
        $calonGuru = User::create([
            'name'               => 'Ustadz Hasan Pondok',
            'email'              => 'hasan.pondok@almuttaqin.id',
            'password'           => bcrypt('password'),
            'role'               => 'guru',
            'is_active'          => true,
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $smp->id,
            'mata_pelajaran'     => 'PAI',
            'mapel_status'       => 'disetujui',
        ]);
        $calonGuru->lembagas()->sync([$smp->id]);

        // 1. Tambah Plotting Guru Pondok
        $storeRes = $this->actingAs($this->yayasan)->post(route('guru-pondok.store'), [
            'user_id'             => $calonGuru->id,
            'kelas_pengajian_ids' => [$kelas1->id, $kelas2->id],
            'pondok_materi'       => [$materi1->nama_materi],
            'pondok_catatan'      => "Pengampu Ba'da Maghrib",
        ]);
        $storeRes->assertRedirect(route('guru-pondok.index'));

        $calonGuru->refresh();
        $this->assertTrue($calonGuru->is_guru_pondok);
        $this->assertTrue($calonGuru->is_guru_sekolah);
        $this->assertEquals([$kelas1->id, $kelas2->id], $calonGuru->daftar_kelas_pengajian_ids);
        $this->assertEquals("Al-Qur'an Cepatan", $calonGuru->pondok_materi);
        $this->assertEquals("Pengampu Ba'da Maghrib", $calonGuru->pondok_catatan);
        $this->assertTrue($calonGuru->lembagas->contains('id', $pondok->id));
        $this->assertEquals($smp->id, $calonGuru->primary_lembaga_id);

        // 2. Filter kelas pengajian, materi, dan search
        $this->actingAs($this->yayasan)
            ->get(route('guru-pondok.index', ['kelas_pengajian_id' => $kelas1->id]))
            ->assertStatus(200)
            ->assertSee('Ustadz Hasan Pondok')
            ->assertSee($kelas1->nama_kelas);

        $this->actingAs($this->yayasan)
            ->get(route('guru-pondok.index', ['materi' => $materi1->nama_materi]))
            ->assertStatus(200)
            ->assertSee('Ustadz Hasan Pondok');

        $this->actingAs($this->yayasan)
            ->get(route('guru-pondok.index', ['search' => 'Hasan']))
            ->assertStatus(200)
            ->assertSee('Ustadz Hasan Pondok');

        // 3. Sinkron dengan Data Warga -> Guru dan Jurnal Mengaji
        $this->actingAs($this->yayasan)
            ->get(route('data-warga.guru', ['lembaga_id' => $pondok->id]))
            ->assertStatus(200)
            ->assertSee('Ustadz Hasan Pondok')
            ->assertSee($kelas1->nama_kelas);

        $this->actingAs($this->yayasan)
            ->get(route('pondok.jurnal.index'))
            ->assertStatus(200)
            ->assertSee('Ustadz Hasan Pondok');

        // 4. Ubah Plotting Guru Pondok
        $updateRes = $this->actingAs($this->yayasan)->put(route('guru-pondok.update', $calonGuru->id), [
            'kelas_pengajian_ids' => [$kelas2->id],
            'pondok_materi'       => [$materi1->nama_materi, $materi2->nama_materi],
            'pondok_catatan'      => "Pengampu Ba'da Subuh",
        ]);
        $updateRes->assertRedirect(route('guru-pondok.index'));

        $calonGuru->refresh();
        $this->assertEquals([$kelas2->id], $calonGuru->daftar_kelas_pengajian_ids);
        $this->assertEquals("Al-Qur'an Cepatan, Kitab Sholah", $calonGuru->pondok_materi);
        $this->assertEquals("Pengampu Ba'da Subuh", $calonGuru->pondok_catatan);

        // 5. Hapus dari Guru Pondok (akun & status guru sekolah tetap utuh)
        $deleteRes = $this->actingAs($this->yayasan)->delete(route('guru-pondok.destroy', $calonGuru->id));
        $deleteRes->assertRedirect(route('guru-pondok.index'));

        $calonGuru->refresh();
        $this->assertDatabaseHas('users', ['id' => $calonGuru->id]);
        $this->assertFalse($calonGuru->is_guru_pondok);
        $this->assertNull($calonGuru->pondok_kelas_ids);
        $this->assertNull($calonGuru->pondok_materi);
        $this->assertNull($calonGuru->pondok_catatan);
        $this->assertTrue($calonGuru->is_guru_sekolah);
    }

    public function test_guru_pondok_view_only_menyembunyikan_tombol_aksi_dan_memblokir_mutasi(): void
    {
        MasterKelasPengajian::seedStandar();
        $kelas = MasterKelasPengajian::firstOrFail();
        $pondok = Lembaga::where('kode', 'pondok')->firstOrFail();
        $staff = User::where('role', 'admin_lembaga')->firstOrFail();

        $this->actingAs($this->yayasan)->put(route('pengguna.update', $staff->id), [
            'name'      => $staff->name,
            'email'     => $staff->email,
            'role'      => 'admin_lembaga',
            'jabatan'   => 'Staff Pondok',
            'lembagas'  => [$pondok->id],
            'is_active' => 1,
            'permissions' => [
                'guru_pondok' => [
                    'view' => 1,
                ],
            ],
        ])->assertRedirect(route('pengguna.index'));

        $staff->refresh();
        $this->assertTrue($staff->canDo('guru_pondok', 'view'));
        $this->assertFalse($staff->canDo('guru_pondok', 'create'));
        $this->assertFalse($staff->canDo('guru_pondok', 'edit'));
        $this->assertFalse($staff->canDo('guru_pondok', 'delete'));

        $targetGuru = User::create([
            'name'             => 'Ustadz Target ViewOnly',
            'email'            => 'target.pondok@almuttaqin.id',
            'password'         => bcrypt('password'),
            'role'             => 'guru',
            'is_active'        => true,
            'is_guru_pondok'   => true,
            'pondok_kelas_ids' => [$kelas->id],
        ]);

        $res = $this->actingAs($staff)->get(route('guru-pondok.index'));
        $res->assertStatus(200);
        $res->assertSee('Ustadz Target ViewOnly');
        $res->assertDontSee('onclick="bukaModalTambahGuruPondok()"', false);
        $res->assertDontSee('id="modalTambahGuruPondok"', false);
        $res->assertDontSee('id="modalUbahGuruPondok"', false);
        $res->assertDontSee('action-btn-edit', false);
        $res->assertDontSee('action-btn-delete', false);

        $this->actingAs($staff)->post(route('guru-pondok.store'), [
            'user_id'             => $targetGuru->id,
            'kelas_pengajian_ids' => [$kelas->id],
        ])->assertRedirect(route('beranda'));

        $this->actingAs($staff)->postJson(route('guru-pondok.store'), [
            'user_id'             => $targetGuru->id,
            'kelas_pengajian_ids' => [$kelas->id],
        ])->assertStatus(403);

        $this->actingAs($staff)->putJson(route('guru-pondok.update', $targetGuru->id), [
            'kelas_pengajian_ids' => [$kelas->id],
        ])->assertStatus(403);

        $this->actingAs($staff)->deleteJson(route('guru-pondok.destroy', $targetGuru->id))
            ->assertStatus(403);
    }
}
