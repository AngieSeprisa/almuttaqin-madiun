<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\Lembaga;
use App\Models\PondokMateri;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_semua_menu_dan_submenu_ditampilkan_terpisah_pada_halaman_hak_akses(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        $createRes = $this->actingAs($yayasan)->get(route('pengguna.create'));
        $createRes->assertStatus(200);

        $editRes = $this->actingAs($yayasan)->get(route('pengguna.edit', $guru->id));
        $editRes->assertStatus(200);

        foreach (UserController::systemMenuGroups() as $groupName => $items) {
            $createRes->assertSee($groupName, false);
            $editRes->assertSee($groupName, false);

            foreach ($items as $code => $label) {
                $createRes->assertSee('name="permissions[' . $code . '][view]"', false);
                $createRes->assertSee('name="permissions[' . $code . '][create]"', false);
                $createRes->assertSee('name="permissions[' . $code . '][edit]"', false);
                $createRes->assertSee('name="permissions[' . $code . '][delete]"', false);

                $editRes->assertSee('name="permissions[' . $code . '][view]"', false);
                $editRes->assertSee('name="permissions[' . $code . '][create]"', false);
                $editRes->assertSee('name="permissions[' . $code . '][edit]"', false);
                $editRes->assertSee('name="permissions[' . $code . '][delete]"', false);
            }
        }
    }

    public function test_hak_akses_granular_view_only_dan_pemisahan_submenu_benar_benar_diterapkan(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();
        $lembagaIds = Lembaga::pluck('id')->all();

        // Atur hak akses akun melalui form Edit Pengguna:
        // - Pondok -> Jurnal Mengaji: hanya VIEW (tidak bisa create/edit/delete)
        // - Pondok -> Target Pengajian: TIDAK dicentang sama sekali
        // - Pondok -> Daftar Materi: VIEW + CREATE (tidak bisa edit/delete)
        // - Perpustakaan -> Daftar Buku: hanya VIEW
        // - Perpustakaan -> Pinjam Buku: TIDAK dicentang
        $updateRes = $this->actingAs($yayasan)->put(route('pengguna.update', $guru->id), [
            'name'      => $guru->name,
            'email'     => $guru->email,
            'role'      => 'guru',
            'jabatan'   => 'Guru Pengajian',
            'lembagas'  => $lembagaIds,
            'is_active' => 1,
            'permissions' => [
                'jurnal_mengaji' => [
                    'view' => 1,
                ],
                'materi_pondok' => [
                    'view'   => 1,
                    'create' => 1,
                ],
                'daftar_buku' => [
                    'view' => 1,
                ],
            ],
        ]);
        $updateRes->assertRedirect(route('pengguna.index'));

        $guru->refresh();

        // Verifikasi nilai canDo() pada model User
        $this->assertTrue($guru->canDo('jurnal_mengaji', 'view'));
        $this->assertFalse($guru->canDo('jurnal_mengaji', 'create'));
        $this->assertFalse($guru->canDo('jurnal_mengaji', 'edit'));
        $this->assertFalse($guru->canDo('jurnal_mengaji', 'delete'));

        $this->assertFalse($guru->canDo('target_pengajian', 'view'));
        $this->assertFalse($guru->canDo('target_pengajian', 'create'));

        $this->assertTrue($guru->canDo('materi_pondok', 'view'));
        $this->assertTrue($guru->canDo('materi_pondok', 'create'));
        $this->assertFalse($guru->canDo('materi_pondok', 'edit'));
        $this->assertFalse($guru->canDo('materi_pondok', 'delete'));

        $this->assertTrue($guru->canDo('daftar_buku', 'view'));
        $this->assertFalse($guru->canDo('daftar_buku', 'create'));
        $this->assertFalse($guru->canDo('pinjam_buku', 'view'));

        // 1. Bisa membuka halaman Jurnal Mengaji (view=true), tapi tidak bisa create
        $jurnalView = $this->actingAs($guru)->get(route('pondok.jurnal.index'));
        $jurnalView->assertStatus(200);
        $jurnalView->assertDontSee('data-modal-target="modalTambahJurnalMengaji"', false);

        $jurnalStore = $this->actingAs($guru)->post(route('pondok.jurnal.store'), [
            'tanggal' => now()->toDateString(),
        ]);
        $jurnalStore->assertRedirect(route('beranda'));

        // 2. Tidak bisa membuka halaman Target Pengajian karena tidak dicentang (meskipun Jurnal Mengaji dicentang)
        $targetView = $this->actingAs($guru)->get(route('pondok.target.index'));
        $targetView->assertRedirect(route('beranda'));

        // 3. Bisa membuka & menambah Daftar Materi, tetapi tidak bisa edit atau delete
        $materiStore = $this->actingAs($guru)->post(route('pondok.materi.store'), [
            'nama_materi'    => 'Kitab Adabul Alim',
            'kategori'       => PondokMateri::KATEGORI_AL_HADIST,
            'jumlah_halaman' => 50,
            'jumlah_baris'   => 1200,
        ]);
        $materiStore->assertRedirect(route('pondok.materi.index'));

        $materi = PondokMateri::where('nama_materi', 'Kitab Adabul Alim')->firstOrFail();

        $materiUpdate = $this->actingAs($guru)->put(route('pondok.materi.update', $materi->id), [
            'nama_materi'    => 'Kitab Diubah',
            'kategori'       => PondokMateri::KATEGORI_AL_HADIST,
            'jumlah_halaman' => 60,
            'jumlah_baris'   => 1300,
        ]);
        $materiUpdate->assertRedirect(route('beranda'));

        $materiDelete = $this->actingAs($guru)->delete(route('pondok.materi.destroy', $materi->id));
        $materiDelete->assertRedirect(route('beranda'));

        // 4. Bisa melihat Daftar Buku, tetapi tidak bisa tambah buku dan tidak bisa membuka Pinjam Buku
        $bukuView = $this->actingAs($guru)->get(route('perpustakaan.buku.index'));
        $bukuView->assertStatus(200);

        $bukuCreate = $this->actingAs($guru)->get(route('perpustakaan.buku.create'));
        $bukuCreate->assertRedirect(route('beranda'));

        $pinjamView = $this->actingAs($guru)->get(route('perpustakaan.pinjam.index'));
        $pinjamView->assertRedirect(route('beranda'));
    }
}

