<?php

namespace Tests\Feature;

use App\Models\Lembaga;
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

    public function test_admin_dapat_membuat_penerimaan_dengan_checklist_multi_jenjang_dan_wa_per_jenjang(): void
    {
        $response = $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('penerimaan.store'), [
                'judul'                 => 'SPMB Al Muttaqin 2027/2028',
                'slug'                  => 'SPMB 2027 2028',
                'tahun_ajaran'          => '2027/2028',
                'jenjangs'              => ['SMP', 'SMA'],
                'kontak_wa_per_jenjang' => [
                    'SMP' => '081111111111',
                    'SMA' => '082222222222',
                ],
                'jadwal_seleksi'        => 'Sabtu, 15 Mei 2027 · 08.00 WIB',
                'lokasi_seleksi'        => 'Pondok Pesantren Al Muttaqin Madiun',
                'is_active'             => '1',
                'syarat_dokumen'        => [
                    'berkas_akta'             => 'wajib',
                    'berkas_kk'               => 'opsional',
                    'berkas_ktp_ortu'         => 'opsional',
                    'berkas_surat_pernyataan' => 'tidak',
                ],
                'catatan_syarat'        => "Lulusan SD/MI atau SMP/MTs\nSiap mengikuti seleksi offline",
                'gelombangs'            => [
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
        $this->assertSame(['SMP', 'SMA'], $event->allowedJenjangs());
        $this->assertSame('081111111111', $event->waNumberForJenjang('SMP'));
        $this->assertSame('082222222222', $event->waNumberForJenjang('SMA'));
        $this->assertCount(1, $event->gelombangs);
        $this->assertTrue($event->isOpen());

        $this->actingAs($this->admin)->get(route('penerimaan.index'))
            ->assertOk()
            ->assertSee('/spmb/spmb-2027-2028');

        $this->actingAs($this->admin)->get(route('penerimaan.edit', $event->id))
            ->assertOk()
            ->assertSee('081111111111')
            ->assertSee('082222222222');
    }

    public function test_nomor_registrasi_format_lembaga_urut_tahun_dan_bukti_cetak_2_halaman_sesuai_kop_lembaga(): void
    {
        Storage::fake('public');

        // 1. Update kelengkapan Lembaga SMP (alamat, telepon, email, logo)
        $smp = Lembaga::where('kode', 'smp')->firstOrFail();
        $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->put(route('master.lembaga.update', $smp->id), [
                'nama'      => 'SMP AL MUTTAQIN MADIUN',
                'singkatan' => 'SMP',
                'kategori'  => 'formal',
                'alamat'    => 'Jl. Khusus SMP No. 99 Josenan Madiun',
                'telepon'   => '0351-999888',
                'email'     => 'smp@almuttaqin-madiun.my.id',
                'logo'      => UploadedFile::fake()->image('logo-smp-baru.png', 300, 300),
                'is_active' => '1',
            ])
            ->assertRedirect(route('master.lembaga.index'));

        $smp->refresh();
        $this->assertSame('Jl. Khusus SMP No. 99 Josenan Madiun', $smp->alamat);
        $this->assertNotNull($smp->logo);
        Storage::disk('public')->assertExists($smp->logo);

        // 2. Buat event multi-jenjang SMP & SMA dengan WA masing-masing
        $event = PenerimaanSantri::create([
            'judul'                 => 'SPMB 2027/2028',
            'slug'                  => 'spmb-2027-2028',
            'tahun_ajaran'          => '2027/2028',
            'jenjang'               => 'SMP, SMA',
            'jenjangs'              => ['SMP', 'SMA'],
            'kontak_wa_per_jenjang' => [
                'SMP' => '081111111111',
                'SMA' => '082222222222',
            ],
            'jadwal_seleksi'        => 'Minggu, 20 Mei 2027',
            'lokasi_seleksi'        => 'Kampus Al Muttaqin',
            'syarat_dokumen'        => [
                'berkas_akta'             => 'wajib',
                'berkas_kk'               => 'opsional',
                'berkas_ktp_ortu'         => 'tidak',
                'berkas_surat_pernyataan' => 'tidak',
            ],
            'is_active'             => true,
        ]);

        $event->gelombangs()->create([
            'nama_gelombang'  => 'Gelombang 1',
            'tanggal_mulai'   => now()->subDay()->toDateString(),
            'tanggal_selesai' => now()->addDays(7)->toDateString(),
            'urutan'          => 1,
        ]);

        // Form publik hanya menampilkan pilihan SMP & SMA (tidak Reguler)
        $this->get(route('spmb.show', $event->slug))
            ->assertOk()
            ->assertSee('SPMB 2027/2028')
            ->assertSee('Gelombang 1');

        // Pendaftar SMP #1 -> SMP-01-202728
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('spmb.store', $event->slug), [
                'jenjang'       => 'SMP',
                'nik'           => '3577010101120001',
                'nama_lengkap'  => 'Budi Santoso',
                'jenis_kelamin' => 'Laki-laki',
                'pj_biaya_hp'   => '0856-1234-5678',
                'foto_akta'     => UploadedFile::fake()->image('akta1.jpg', 800, 600),
            ])
            ->assertRedirect();

        $pSmp1 = Pendaftaran::where('nama_lengkap', 'Budi Santoso')->firstOrFail();
        $this->assertSame('SMP-01-202728', $pSmp1->no_induk);

        // Pendaftar SMP #2 -> SMP-02-202728
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('spmb.store', $event->slug), [
                'jenjang'       => 'SMP',
                'nama_lengkap'  => 'Ahmad Fauzi',
                'jenis_kelamin' => 'Laki-laki',
                'pj_biaya_hp'   => '085699998888',
                'foto_akta'     => UploadedFile::fake()->image('akta2.jpg', 800, 600),
            ])
            ->assertRedirect();

        $pSmp2 = Pendaftaran::where('nama_lengkap', 'Ahmad Fauzi')->firstOrFail();
        $this->assertSame('SMP-02-202728', $pSmp2->no_induk);

        // Pendaftar SMA #1 -> SMA-01-202728
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('spmb.store', $event->slug), [
                'jenjang'       => 'SMA',
                'nama_lengkap'  => 'Dewi Lestari',
                'jenis_kelamin' => 'Perempuan',
                'pj_biaya_hp'   => '085700001111',
                'foto_akta'     => UploadedFile::fake()->image('akta3.jpg', 800, 600),
            ])
            ->assertRedirect();

        $pSma1 = Pendaftaran::where('nama_lengkap', 'Dewi Lestari')->firstOrFail();
        $this->assertSame('SMA-01-202728', $pSma1->no_induk);

        // Bukti SMP mengarah ke WA Panitia SMP (628111111111), Bukti SMA ke WA Panitia SMA (628222222222)
        $this->get(route('spmb.bukti', [$event->slug, $pSmp1->public_token]))
            ->assertOk()
            ->assertSee('SMP-01-202728')
            ->assertSee('https://wa.me/628111111111', false);

        $this->get(route('spmb.bukti', [$event->slug, $pSma1->public_token]))
            ->assertOk()
            ->assertSee('SMA-01-202728')
            ->assertSee('https://wa.me/628222222222', false);

        // Halaman cetak bukti SMP terdiri dari 2 halaman (Kartu Bukti + Formulir) dan memakai KOP Lembaga SMP
        $this->get(route('spmb.cetak', [$event->slug, $pSmp1->public_token]))
            ->assertOk()
            ->assertSee('Kartu Bukti Pendaftaran')
            ->assertSee('FORMULIR PENDAFTARAN SANTRI BARU')
            ->assertSee('SMP AL MUTTAQIN MADIUN')
            ->assertSee('Jl. Khusus SMP No. 99 Josenan Madiun')
            ->assertSee('0351-999888')
            ->assertSee('smp@almuttaqin-madiun.my.id')
            ->assertSee('SMP-01-202728');

        // Halaman cek status menemukan pendaftar dari no registrasi
        $this->get(route('spmb.cek-status', ['slug' => $event->slug, 'kode' => 'SMP-01-202728']))
            ->assertOk()
            ->assertSee('Budi Santoso');
    }

    public function test_event_tutup_tidak_menerima_pendaftaran_dan_alur_lulus_seleksi_ke_daftar_ulang(): void
    {
        $event = PenerimaanSantri::create([
            'judul'        => 'SPMB Tutup',
            'slug'         => 'spmb-tutup',
            'tahun_ajaran' => '2027/2028',
            'jenjang'      => 'SMA',
            'jenjangs'     => ['SMA'],
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
