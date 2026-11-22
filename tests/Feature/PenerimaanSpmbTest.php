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

    public function test_jenjang_diambil_dari_lembaga_formal_dengan_wa_lokasi_dan_kuota_per_lembaga(): void
    {
        // Tambah 1 lembaga formal baru di Master Lembaga untuk uji dinamis
        Lembaga::create([
            'kode'      => 'smk',
            'nama'      => 'SMK Al Muttaqin',
            'singkatan' => 'SMK',
            'kategori'  => 'formal',
            'alamat'    => 'Jl. SMK No. 1 Madiun',
            'is_active' => true,
            'urutan'    => 5,
        ]);

        // Form create memuat semua lembaga formal aktif (SMP, SMA, SMK) dan tidak memuat Yayasan/Pondok
        $this->actingAs($this->admin)->get(route('penerimaan.create'))
            ->assertOk()
            ->assertSee('SMP Al Muttaqin')
            ->assertSee('SMA Al Muttaqin')
            ->assertSee('SMK Al Muttaqin');

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('penerimaan.store'), [
                'judul'                      => 'SPMB Al Muttaqin 2027/2028',
                'slug'                       => 'SPMB 2027 2028',
                'tahun_ajaran'               => '2027/2028',
                'jenjangs'                   => ['SMP', 'SMA', 'SMK'],
                'kontak_wa_per_jenjang'      => [
                    'SMP' => '081111111111',
                    'SMA' => '082222222222',
                    'SMK' => '083333333333',
                ],
                'lokasi_seleksi_per_jenjang' => [
                    'SMP' => 'Gedung SMP Al Muttaqin',
                    'SMA' => 'Gedung SMA Al Muttaqin',
                    'SMK' => 'Gedung SMK Al Muttaqin',
                ],
                'jadwal_seleksi'             => 'Sabtu, 15 Mei 2027 · 08.00 WIB',
                'is_active'                  => '1',
                'syarat_dokumen'             => [
                    'berkas_akta'             => 'wajib',
                    'berkas_kk'               => 'opsional',
                    'berkas_ktp_ortu'         => 'opsional',
                    'berkas_surat_pernyataan' => 'tidak',
                ],
                'catatan_syarat'             => "Lulusan SD/MI atau SMP/MTs\nSiap mengikuti seleksi offline",
                'gelombangs'                 => [
                    [
                        'nama_gelombang'    => 'Gelombang 1',
                        'tanggal_mulai'     => now()->subDay()->toDateString(),
                        'tanggal_selesai'   => now()->addDays(10)->toDateString(),
                        'kuota_per_jenjang' => [
                            'SMP' => 64,
                            'SMA' => 32,
                            'SMK' => 40,
                        ],
                    ],
                ],
            ]);

        $response->assertRedirect(route('penerimaan.index'));

        $event = PenerimaanSantri::with('gelombangs')->where('slug', 'spmb-2027-2028')->firstOrFail();
        $this->assertEqualsCanonicalizing(['SMP', 'SMA', 'SMK'], $event->allowedJenjangs());
        $this->assertSame('081111111111', $event->waNumberForJenjang('SMP'));
        $this->assertSame('082222222222', $event->waNumberForJenjang('SMA'));
        $this->assertSame('Gedung SMP Al Muttaqin', $event->lokasiForJenjang('SMP'));
        $this->assertSame('Gedung SMA Al Muttaqin', $event->lokasiForJenjang('SMA'));
        $this->assertSame(64, $event->gelombangs->first()->kuotaForJenjang('SMP'));
        $this->assertSame(32, $event->gelombangs->first()->kuotaForJenjang('SMA'));
        $this->assertSame(40, $event->gelombangs->first()->kuotaForJenjang('SMK'));
        $this->assertTrue($event->isOpen());

        $this->actingAs($this->admin)->get(route('penerimaan.edit', $event->id))
            ->assertOk()
            ->assertSee('Gedung SMP Al Muttaqin')
            ->assertSee('Gedung SMA Al Muttaqin');
    }

    public function test_kuota_per_lembaga_lokasi_per_lembaga_format_reg_dan_cetak_2_halaman(): void
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

        // 2. Buat event dengan kuota SMP=1, SMA=2 dan lokasi terpisah
        $event = PenerimaanSantri::create([
            'judul'                      => 'SPMB 2027/2028',
            'slug'                       => 'spmb-2027-2028',
            'tahun_ajaran'               => '2027/2028',
            'jenjang'                    => 'SMP, SMA',
            'jenjangs'                   => ['SMP', 'SMA'],
            'kontak_wa_per_jenjang'      => [
                'SMP' => '081111111111',
                'SMA' => '082222222222',
            ],
            'lokasi_seleksi_per_jenjang' => [
                'SMP' => 'Aula Gedung SMP',
                'SMA' => 'Aula Gedung SMA',
            ],
            'jadwal_seleksi'             => 'Minggu, 20 Mei 2027',
            'syarat_dokumen'             => [
                'berkas_akta'             => 'wajib',
                'berkas_kk'               => 'opsional',
                'berkas_ktp_ortu'         => 'tidak',
                'berkas_surat_pernyataan' => 'tidak',
            ],
            'is_active'                  => true,
        ]);

        $event->gelombangs()->create([
            'nama_gelombang'    => 'Gelombang 1',
            'tanggal_mulai'     => now()->subDay()->toDateString(),
            'tanggal_selesai'   => now()->addDays(7)->toDateString(),
            'kuota'             => 3,
            'kuota_per_jenjang' => [
                'SMP' => 1,
                'SMA' => 2,
            ],
            'urutan'            => 1,
        ]);

        // Pendaftar SMP #1 -> SMP-01-202728 (mengisi kuota SMP=1)
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

        // Kuota SMP sekarang penuh (1/1), tapi SMA masih buka (0/2)!
        $event->refresh()->load('gelombangs');
        $this->assertTrue($event->isOpen());
        $this->assertSame(['SMA'], $event->availableJenjangs());

        // Form publik menampilkan SMP (Kuota Penuh) dan menolak pendaftaran SMP ke-2
        $this->get(route('spmb.show', $event->slug))
            ->assertOk()
            ->assertSee('SMP (Kuota Penuh)');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('spmb.store', $event->slug), [
                'jenjang'       => 'SMP',
                'nama_lengkap'  => 'Ditolak Kuota SMP',
                'jenis_kelamin' => 'Laki-laki',
                'pj_biaya_hp'   => '085699998888',
                'foto_akta'     => UploadedFile::fake()->image('akta2.jpg', 800, 600),
            ])
            ->assertSessionHasErrors('jenjang');

        // SMA masih bisa mendaftar -> SMA-01-202728
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

        // Bukti SMP -> WA SMP + Lokasi SMP; Bukti SMA -> WA SMA + Lokasi SMA
        $this->get(route('spmb.bukti', [$event->slug, $pSmp1->public_token]))
            ->assertOk()
            ->assertSee('SMP-01-202728')
            ->assertSee('Aula Gedung SMP')
            ->assertDontSee('Aula Gedung SMA')
            ->assertSee('https://wa.me/628111111111', false);

        $this->get(route('spmb.bukti', [$event->slug, $pSma1->public_token]))
            ->assertOk()
            ->assertSee('SMA-01-202728')
            ->assertSee('Aula Gedung SMA')
            ->assertDontSee('Aula Gedung SMP')
            ->assertSee('https://wa.me/628222222222', false);

        // Cetak bukti SMP: 2 halaman + KOP SMP + Lokasi SMP
        $this->get(route('spmb.cetak', [$event->slug, $pSmp1->public_token]))
            ->assertOk()
            ->assertSee('Kartu Bukti Pendaftaran')
            ->assertSee('FORMULIR PENDAFTARAN SANTRI BARU')
            ->assertSee('SMP AL MUTTAQIN MADIUN')
            ->assertSee('Jl. Khusus SMP No. 99 Josenan Madiun')
            ->assertSee('Aula Gedung SMP')
            ->assertSee('SMP-01-202728');

        // Isi kuota SMA ke-2 -> seluruh lembaga penuh -> status Kuota Penuh
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('spmb.store', $event->slug), [
                'jenjang'       => 'SMA',
                'nama_lengkap'  => 'Rina Kusuma',
                'jenis_kelamin' => 'Perempuan',
                'pj_biaya_hp'   => '085700002222',
                'foto_akta'     => UploadedFile::fake()->image('akta4.jpg', 800, 600),
            ])
            ->assertRedirect();

        $event->refresh()->load('gelombangs');
        $this->assertFalse($event->isOpen());
        $this->assertSame('penuh', $event->statusState()['code']);
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

    public function test_endpoint_info_publik_mengembalikan_kuota_dan_ketersediaan_dengan_cors(): void
    {
        $event = PenerimaanSantri::create([
            'judul'                      => 'SPMB 2027/2028',
            'slug'                       => 'spmb-api-2027',
            'tahun_ajaran'               => '2027/2028',
            'jenjang'                    => 'SMP, SMA',
            'jenjangs'                   => ['SMP', 'SMA'],
            'kontak_wa_per_jenjang'      => [
                'SMP' => '081111111111',
                'SMA' => '082222222222',
            ],
            'lokasi_seleksi_per_jenjang' => [
                'SMP' => 'Gedung SMP',
                'SMA' => 'Gedung SMA',
            ],
            'jadwal_seleksi'             => 'Sabtu, 15 Mei 2027',
            'is_active'                  => true,
        ]);

        $gel = $event->gelombangs()->create([
            'nama_gelombang'    => 'Gelombang 1',
            'tanggal_mulai'     => now()->subDay()->toDateString(),
            'tanggal_selesai'   => now()->addDays(10)->toDateString(),
            'kuota'             => 30,
            'kuota_per_jenjang' => ['SMP' => 10, 'SMA' => 20],
            'urutan'            => 1,
        ]);

        Pendaftaran::create([
            'penerimaan_santri_id'    => $event->id,
            'penerimaan_gelombang_id' => $gel->id,
            'jenjang'                 => 'SMP',
            'nama_lengkap'            => 'Pendaftar Satu',
            'status_pendaftaran'      => 'Pending',
        ]);

        $res = $this->get(route('spmb.info', $event->slug));
        $res->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', '*')
            ->assertJsonPath('slug', 'spmb-api-2027')
            ->assertJsonPath('is_open', true)
            ->assertJsonPath('gelombang_aktif.nama', 'Gelombang 1')
            ->assertJsonPath('ringkasan.total_pendaftar', 1)
            ->assertJsonPath('ringkasan.total_kuota', 30)
            ->assertJsonPath('ringkasan.total_sisa', 29)
            ->assertJsonPath('lembaga.0.kode', 'SMP')
            ->assertJsonPath('lembaga.0.kuota', 10)
            ->assertJsonPath('lembaga.0.terisi', 1)
            ->assertJsonPath('lembaga.0.sisa', 9)
            ->assertJsonPath('lembaga.0.penuh', false)
            ->assertJsonPath('lembaga.0.lokasi_seleksi', 'Gedung SMP')
            ->assertJsonPath('lembaga.1.kode', 'SMA')
            ->assertJsonPath('lembaga.1.kuota', 20)
            ->assertJsonPath('lembaga.1.terisi', 0)
            ->assertJsonPath('lembaga.1.sisa', 20);
    }
}
