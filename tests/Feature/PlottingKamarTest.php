<?php

namespace Tests\Feature;

use App\Models\Kamar;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlottingKamarTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
    }

    public function test_sidebar_pengaturan_pondok_visible_for_yayasan_and_admin_pondok_only(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
        $adminPondok = User::where('email', 'admin.pondok@almuttaqin.id')->firstOrFail();
        $adminSMP = User::where('email', 'admin.smp@almuttaqin.id')->firstOrFail();

        // Yayasan melihat Pengaturan Pondok -> Plotting Kamar
        $resYayasan = $this->actingAs($yayasan)->get(route('beranda'));
        $resYayasan->assertStatus(200);
        $resYayasan->assertSee('navGroupPengaturanPondok');
        $resYayasan->assertSee('Pengaturan Pondok');
        $resYayasan->assertSee('Plotting Kamar');
        $resYayasan->assertSee(route('plotting-kamar.index'));

        // Admin Pondok melihat Pengaturan Pondok -> Plotting Kamar
        $resPondok = $this->actingAs($adminPondok)
            ->withSession(['active_lembaga' => 'pondok'])
            ->get(route('beranda'));
        $resPondok->assertStatus(200);
        $resPondok->assertSee('navGroupPengaturanPondok');
        $resPondok->assertSee('Plotting Kamar');

        // Admin SMP tidak melihat Pengaturan Pondok secara default
        $resSMP = $this->actingAs($adminSMP)
            ->withSession(['active_lembaga' => 'smp'])
            ->get(route('beranda'));
        $resSMP->assertStatus(200);
        $resSMP->assertDontSee('navGroupPengaturanPondok');
    }

    public function test_plotting_kamar_auto_syncs_existing_rooms_and_includes_all_jenjang_santri(): void
    {
        $adminPondok = User::where('email', 'admin.pondok@almuttaqin.id')->firstOrFail();

        Siswa::create([
            'nama_lengkap' => 'Santri Reguler Satu',
            'jenjang'      => 'Reguler',
            'kelas'        => null,
            'kamar'        => 'Abu Bakar',
            'status'       => 'aktif',
        ]);

        Siswa::create([
            'nama_lengkap' => 'Santri SMP Mukim',
            'jenjang'      => 'SMP',
            'kelas'        => 'VII A',
            'kamar'        => 'Abu Bakar',
            'status'       => 'aktif',
        ]);

        Siswa::create([
            'nama_lengkap' => 'Santri SMA Belum Berkamar',
            'jenjang'      => 'SMA',
            'kelas'        => 'X A',
            'kamar'        => null,
            'status'       => 'aktif',
        ]);

        $response = $this->actingAs($adminPondok)
            ->withSession(['active_lembaga' => 'pondok'])
            ->get(route('plotting-kamar.index'));

        $response->assertStatus(200);
        $response->assertSee('Kamar Abu Bakar');
        $response->assertSee('2 Santri');
        $response->assertSee('Santri SMA Belum Berkamar');
        $response->assertSee('Belum Berkamar');

        $this->assertDatabaseHas('kamars', [
            'nama_kamar' => 'Abu Bakar',
        ]);
    }

    public function test_crud_kamar_renames_sync_siswa_and_sesi_and_protects_occupied_deletion(): void
    {
        $yayasan = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();

        // 1. Tambah kamar baru
        $this->actingAs($yayasan)->post(route('plotting-kamar.store-kamar'), [
            'nama_kamar'      => 'Umar 1',
            'kategori_asrama' => 'Putra',
            'kapasitas'       => 20,
            'wali_kamar'      => 'Ustadz Fauzi',
        ])->assertRedirect(route('plotting-kamar.index', ['tab' => 'kamar']));

        $kamar = Kamar::where('nama_kamar', 'Umar 1')->firstOrFail();
        $this->assertEquals(20, $kamar->kapasitas);
        $this->assertEquals('Ustadz Fauzi', $kamar->wali_kamar);

        // Kamar baru langsung muncul di daftar kamar aktif untuk dropdown sistem
        $this->assertTrue(Kamar::daftarNamaKamarAktif()->contains('Umar 1'));

        // Masukkan 1 santri & 1 sesi presensi RFID ke kamar Umar 1
        $santri = Siswa::create([
            'nama_lengkap' => 'Ahmad Zaki',
            'jenjang'      => 'Reguler',
            'kamar'        => 'Umar 1',
            'status'       => 'aktif',
        ]);

        DB::table('master_sesis')->insert([
            'kode_sesi'       => 'MALAM_UMAR1',
            'nama_sesi'       => 'Absen Malam Umar 1',
            'jam_mulai'       => '21:00:00',
            'jam_selesai'     => '22:00:00',
            'toleransi_menit' => 15,
            'kategori'        => 'sholat',
            'target_kategori' => 'kamar_khusus',
            'target_filter'   => 'Umar 1',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // 2. Rename kamar Umar 1 -> Umar Bin Khattab menyinkronkan siswa & sesi RFID
        $this->actingAs($yayasan)->put(route('plotting-kamar.update-kamar', $kamar->id), [
            'nama_kamar'      => 'Umar Bin Khattab',
            'kategori_asrama' => 'Putra',
            'kapasitas'       => 24,
            'wali_kamar'      => 'Ustadz Fauzi',
            'urutan'          => 2,
        ])->assertRedirect();

        $this->assertEquals('Umar Bin Khattab', $santri->fresh()->kamar);
        $this->assertDatabaseHas('master_sesis', [
            'kode_sesi'     => 'MALAM_UMAR1',
            'target_filter' => 'Umar Bin Khattab',
        ]);

        // 3. Hapus kamar ditolak selama masih ada santri aktif
        $this->actingAs($yayasan)
            ->delete(route('plotting-kamar.destroy-kamar', $kamar->id))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('kamars', ['id' => $kamar->id]);

        // 4. Setelah santri di-unassign, kamar dapat dihapus
        $this->actingAs($yayasan)->post(route('plotting-kamar.unassign'), [
            'siswa_id' => $santri->id,
        ])->assertSessionHas('success');

        $this->assertNull($santri->fresh()->kamar);

        $this->actingAs($yayasan)
            ->delete(route('plotting-kamar.destroy-kamar', $kamar->id))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('kamars', ['id' => $kamar->id]);
    }

    public function test_mutasi_single_and_bulk_and_anggota_json_endpoint(): void
    {
        $adminPondok = User::where('email', 'admin.pondok@almuttaqin.id')->firstOrFail();

        $this->actingAs($adminPondok)->post(route('plotting-kamar.muat-standar'))
            ->assertSessionHas('success');

        $s1 = Siswa::create([
            'nama_lengkap' => 'Santri Satu',
            'jenjang'      => 'Reguler',
            'kamar'        => null,
            'status'       => 'aktif',
        ]);
        $s2 = Siswa::create([
            'nama_lengkap' => 'Santri Dua',
            'jenjang'      => 'SMP',
            'kelas'        => 'VIII A',
            'kamar'        => null,
            'status'       => 'aktif',
        ]);

        // Bulk mutasi 2 santri sekaligus menggunakan format comma-separated dari UI checkbox
        $this->actingAs($adminPondok)->post(route('plotting-kamar.mutasi'), [
            'siswa_id' => "{$s1->id},{$s2->id}",
            'kamar'    => 'Ali',
        ])->assertSessionHas('success');

        $this->assertEquals('Ali', $s1->fresh()->kamar);
        $this->assertEquals('Ali', $s2->fresh()->kamar);

        // Endpoint JSON anggota kamar
        $jsonRes = $this->actingAs($adminPondok)
            ->getJson(route('plotting-kamar.anggota', ['kamar' => 'Ali']));

        $jsonRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'kamar'   => 'Ali',
                'total'   => 2,
            ]);
    }
}

