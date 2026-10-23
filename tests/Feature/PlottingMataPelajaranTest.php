<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\MataPelajaran;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Database\Seeders\PlottingMapelSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlottingMataPelajaranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->seed(PlottingMapelSeeder::class);
    }

    /**
     * Test submenu 'Plotting Mata Pelajaran' tampil pada menu 'Pengaturan Sekolah'.
     */
    public function test_sidebar_pengaturan_sekolah_has_plotting_mapel_submenu(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($yayasan)->get(route('beranda'));
        $response->assertStatus(200);

        // Pastikan menu Pengaturan Sekolah memiliki submenu Plotting Mata Pelajaran
        $response->assertSee('Pengaturan Sekolah');
        $response->assertSee('Plotting Mata Pelajaran');
        $response->assertSee(route('plotting-mapel.index'));
    }

    /**
     * Test Yayasan dapat melihat mata pelajaran dari SEMUA SEKOLAH (SMP & SMA).
     * Lembaga Pondok dan Yayasan TIDAK masuk dalam Pengaturan Sekolah.
     */
    public function test_yayasan_can_view_subjects_from_all_school_lembagas(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        // 1. Yayasan membuka halaman Plotting Mata Pelajaran
        $response = $this->actingAs($yayasan)->get(route('plotting-mapel.index'));
        $response->assertStatus(200);

        // Yayasan melihat pilihan filter sekolah SMP & SMA
        $response->assertSee('Semua Sekolah');
        $response->assertSee('SMP');
        $response->assertSee('SMA');

        // Pondok dan Yayasan TIDAK masuk dalam pilihan lembaga sekolah
        $response->assertDontSee('Pondok Pesantren Al Muttaqin');

        // Menampilkan mapel dari SMP dan SMA
        $response->assertSee('Matematika');
        $response->assertSee('Fisika');
        $response->assertSee('Kimia');

        // Mapel Pondok tidak ada karena Pondok tidak masuk dalam pengaturan sekolah
        $response->assertDontSee('Tahfidz Al-Qur\'an', false);
        $response->assertDontSee('Nahwu & Shorof');

        // 2. Yayasan melakukan filter khusus SMP
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();
        $smpResponse = $this->actingAs($yayasan)->get(route('plotting-mapel.index', ['lembaga_id' => $smp->id]));
        $smpResponse->assertStatus(200);
        $smpResponse->assertSee('Matematika');
        $smpResponse->assertSee('Ilmu Pengetahuan Alam (IPA)');
        // Mapel SMA tidak tampil saat filter SMP aktif
        $smpResponse->assertDontSee('Fisika');

        // 3. Yayasan melakukan filter khusus SMA
        $sma = Lembaga::where('kode', 'sma')->firstOrFail();
        $smaResponse = $this->actingAs($yayasan)->get(route('plotting-mapel.index', ['lembaga_id' => $sma->id]));
        $smaResponse->assertStatus(200);
        $smaResponse->assertSee('Fisika');
        $smaResponse->assertSee('Kimia');
        $smaResponse->assertSee('Ekonomi & Akuntansi');
        // Mapel SMP tidak tampil pada filter SMA
        $smaResponse->assertDontSee('SMP-IPA');
    }

    /**
     * Test Lembaga masing-masing: Menu SMP HANYA bisa melihat mata pelajaran SMP.
     */
    public function test_smp_user_can_only_see_smp_subjects(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('plotting-mapel.index'));

        $response->assertStatus(200);

        // Melihat mata pelajaran SMP
        $response->assertSee('Matematika');
        $response->assertSee('Ilmu Pengetahuan Alam (IPA)');
        $response->assertSee('Bahasa Indonesia');

        // TIDAK BISA melihat mata pelajaran SMA (Fisika, Kimia, dll)
        $response->assertDontSee('Fisika');
        $response->assertDontSee('Kimia');

        // Tidak memiliki dropdown untuk ganti ke SMA
        $response->assertDontSee('Semua Sekolah (SMP & SMA)');
    }

    /**
     * Test pencegahan manipulasi URL: User SMP mencoba request lembaga_id SMA tetap terkunci pada SMP.
     */
    public function test_smp_user_cannot_access_other_institution_via_query_param(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $sma = Lembaga::where('kode', 'sma')->firstOrFail();

        // Coba injeksi ?lembaga_id=sma
        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('plotting-mapel.index', ['lembaga_id' => $sma->id]));

        $response->assertStatus(200);

        // Tetap hanya melihat mapel SMP
        $response->assertSee('Matematika');
        $response->assertDontSee('Fisika');
        $response->assertDontSee('Kimia');
    }

    /**
     * Test Admin SMP dapat menambahkan mata pelajaran baru yang otomatis masuk ke SMP.
     */
    public function test_smp_user_can_create_subject_for_smp(): void
    {
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();

        $response = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->post(route('plotting-mapel.store'), [
                'mata_pelajaran' => 'Robotika & Coding SMP',
                'kode'           => 'SMP-ROB',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Cek data tersimpan dengan kolom lembaga_id, mata_pelajaran, kode
        $this->assertDatabaseHas('mata_pelajarans', [
            'lembaga_id'     => $smp->id,
            'mata_pelajaran' => 'Robotika & Coding SMP',
            'kode'           => 'SMP-ROB',
        ]);
    }

    /**
     * Test Yayasan dapat menambahkan mata pelajaran untuk lembaga sekolah tertentu.
     */
    public function test_yayasan_can_create_subject_for_any_school_lembaga(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $sma = Lembaga::where('kode', 'sma')->firstOrFail();

        $response = $this->actingAs($yayasan)->post(route('plotting-mapel.store'), [
            'lembaga_id'     => $sma->id,
            'mata_pelajaran' => 'Astronomi & Antariksa',
            'kode'           => 'SMA-AST',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('mata_pelajarans', [
            'lembaga_id'     => $sma->id,
            'mata_pelajaran' => 'Astronomi & Antariksa',
            'kode'           => 'SMA-AST',
        ]);
    }

    /**
     * Test penambahan mata pelajaran ke lembaga non-sekolah (Pondok) ditolak.
     */
    public function test_cannot_create_subject_for_non_school_institution(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $pondok = Lembaga::where('kode', 'pondok')->firstOrFail();

        $response = $this->actingAs($yayasan)->post(route('plotting-mapel.store'), [
            'lembaga_id'     => $pondok->id,
            'mata_pelajaran' => 'Kitab Kuning',
            'kode'           => 'PND-KTB',
        ]);

        $response->assertSessionHasErrors('lembaga_id');
        $this->assertDatabaseMissing('mata_pelajarans', [
            'kode' => 'PND-KTB',
        ]);
    }

    /**
     * Test update mata pelajaran dan kodenya.
     */
    public function test_user_can_update_subject_and_code(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $mapel = MataPelajaran::where('mata_pelajaran', 'Matematika')->firstOrFail();

        $response = $this->actingAs($yayasan)->put(route('plotting-mapel.update', $mapel->id), [
            'lembaga_id'     => $mapel->lembaga_id,
            'mata_pelajaran' => 'Matematika Terapan',
            'kode'           => 'SMP-MAT-T',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $mapel->refresh();
        $this->assertEquals('Matematika Terapan', $mapel->mata_pelajaran);
        $this->assertEquals('SMP-MAT-T', $mapel->kode);
    }

    /**
     * Test toggle status / route kompatibilitas.
     */
    public function test_user_can_toggle_subject_status(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $mapel = MataPelajaran::where('mata_pelajaran', 'Matematika')->firstOrFail();

        $response = $this->actingAs($yayasan)->patch(route('plotting-mapel.toggle-status', $mapel->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /**
     * Test hapus mata pelajaran.
     */
    public function test_user_can_delete_subject(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();
        $mapel = MataPelajaran::create([
            'lembaga_id'     => $smp->id,
            'mata_pelajaran' => 'Mapel Uji Coba Hapus',
            'kode'           => 'TEST-DEL-01',
        ]);

        $response = $this->actingAs($yayasan)->delete(route('plotting-mapel.destroy', $mapel->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('mata_pelajarans', [
            'id' => $mapel->id,
        ]);
    }

    /**
     * Test user tanpa izin tidak dapat mengakses menu plotting mata pelajaran.
     */
    public function test_unauthorized_user_cannot_access_plotting_mapel(): void
    {
        $guru = User::where('email', 'guru.pondok.smp@almuttaqin.id')->firstOrFail();

        $response = $this->actingAs($guru)->get(route('plotting-mapel.index'));
        $response->assertRedirect(route('beranda'));
    }
}
