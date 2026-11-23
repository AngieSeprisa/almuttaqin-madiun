<?php

namespace Tests\Feature;

use App\Http\Controllers\UserController;
use App\Models\Lembaga;
use App\Models\PermissionTemplate;
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

    public function test_guru_sekolah_view_only_menyembunyikan_tombol_aksi_dan_memblokir_mutasi(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $adminSmp = User::where('role', 'admin_lembaga')->firstOrFail();
        $smpLembaga = Lembaga::where('kode', 'smp')->first() ?? Lembaga::firstOrFail();

        // Atur hak akses akun admin_lembaga menjadi VIEW ONLY pada guru_sekolah
        $updateRes = $this->actingAs($yayasan)->put(route('pengguna.update', $adminSmp->id), [
            'name'      => $adminSmp->name,
            'email'     => $adminSmp->email,
            'role'      => 'admin_lembaga',
            'jabatan'   => 'Staff SMP',
            'lembagas'  => [$smpLembaga->id],
            'is_active' => 1,
            'permissions' => [
                'guru_sekolah' => [
                    'view' => 1,
                ],
            ],
        ]);
        $updateRes->assertRedirect(route('pengguna.index'));

        $adminSmp->refresh();

        // canDo() harus strictly false untuk mutasi
        $this->assertTrue($adminSmp->canDo('guru_sekolah', 'view'));
        $this->assertFalse($adminSmp->canDo('guru_sekolah', 'create'));
        $this->assertFalse($adminSmp->canDo('guru_sekolah', 'edit'));
        $this->assertFalse($adminSmp->canDo('guru_sekolah', 'delete'));

        // Buat user guru untuk dites
        $targetGuru = User::create([
            'name'        => 'Target Guru Test',
            'email'       => 'targetguru@almuttaqin.id',
            'password'    => bcrypt('password'),
            'role'        => 'guru',
            'primary_lembaga_id' => $smpLembaga->id,
            'mata_pelajaran' => 'Matematika',
            'mapel_status' => 'disetujui',
            'is_active'   => 1,
        ]);
        $targetGuru->lembagas()->sync([$smpLembaga->id]);

        // 1. GET /guru-sekolah harus 200 dan tidak menampilkan tombol Tambah Guru, Edit, maupun Hapus
        $response = $this->actingAs($adminSmp)->get(route('guru-sekolah.index'));
        $response->assertStatus(200);
        $response->assertDontSee('onclick="bukaModalTambahGuru()"', false);
        $response->assertDontSee('action-btn-edit', false);
        $response->assertDontSee('action-btn-delete', false);
        $response->assertDontSee('id="modalTambahGuru"', false);
        $response->assertDontSee('id="modalUbahGuru"', false);
        $response->assertDontSee('id="modalTolakPengajuan"', false);

        // 2. POST store harus diblokir (redirect ke beranda atau 403 jika JSON)
        $storeRes = $this->actingAs($adminSmp)->post(route('guru-sekolah.store'), [
            'user_id' => $targetGuru->id,
            'lembaga_id' => $smpLembaga->id,
            'mata_pelajaran' => 'Matematika',
        ]);
        $storeRes->assertRedirect(route('beranda'));

        $storeJsonRes = $this->actingAs($adminSmp)->postJson(route('guru-sekolah.store'), [
            'user_id' => $targetGuru->id,
            'lembaga_id' => $smpLembaga->id,
            'mata_pelajaran' => 'Matematika',
        ]);
        $storeJsonRes->assertStatus(403);

        // 3. DELETE destroy harus diblokir (redirect ke beranda atau 403 jika JSON)
        $deleteRes = $this->actingAs($adminSmp)->delete(route('guru-sekolah.destroy', $targetGuru->id));
        $deleteRes->assertRedirect(route('beranda'));

        $deleteJsonRes = $this->actingAs($adminSmp)->deleteJson(route('guru-sekolah.destroy', $targetGuru->id));
        $deleteJsonRes->assertStatus(403);
    }

    public function test_menu_template_hak_akses_crud_dan_tampil_di_form_pengguna(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        // 1. Halaman daftar & buat template terbuka
        $this->actingAs($yayasan)->get(route('template-akses.index'))->assertOk();
        $this->actingAs($yayasan)->get(route('template-akses.create'))->assertOk();

        // 2. Simpan template baru (auto-include view saat create dicentang)
        $this->actingAs($yayasan)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('template-akses.store'), [
                'nama'         => 'Panitia SPMB SMP',
                'default_role' => 'admin_lembaga',
                'lembagas'     => [$smp->id],
                'permissions'  => [
                    'pendaftaran' => ['create' => 1, 'edit' => 1],
                    'siswa'       => ['view' => 1],
                ],
            ])
            ->assertRedirect(route('template-akses.index'));

        $tpl = PermissionTemplate::where('nama', 'Panitia SPMB SMP')->firstOrFail();
        $this->assertSame('admin_lembaga', $tpl->default_role);
        $this->assertSame([$smp->id], $tpl->lembaga_ids);
        $this->assertSame(['view', 'create', 'edit'], $tpl->permissions['pendaftaran']);
        $this->assertSame(['view'], $tpl->permissions['siswa']);

        // 3. Template tampil di daftar, form Tambah Pengguna, dan form Edit Pengguna
        $this->actingAs($yayasan)->get(route('template-akses.index'))
            ->assertOk()
            ->assertSee('Panitia SPMB SMP');

        $this->actingAs($yayasan)->get(route('pengguna.create'))
            ->assertOk()
            ->assertSee('Panitia SPMB SMP');

        $this->actingAs($yayasan)->get(route('pengguna.edit', $guru->id))
            ->assertOk()
            ->assertSee('Panitia SPMB SMP');

        // 4. Edit & update template
        $this->actingAs($yayasan)->get(route('template-akses.edit', $tpl->id))
            ->assertOk()
            ->assertSee('Panitia SPMB SMP');

        $this->actingAs($yayasan)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->put(route('template-akses.update', $tpl->id), [
                'nama'         => 'Panitia SPMB Terpadu',
                'default_role' => 'guru',
                'lembagas'     => [$smp->id],
                'permissions'  => [
                    'pendaftaran' => ['view' => 1],
                ],
            ])
            ->assertRedirect(route('template-akses.index'));

        $this->assertSame('Panitia SPMB Terpadu', $tpl->fresh()->nama);
        $this->assertSame(1, $tpl->fresh()->menuCount());

        // 5. Hapus template
        $this->actingAs($yayasan)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->delete(route('template-akses.destroy', $tpl->id))
            ->assertRedirect(route('template-akses.index'));

        $this->assertDatabaseMissing('permission_templates', ['id' => $tpl->id]);
    }
}

