<?php

namespace Tests\Feature;

use App\Models\Pendaftaran;
use App\Models\PenerimaanSantri;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PenerimaanSpmbTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->admin = User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
    }

    public function test_admin_dapat_membuat_dan_mengelola_link_penerimaan_dengan_slug_dan_gelombang(): void
    {
        $response = $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('penerimaan.store'), [
                'judul'          => 'SPMB Al Muttaqin 2027/2028',
                'slug'           => 'SPMB 2027 2028',
                'tahun_ajaran'   => '2027/2028',
                'jenjang'        => 'Semua',
                'kontak_wa'      => '081234567890',
                'jadwal_seleksi' => 'Sabtu, 15 Mei 2027 · 08.00 WIB',
                'lokasi_seleksi' => 'Pondok Pesantren Al Muttaqin Madiun',
                'is_active'      => '1',
                'syarat_dokumen' => [
                    'berkas_akta'             => 'wajib',
                    'berkas_kk'               => 'opsional',
                    'berkas_ktp_ortu'         => 'opsional',
                    'berkas_surat_pernyataan' => 'tidak',
                ],
                'catatan_syarat' => "Lulusan SD/MI atau SMP/MTs\nSiap mengikuti seleksi offline",
                'gelombangs'     => [
                    [
                        'nama_gelombang'  => 'Gelombang 1',
                        'tanggal_mulai'   => now()->subDay()->toDateString(),
                        'tanggal_selesai' => now()->addDays(10)->toDateString(),
                        'kuota'           => 50,
                    ],
                ],
            ]);

        $response->assertRedirect(route('penerimaan.index'));

        $event = PenerimaanSantri::where('slug', 'spmb-2027-2028')->firstOrFail();
        $this->assertSame('SPMB Al Muttaqin 2027/2028', $event->judul);
        $this->assertCount(1, $event->gelombangs);
        $this->assertTrue($event->isOpen());

        // Halaman daftar & edit tampil dengan status 200
        $this->actingAs($this->admin)->get(route('penerimaan.index'))
            ->assertOk()
            ->assertSee('/spmb/spmb-2027-2028');

        $this->actingAs($this->admin)->get(route('penerimaan.edit', $event->id))
            ->assertOk()
            ->assertSee('spmb-2027-2028');
    }

    public function test_calon_santri_dapat_mendaftar_tanpa_login_lewat_slug_dan_cek_status(): void
    {
        Storage::fake('public');

        $event = PenerimaanSantri::create([
            'judul'          => 'SPMB 2027/2028',
            'slug'           => 'spmb-2027-2028',
            'tahun_ajaran'   => '2027/2028',
            'jenjang'        => 'SMP',
            'kontak_wa'      => '081234567890',
            'jadwal_seleksi' => 'Minggu, 20 Mei 2027',
            'lokasi_seleksi' => 'Kampus Al Muttaqin',
            'syarat_dokumen' => [
                'berkas_akta'             => 'wajib',
                'berkas_kk'               => 'opsional',
                'berkas_ktp_ortu'         => 'tidak',
                'berkas_surat_pernyataan' => 'tidak',
            ],
            'is_active'      => true,
        ]);

        $gel = $event->gelombangs()->create([
            'nama_gelombang'  => 'Gelombang 1',
            'tanggal_mulai'   => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(7)->toDateString(),
            'urutan'          => 1,
        ]);

        // Form publik terbuka tanpa login
        $this->get(route('spmb.show', $event->slug))
            ->assertOk()
            ->assertSee('SPMB 2027/2028')
            ->assertSee('Gelombang 1');

        // Wajib upload foto_akta jika diset wajib
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('spmb.store', $event->slug), [
                'jenjang'       => 'SMP',
                'nama_lengkap'  => 'Budi Santoso',
                'jenis_kelamin' => 'Laki-laki',
                'pj_biaya_hp'   => '085612345678',
            ])
            ->assertSessionHasErrors('foto_akta');

        // Kirim dengan berkas wajib lengkap
        $submit = $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('spmb.store', $event->slug), [
                'jenjang'       => 'SMP',
                'nik'           => '3577010101120001',
                'nama_lengkap'  => 'Budi Santoso',
                'jenis_kelamin' => 'Laki-laki',
                'pj_biaya_hp'   => '0856-1234-5678',
                'foto_akta'     => UploadedFile::fake()->image('akta.jpg', 800, 600),
            ]);

        $pendaftaran = Pendaftaran::where('nama_lengkap', 'Budi Santoso')->firstOrFail();
        $this->assertSame($event->id, $pendaftaran->penerimaan_santri_id);
        $this->assertSame($gel->id, $pendaftaran->penerimaan_gelombang_id);
        $this->assertSame('Pending', $pendaftaran->status_pendaftaran);
        $this->assertSame('085612345678', $pendaftaran->pj_biaya_hp);
        $this->assertNotEmpty($pendaftaran->public_token);
        Storage::disk('public')->assertExists($pendaftaran->foto_akta);

        $submit->assertRedirect(route('spmb.bukti', [
            'slug'  => $event->slug,
            'token' => $pendaftaran->public_token,
        ]));

        // Halaman bukti & cetak dapat dibuka menggunakan token
        $this->get(route('spmb.bukti', [$event->slug, $pendaftaran->public_token]))
            ->assertOk()
            ->assertSee($pendaftaran->no_induk)
            ->assertSee('Budi Santoso');

        $this->get(route('spmb.cetak', [$event->slug, $pendaftaran->public_token]))
            ->assertOk()
            ->assertSee($pendaftaran->no_induk);

        // Halaman cek status menemukan pendaftar dari no registrasi
        $this->get(route('spmb.cek-status', ['slug' => $event->slug, 'kode' => $pendaftaran->no_induk]))
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Pending');
    }

    public function test_event_tutup_tidak_menerima_pendaftaran_dan_alur_lulus_seleksi_ke_daftar_ulang(): void
    {
        $event = PenerimaanSantri::create([
            'judul'        => 'SPMB Tutup',
            'slug'         => 'spmb-tutup',
            'tahun_ajaran' => '2027/2028',
            'jenjang'      => 'SMA',
            'is_active'    => false,
        ]);

        $this->get(route('spmb.show', $event->slug))
            ->assertOk()
            ->assertSee('Ditutup');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('spmb.store', $event->slug), [
                'jenjang'       => 'SMA',
                'nama_lengkap'  => 'Test Tutup',
                'jenis_kelamin' => 'Perempuan',
                'pj_biaya_hp'   => '08123456789',
            ])
            ->assertRedirect(route('spmb.show', $event->slug));

        $this->assertDatabaseMissing('pendaftarans', ['nama_lengkap' => 'Test Tutup']);

        // Uji transisi status: Pending -> Lulus Seleksi (belum jadi Siswa) -> Diterima (jadi Siswa dengan jenjang benar)
        $pendaftar = Pendaftaran::create([
            'penerimaan_santri_id' => $event->id,
            'jenjang'              => 'SMA',
            'nama_lengkap'         => 'Siti Aisyah',
            'status_pendaftaran'   => 'Pending',
        ]);

        $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->patch(route('pendaftaran.updateStatus', $pendaftar->id), [
                'status_pendaftaran' => 'Lulus Seleksi',
            ])
            ->assertRedirect();

        $this->assertSame('Lulus Seleksi', $pendaftar->fresh()->status_pendaftaran);
        $this->assertDatabaseMissing('siswas', ['pendaftaran_id' => $pendaftar->id]);

        $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->patch(route('pendaftaran.updateStatus', $pendaftar->id), [
                'status_pendaftaran' => 'Diterima',
            ])
            ->assertRedirect();

        $siswa = Siswa::where('pendaftaran_id', $pendaftar->id)->firstOrFail();
        $this->assertSame('Siti Aisyah', $siswa->nama_lengkap);
        $this->assertSame('SMA', $siswa->jenjang);
    }
}
