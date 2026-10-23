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

    public function test_unauthorized_user_cannot_access_guru_sekolah(): void
    {
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($guru)->get(route('guru-sekolah.index'));
        $response->assertRedirect(route('beranda'));
        $response->assertSessionHas('error');
    }
}
