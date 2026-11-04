<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuruSekolahApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_sidebar_menus_renamed_and_guru_sekolah_menu_present(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($yayasan)->get(route('beranda'));
        $response->assertStatus(200);

        // 1. Menu 'pengaturan' namanya diubah menjadi 'pengaturan sekolah'
        $response->assertSee('Pengaturan Sekolah');

        // 2. Menu 'absensi' namanya diubah menjadi 'absensi guru'
        $response->assertSee('Absensi Guru');

        // 3. Menu 'guru sekolah' dibuat
        $response->assertSee('Guru Sekolah');
        $response->assertSee(route('guru-sekolah.index'));
    }

    public function test_guru_can_register_subject_on_profile_page(): void
    {
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        // 1. Buka halaman profile guru, cek ada bagian registrasi mata pelajaran
        $response = $this->actingAs($guru)->get(route('profile'));
        $response->assertStatus(200);
        $response->assertSee('Registrasi Mata Pelajaran Pengampu');
        $response->assertSee(route('profile.registrasi-mapel'));

        // 2. Registrasi mata pelajaran Informatika untuk diajukan ke yayasan
        $postResponse = $this->actingAs($guru)->post(route('profile.registrasi-mapel'), [
            'mata_pelajaran' => 'Informatika',
            'catatan'        => 'Mengajar mapel Informatika kelas 7 dan 8',
        ]);

        $postResponse->assertRedirect(route('profile'));
        $postResponse->assertSessionHas('success');

        // Cek data di database
        $guru->refresh();
        $this->assertEquals('Informatika', $guru->mapel_diajukan);
        $this->assertEquals('menunggu', $guru->mapel_status);
        $this->assertEquals('Mengajar mapel Informatika kelas 7 dan 8', $guru->mapel_catatan);
        $this->assertNotNull($guru->mapel_diajukan_at);

        // Halaman profil menampilkan status menunggu
        $profileResponse = $this->actingAs($guru)->get(route('profile'));
        $profileResponse->assertSee('Menunggu Persetujuan Yayasan');
        $profileResponse->assertSee('Informatika');
    }

    public function test_yayasan_can_view_guru_sekolah_and_approve_subject(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        // Guru mengajukan Informatika
        $this->actingAs($guru)->post(route('profile.registrasi-mapel'), [
            'mata_pelajaran' => 'Informatika',
            'catatan'        => 'Mengajukan diri sebagai guru Informatika',
        ]);

        // Yayasan membuka menu Guru Sekolah
        $viewResponse = $this->actingAs($yayasan)->get(route('guru-sekolah.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Guru Sekolah');
        $viewResponse->assertSee('Menunggu Persetujuan');
        $viewResponse->assertSee($guru->name);
        $viewResponse->assertSee('Informatika');

        // Yayasan meng-approve pengajuan guru tersebut
        $approveResponse = $this->actingAs($yayasan)->post(route('guru-sekolah.approve', $guru->id), [
            'mata_pelajaran' => 'Informatika',
            'catatan'        => 'Disetujui oleh Yayasan sebagai guru Informatika',
        ]);

        $approveResponse->assertSessionHas('success');

        // Verifikasi database: guru sekarang resmi menjadi guru Informatika
        $guru->refresh();
        $this->assertEquals('Informatika', $guru->mata_pelajaran);
        $this->assertEquals('disetujui', $guru->mapel_status);
        $this->assertEquals($yayasan->id, $guru->mapel_diverifikasi_by);
        $this->assertNotNull($guru->mapel_diverifikasi_at);

        // Guru membuka kembali profile, melihat status telah resmi disetujui
        $guruProfile = $this->actingAs($guru)->get(route('profile'));
        $guruProfile->assertSee('Resmi Disetujui Yayasan');
        $guruProfile->assertSee('Informatika');
    }

    public function test_yayasan_can_reject_subject_with_notes(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        // Guru mengajukan Fisika
        $this->actingAs($guru)->post(route('profile.registrasi-mapel'), [
            'mata_pelajaran' => 'Fisika Nuklir',
        ]);

        // Yayasan menolak dengan alasan
        $rejectResponse = $this->actingAs($yayasan)->post(route('guru-sekolah.reject', $guru->id), [
            'catatan' => 'Mata pelajaran tidak sesuai kurikulum SMP Al-Muttaqin',
        ]);

        $rejectResponse->assertSessionHas('success');

        $guru->refresh();
        $this->assertEquals('ditolak', $guru->mapel_status);
        $this->assertEquals('Mata pelajaran tidak sesuai kurikulum SMP Al-Muttaqin', $guru->mapel_catatan);

        // Profil guru menampilkan status ditolak dan alasan
        $guruProfile = $this->actingAs($guru)->get(route('profile'));
        $guruProfile->assertSee('Perlu Revisi / Ditolak');
        $guruProfile->assertSee('Mata pelajaran tidak sesuai kurikulum SMP Al-Muttaqin');
    }

    public function test_yayasan_can_assign_subject_directly(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($yayasan)->post(route('guru-sekolah.assign', $guru->id), [
            'mata_pelajaran' => 'Robotika & Coding',
            'catatan'        => 'Ditugaskan oleh Yayasan',
        ]);

        $response->assertSessionHas('success');

        $guru->refresh();
        $this->assertEquals('Robotika & Coding', $guru->mata_pelajaran);
        $this->assertEquals('disetujui', $guru->mapel_status);
    }

    public function test_yayasan_can_assign_multiple_subjects_to_a_teacher(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        // Yayasan menetapkan 2 mata pelajaran sekaligus (bisa array atau comma-separated)
        $response = $this->actingAs($yayasan)->post(route('guru-sekolah.assign', $guru->id), [
            'mata_pelajaran' => ['Informatika', 'Matematika'],
            'catatan'        => 'Mengampu 2 mapel',
        ]);

        $response->assertSessionHas('success');

        $guru->refresh();
        $this->assertEquals('Informatika, Matematika', $guru->mata_pelajaran);
        $this->assertEquals('disetujui', $guru->mapel_status);
        $this->assertEquals(['Informatika', 'Matematika'], $guru->daftar_mata_pelajaran);

        // Halaman guru sekolah menampilkan kedua mapel
        $viewResponse = $this->actingAs($yayasan)->get(route('guru-sekolah.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Informatika');
        $viewResponse->assertSee('Matematika');
    }

    public function test_unauthorized_user_cannot_access_guru_sekolah(): void
    {
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($guru)->get(route('guru-sekolah.index'));
        $response->assertRedirect(route('beranda'));
        $response->assertSessionHas('error');
    }

    public function test_yayasan_can_add_guru_sekolah_from_registered_user(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $smp = \App\Models\Lembaga::where('kode', 'smp')->firstOrFail();

        // Buat akun baru di sistem yang belum menjadi guru sekolah
        $newUser = User::factory()->create([
            'name'      => 'Ahmad Dahlan, S.Pd.',
            'email'     => 'ahmad.dahlan@almuttaqin.id',
            'role'      => 'pengguna',
            'is_active' => true,
        ]);

        $response = $this->actingAs($yayasan)->post(route('guru-sekolah.store'), [
            'user_id'        => $newUser->id,
            'lembaga_id'     => $smp->id,
            'mata_pelajaran' => ['Matematika', 'Informatika'],
        ]);

        $response->assertRedirect(route('guru-sekolah.index'));
        $response->assertSessionHas('success');

        $newUser->refresh();
        $this->assertTrue($newUser->is_guru_sekolah);
        $this->assertEquals($smp->id, $newUser->primary_lembaga_id);
        $this->assertEquals('Matematika, Informatika', $newUser->mata_pelajaran);
        $this->assertEquals('disetujui', $newUser->mapel_status);

        // Muncul di halaman Guru Sekolah
        $indexResponse = $this->actingAs($yayasan)->get(route('guru-sekolah.index'));
        $indexResponse->assertSee('Ahmad Dahlan, S.Pd.');
        $indexResponse->assertSee('Matematika');
    }

    public function test_unregistered_account_is_not_listed_in_guru_sekolah(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        // Akun guru pondok biasa yang TIDAK didaftarkan sebagai guru sekolah
        $pondokUser = User::factory()->create([
            'name'            => 'Ustadzah Khadijah',
            'email'           => 'khadijah@almuttaqin.id',
            'role'            => 'guru',
            'is_guru_sekolah' => false,
            'mapel_status'    => 'belum_mengajukan',
            'is_active'       => true,
        ]);

        $response = $this->actingAs($yayasan)->get(route('guru-sekolah.index'));
        $response->assertStatus(200);

        // Pastikan tidak ada di daftar tabel Guru Sekolah
        $tableUsers = $response->viewData('users');
        $this->assertFalse($tableUsers->contains('id', $pondokUser->id));
    }

    public function test_yayasan_can_remove_guru_from_guru_sekolah_list(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $smp = \App\Models\Lembaga::where('kode', 'smp')->firstOrFail();

        $guru = User::factory()->create([
            'name'               => 'Budi Santoso, M.Pd.',
            'email'              => 'budi.santoso@almuttaqin.id',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $smp->id,
            'mata_pelajaran'     => 'Fisika',
            'mapel_status'       => 'disetujui',
            'is_active'          => true,
        ]);

        $deleteResponse = $this->actingAs($yayasan)->delete(route('guru-sekolah.destroy', $guru->id));
        $deleteResponse->assertRedirect(route('guru-sekolah.index'));
        $deleteResponse->assertSessionHas('success');

        $guru->refresh();
        $this->assertFalse($guru->is_guru_sekolah);
        $this->assertNull($guru->mata_pelajaran);

        // Akun tetap ada di database, tetapi tidak terdaftar di tabel guru sekolah
        $this->assertDatabaseHas('users', ['id' => $guru->id]);

        $indexResponse = $this->actingAs($yayasan)->get(route('guru-sekolah.index'));
        $tableUsers = $indexResponse->viewData('users');
        $this->assertFalse($tableUsers->contains('id', $guru->id));
    }

    public function test_table_custom_columns_and_filter_popover_rendered_correctly(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($yayasan)->get(route('guru-sekolah.index'));
        $response->assertStatus(200);

        // 1. Tombol filter popover dan controls di dalam popover
        $response->assertSee('title="Filter Data Guru"', false);
        $response->assertSee('Filter Guru Sekolah');
        $response->assertSee('Status Pengajuan');
        $response->assertSee('Terapkan Filter');

        // 2. Tombol tabel custom dan controls visibilitas kolom
        $response->assertSee('title="Tabel Kustom (Atur Kolom)"', false);
        $response->assertSee('Kolom Tabel');
        $response->assertSee('x-model="columns.sekolah"', false);
        $response->assertSee('x-model="columns.mapel"', false);
        $response->assertSee('x-model="columns.status"', false);
        $response->assertSee('x-model="columns.aksi"', false);
    }
}
