<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\Pendaftaran;
use App\Models\PenerimaanSantri;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SpmbFormBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected Lembaga $yayasan;
    protected Lembaga $smp;
    protected Lembaga $sdit;
    protected User $yayasanUser;
    protected User $guruSdit;
    protected PenerimaanSantri $eventSdit;
    protected PenerimaanSantri $eventSmp;

    protected function setUp(): void
    {
        parent::setUp();

        $this->yayasan = Lembaga::create([
            'kode' => 'yayasan', 'nama' => 'Yayasan Pondok Pesantren Muttaqin', 'singkatan' => 'Yayasan',
            'kategori' => 'yayasan', 'is_active' => true, 'urutan' => 1,
        ]);
        $this->smp = Lembaga::create([
            'kode' => 'smp', 'nama' => 'SMP Al Muttaqin', 'singkatan' => 'SMP',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 2,
        ]);
        $this->sdit = Lembaga::create([
            'kode' => 'sdit', 'nama' => 'SD IT AL MUTTAQIN', 'singkatan' => 'SD IT',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 3,
        ]);

        $pwd = Hash::make('password');
        $this->yayasanUser = User::create([
            'name' => 'Ketua Yayasan', 'email' => 'yayasan.fb@almuttaqin.id', 'password' => $pwd,
            'role' => 'yayasan', 'primary_lembaga_id' => $this->yayasan->id, 'is_active' => true,
        ]);
        $this->yayasanUser->lembagas()->sync([$this->yayasan->id, $this->smp->id, $this->sdit->id]);

        $this->guruSdit = User::create([
            'name' => 'Admin SDIT', 'email' => 'admin.sdit.fb@almuttaqin.id', 'password' => $pwd,
            'role' => 'admin_lembaga', 'primary_lembaga_id' => $this->sdit->id, 'is_active' => true,
        ]);
        $this->guruSdit->lembagas()->sync([$this->sdit->id]);
        UserPermission::updateOrCreate(
            ['user_id' => $this->guruSdit->id, 'menu_code' => 'pendaftaran'],
            ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true]
        );

        $this->eventSdit = PenerimaanSantri::create([
            'judul'        => 'SPMB SD IT 2027/2028',
            'slug'         => 'spmb-sdit-2027',
            'tahun_ajaran' => '2027/2028',
            'jenjang'      => 'SD IT',
            'jenjangs'     => ['SD IT'],
            'is_active'    => true,
            'created_by'   => $this->yayasanUser->id,
        ]);

        $this->eventSmp = PenerimaanSantri::create([
            'judul'        => 'SPMB SMP 2027/2028',
            'slug'         => 'spmb-smp-2027',
            'tahun_ajaran' => '2027/2028',
            'jenjang'      => 'SMP',
            'jenjangs'     => ['SMP'],
            'is_active'    => true,
            'created_by'   => $this->yayasanUser->id,
        ]);
    }

    private function sampleCustomSchema(): array
    {
        return [
            'layout'      => 'single',
            'description' => 'Silakan lengkapi formulir singkat SD IT Al Muttaqin.',
            'sections'    => [
                [
                    'id'          => 'sec_anak',
                    'title'       => 'Data Calon Siswa',
                    'short_title' => 'Siswa',
                    'description' => 'Isi sesuai akta kelahiran',
                    'enabled'     => true,
                    'fields'      => [
                        ['id' => 'jenjang', 'source' => 'builtin', 'key' => 'jenjang', 'label' => 'Jenjang Pilihan', 'type' => 'select', 'required' => true, 'enabled' => true, 'locked' => true],
                        ['id' => 'nama_lengkap', 'source' => 'builtin', 'key' => 'nama_lengkap', 'label' => 'Nama Lengkap Ananda', 'type' => 'text', 'required' => true, 'enabled' => true, 'locked' => true],
                        ['id' => 'tanggal_lahir', 'source' => 'builtin', 'key' => 'tanggal_lahir', 'label' => 'Tanggal Lahir', 'type' => 'date', 'required' => true, 'enabled' => true, 'locked' => false],
                        ['id' => 'nisn', 'source' => 'builtin', 'key' => 'nisn', 'label' => 'NISN', 'type' => 'text', 'required' => false, 'enabled' => false, 'locked' => false],
                        ['id' => 'foto_akta', 'source' => 'builtin', 'key' => 'foto_akta', 'label' => 'Foto Akta', 'type' => 'file', 'required' => false, 'enabled' => true, 'locked' => false],
                        ['id' => 'foto_surat_pernyataan', 'source' => 'builtin', 'key' => 'foto_surat_pernyataan', 'label' => 'Surat Pernyataan', 'type' => 'file', 'required' => false, 'enabled' => false, 'locked' => false],
                    ],
                ],
                [
                    'id'          => 'sec_tambahan',
                    'title'       => 'Informasi Tambahan',
                    'short_title' => 'Tambahan',
                    'description' => 'Pertanyaan khusus panitia',
                    'enabled'     => true,
                    'fields'      => [
                        [
                            'id' => 'cf_seragam', 'source' => 'custom', 'key' => 'cf_seragam',
                            'label' => 'Ukuran Baju Seragam', 'type' => 'radio', 'required' => true, 'enabled' => true,
                            'options' => ['S', 'M', 'L'],
                        ],
                        [
                            'id' => 'cf_hobi', 'source' => 'custom', 'key' => 'cf_hobi',
                            'label' => 'Minat & Bakat Anak', 'type' => 'checkbox', 'required' => true, 'enabled' => true,
                            'options' => ['Menggambar', 'Tahfidz', 'Olahraga'],
                        ],
                        [
                            'id' => 'cf_info', 'source' => 'custom', 'key' => 'cf_info',
                            'label' => 'Mengetahui Sekolah Dari', 'type' => 'select', 'required' => false, 'enabled' => true,
                            'options' => ['Instagram', 'WhatsApp', 'Tetangga'],
                        ],
                        [
                            'id' => 'cf_catatan', 'source' => 'custom', 'key' => 'cf_catatan',
                            'label' => 'Catatan Khusus Orang Tua', 'type' => 'textarea', 'required' => false, 'enabled' => true,
                        ],
                        [
                            'id' => 'cf_lampiran', 'source' => 'custom', 'key' => 'cf_lampiran',
                            'label' => 'Piagam Prestasi', 'type' => 'file', 'required' => false, 'enabled' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function test_admin_dapat_membuka_dan_menyimpan_desain_formulir_kustom(): void
    {
        $respGet = $this->actingAs($this->yayasanUser)
            ->get(route('penerimaan.formulir.edit', $this->eventSdit->id));
        $respGet->assertStatus(200);
        $respGet->assertSee('Desain Formulir');
        $respGet->assertSee('Preset Ringkas (RA / SD IT)');

        $respPut = $this->actingAs($this->yayasanUser)
            ->put(route('penerimaan.formulir.update', $this->eventSdit->id), [
                'form_schema_json' => json_encode($this->sampleCustomSchema()),
            ]);
        $respPut->assertRedirect(route('penerimaan.formulir.edit', $this->eventSdit->id));
        $respPut->assertSessionHas('success');

        $fresh = $this->eventSdit->fresh();
        $this->assertTrue($fresh->hasCustomFormSchema());
        $this->assertEquals('single', $fresh->form_schema['layout']);
        $this->assertEquals('opsional', $fresh->syarat_dokumen['berkas_akta']);
        $this->assertEquals('tidak', $fresh->syarat_dokumen['berkas_surat_pernyataan']);

        // Badge Form Kustom muncul di halaman daftar Link Penerimaan
        $indexResp = $this->actingAs($this->yayasanUser)->get(route('penerimaan.index'));
        $indexResp->assertStatus(200);
        $indexResp->assertSee('Form Kustom');
    }

    public function test_form_publik_merender_memvalidasi_dan_menyimpan_jawaban_kustom(): void
    {
        Storage::fake('public');

        $this->actingAs($this->yayasanUser)
            ->put(route('penerimaan.formulir.update', $this->eventSdit->id), [
                'form_schema_json' => json_encode($this->sampleCustomSchema()),
            ])
            ->assertRedirect();

        // 1. Tampilan publik memuat label & pertanyaan kustom dan menyembunyikan NISN
        $respShow = $this->get(route('spmb.show', $this->eventSdit->slug));
        $respShow->assertStatus(200);
        $respShow->assertSee('Nama Lengkap Ananda');
        $respShow->assertSee('Ukuran Baju Seragam');
        $respShow->assertSee('Minat &amp; Bakat Anak', false);
        $respShow->assertSee('Piagam Prestasi');
        $respShow->assertDontSee('name="nisn"', false);

        // 2. Submit gagal bila pertanyaan kustom wajib kosong
        $respInvalid = $this->post(route('spmb.store', $this->eventSdit->slug), [
            'jenjang'       => 'SD IT',
            'nama_lengkap'  => 'Muhammad Zaki',
            'tanggal_lahir' => '2020-05-12',
        ]);
        $respInvalid->assertSessionHasErrors(['custom_fields.cf_seragam', 'custom_fields.cf_hobi']);

        // 3. Submit sukses meski jenis_kelamin & pj_biaya_hp tidak ditampilkan di form kustom
        $fakePdf = UploadedFile::fake()->create('piagam-tahfidz.pdf', 120, 'application/pdf');
        $respValid = $this->post(route('spmb.store', $this->eventSdit->slug), [
            'jenjang'       => 'SD IT',
            'nama_lengkap'  => 'Muhammad Zaki',
            'tanggal_lahir' => '2020-05-12',
            'custom_fields' => [
                'cf_seragam'  => 'M',
                'cf_hobi'     => ['Tahfidz', 'Olahraga'],
                'cf_info'     => 'Instagram',
                'cf_catatan'  => 'Suka membaca buku cerita Islami.',
                'cf_lampiran' => $fakePdf,
            ],
        ]);

        $pendaftaran = Pendaftaran::where('nama_lengkap', 'Muhammad Zaki')->first();
        $this->assertNotNull($pendaftaran);
        $respValid->assertRedirect(route('spmb.bukti', [
            'slug'  => $this->eventSdit->slug,
            'token' => $pendaftaran->public_token,
        ]));

        $this->assertIsArray($pendaftaran->custom_responses);
        $this->assertCount(5, $pendaftaran->custom_responses);

        $fileResp = collect($pendaftaran->custom_responses)->firstWhere('id', 'cf_lampiran');
        $this->assertNotNull($fileResp);
        Storage::disk('public')->assertExists($fileResp['value']);

        // 4. Jawaban kustom tampil di bukti publik, cetak A4, dan detail admin
        $this->get(route('spmb.bukti', ['slug' => $this->eventSdit->slug, 'token' => $pendaftaran->public_token]))
            ->assertStatus(200)
            ->assertSee('Ukuran Baju Seragam')
            ->assertSee('Tahfidz, Olahraga')
            ->assertSee('piagam-tahfidz.pdf');

        $this->get(route('spmb.cetak', ['slug' => $this->eventSdit->slug, 'token' => $pendaftaran->public_token]))
            ->assertStatus(200)
            ->assertSee('INFORMASI TAMBAHAN')
            ->assertSee('Ukuran Baju Seragam')
            ->assertSee('Tahfidz, Olahraga');

        $this->actingAs($this->yayasanUser)
            ->get(route('pendaftaran.show', $pendaftaran->id))
            ->assertStatus(200)
            ->assertSee('5. Informasi Tambahan (Formulir Kustom)')
            ->assertSee('Suka membaca buku cerita Islami.')
            ->assertSee('piagam-tahfidz.pdf');
    }

    public function test_reset_desain_formulir_mengembalikan_ke_standar_bawaan(): void
    {
        $this->eventSdit->update(['form_schema' => $this->sampleCustomSchema()]);
        $this->assertTrue($this->eventSdit->fresh()->hasCustomFormSchema());

        $this->actingAs($this->yayasanUser)
            ->post(route('penerimaan.formulir.reset', $this->eventSdit->id))
            ->assertRedirect(route('penerimaan.formulir.edit', $this->eventSdit->id));

        $this->assertFalse($this->eventSdit->fresh()->hasCustomFormSchema());
        $this->get(route('spmb.show', $this->eventSdit->slug))
            ->assertStatus(200)
            ->assertSee('name="nisn"', false);
    }

    public function test_isolasi_room_admin_sdit_tidak_bisa_edit_form_builder_smp(): void
    {
        // Akses event SMP ditolak 403
        $this->actingAs($this->guruSdit)
            ->get(route('penerimaan.formulir.edit', $this->eventSmp->id))
            ->assertStatus(403);

        $this->actingAs($this->guruSdit)
            ->put(route('penerimaan.formulir.update', $this->eventSmp->id), [
                'form_schema_json' => json_encode($this->sampleCustomSchema()),
            ])
            ->assertStatus(403);

        $this->actingAs($this->guruSdit)
            ->post(route('penerimaan.formulir.reset', $this->eventSmp->id))
            ->assertStatus(403);

        // Akses event milik SD IT sendiri berhasil
        $this->actingAs($this->guruSdit)
            ->get(route('penerimaan.formulir.edit', $this->eventSdit->id))
            ->assertStatus(200);

        $this->actingAs($this->guruSdit)
            ->put(route('penerimaan.formulir.update', $this->eventSdit->id), [
                'form_schema_json' => json_encode($this->sampleCustomSchema()),
            ])
            ->assertRedirect(route('penerimaan.formulir.edit', $this->eventSdit->id));
    }

    public function test_ubah_nama_dan_singkatan_lembaga_otomatis_sinkron_ke_dropdown_spmb(): void
    {
        $raKota = Lembaga::create([
            'kode' => 'ra', 'nama' => 'RA AL MUTTAQIN', 'singkatan' => 'RA',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 4,
        ]);
        $raGemarang = Lembaga::create([
            'kode' => 'ra-gemarang', 'nama' => 'ra gemarang', 'singkatan' => 'ra gemarang',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 5,
        ]);

        $eventRa = PenerimaanSantri::create([
            'judul'        => 'SPMB RA AL MUTTAQIN',
            'slug'         => 'spmb-ra-al-muttaqin',
            'tahun_ajaran' => '2026/2027',
            'jenjang'      => 'RA, ra gemarang',
            'jenjangs'     => ['RA', 'ra gemarang'],
            'kontak_wa_per_jenjang'      => ['RA' => '08111111111', 'ra gemarang' => '08222222222'],
            'lokasi_seleksi_per_jenjang' => ['RA' => 'Madiun Kota', 'ra gemarang' => 'Gemarang'],
            'is_active'    => true,
            'created_by'   => $this->yayasanUser->id,
        ]);

        // Admin mengganti nama, kode, dan singkatan kedua lembaga lewat Master Lembaga
        $this->actingAs($this->yayasanUser)->put(route('master.lembaga.update', $raKota->id), [
            'kode'      => 'ra-al-muttaqin-kota-madiun',
            'nama'      => 'RA AL MUTTAQIN KOTA',
            'singkatan' => 'RA AL MUTTAQIN KOTA MADIUN',
            'kategori'  => 'formal',
            'is_active' => '1',
        ])->assertRedirect(route('master.lembaga.index'));

        $this->actingAs($this->yayasanUser)->put(route('master.lembaga.update', $raGemarang->id), [
            'kode'      => 'ra-al-muttaqin-gemarang',
            'nama'      => 'RA AL MUTTAQIN GEMARANG',
            'singkatan' => 'RA AL MUTTAQIN GEMARANG',
            'kategori'  => 'formal',
            'is_active' => '1',
        ])->assertRedirect(route('master.lembaga.index'));

        $eventRa->refresh();
        $this->assertEquals(['RA AL MUTTAQIN KOTA MADIUN', 'RA AL MUTTAQIN GEMARANG'], $eventRa->jenjangs);
        $this->assertEquals('08111111111', $eventRa->kontak_wa_per_jenjang['RA AL MUTTAQIN KOTA MADIUN']);
        $this->assertEquals('Gemarang', $eventRa->lokasi_seleksi_per_jenjang['RA AL MUTTAQIN GEMARANG']);

        $resp = $this->get(route('spmb.show', $eventRa->slug));
        $resp->assertStatus(200);
        $resp->assertSee('RA AL MUTTAQIN KOTA MADIUN');
        $resp->assertSee('RA AL MUTTAQIN GEMARANG');
        $resp->assertDontSee('ra gemarang');
    }

    public function test_sync_otomatis_key_jenjang_usang_pada_event_spmb_yang_sudah_terlanjur_diubah(): void
    {
        // Simulasi kondisi VPS: Master Lembaga sudah terlanjur diubah duluan,
        // sedangkan event SPMB masih menyimpan ["RA", "ra gemarang"]
        Lembaga::create([
            'kode' => 'ra-al-muttaqin-kota-madiun', 'nama' => 'RA AL MUTTAQIN KOTA',
            'singkatan' => 'RA AL MUTTAQIN KOTA MADIUN', 'kategori' => 'formal', 'is_active' => true, 'urutan' => 4,
        ]);
        Lembaga::create([
            'kode' => 'ra-al-muttaqin-gemarang', 'nama' => 'RA AL MUTTAQIN GEMARANG',
            'singkatan' => 'RA AL MUTTAQIN GEMARANG', 'kategori' => 'formal', 'is_active' => true, 'urutan' => 5,
        ]);

        $staleEvent = PenerimaanSantri::create([
            'judul'        => 'SPMB RA EXISTING',
            'slug'         => 'spmb-ra-existing',
            'tahun_ajaran' => '2026/2027',
            'jenjang'      => 'RA, ra gemarang',
            'jenjangs'     => ['RA', 'ra gemarang'],
            'is_active'    => true,
            'created_by'   => $this->yayasanUser->id,
        ]);

        $resp = $this->get(route('spmb.show', $staleEvent->slug));
        $resp->assertStatus(200);
        $resp->assertSee('RA AL MUTTAQIN KOTA MADIUN');
        $resp->assertSee('RA AL MUTTAQIN GEMARANG');
        $resp->assertDontSee('ra gemarang');

        $staleEvent->refresh();
        $this->assertEquals(['RA AL MUTTAQIN KOTA MADIUN', 'RA AL MUTTAQIN GEMARANG'], $staleEvent->jenjangs);
    }

    public function test_prefix_nomor_registrasi_kustom_di_master_lembaga_dipakai_saat_pendaftaran_spmb(): void
    {
        $raKota = Lembaga::create([
            'kode' => 'ra-kota', 'nama' => 'RA AL MUTTAQIN KOTA',
            'singkatan' => 'RA AL MUTTAQIN KOTA MADIUN', 'prefix_registrasi' => 'RAKT',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 4,
        ]);
        $raGemarang = Lembaga::create([
            'kode' => 'ra-gemarang', 'nama' => 'RA AL MUTTAQIN GEMARANG',
            'singkatan' => 'RA AL MUTTAQIN GEMARANG', 'prefix_registrasi' => 'RAGM',
            'kategori' => 'formal', 'is_active' => true, 'urutan' => 5,
        ]);

        // Update prefix lewat Master Lembaga tetap ternormalisasi huruf kapital tanpa spasi/simbol
        $this->actingAs($this->yayasanUser)->put(route('master.lembaga.update', $raGemarang->id), [
            'kode'              => 'ra-gemarang',
            'nama'              => 'RA AL MUTTAQIN GEMARANG',
            'singkatan'         => 'RA AL MUTTAQIN GEMARANG',
            'prefix_registrasi' => ' ra-gm ',
            'kategori'          => 'formal',
            'is_active'         => '1',
        ])->assertRedirect(route('master.lembaga.index'));

        $this->assertEquals('RAGM', $raGemarang->fresh()->prefix_registrasi);

        $eventRa = PenerimaanSantri::create([
            'judul'        => 'SPMB RA PREFIX',
            'slug'         => 'spmb-ra-prefix',
            'tahun_ajaran' => '2026/2027',
            'jenjang'      => 'RA AL MUTTAQIN KOTA MADIUN, RA AL MUTTAQIN GEMARANG',
            'jenjangs'     => ['RA AL MUTTAQIN KOTA MADIUN', 'RA AL MUTTAQIN GEMARANG'],
            'is_active'    => true,
            'created_by'   => $this->yayasanUser->id,
        ]);

        $this->post(route('spmb.store', $eventRa->slug), [
            'jenjang'       => $raKota->jenjang_key,
            'nama_lengkap'  => 'Calon Siswa Kota',
            'jenis_kelamin' => 'Laki-laki',
            'pj_biaya_hp'   => '08123456789',
        ])->assertRedirect();

        $this->post(route('spmb.store', $eventRa->slug), [
            'jenjang'       => $raGemarang->jenjang_key,
            'nama_lengkap'  => 'Calon Siswa Gemarang',
            'jenis_kelamin' => 'Perempuan',
            'pj_biaya_hp'   => '08123456780',
        ])->assertRedirect();

        $this->assertDatabaseHas('pendaftarans', [
            'nama_lengkap' => 'Calon Siswa Kota',
            'no_induk'     => 'RAKT-01-202627',
        ]);
        $this->assertDatabaseHas('pendaftarans', [
            'nama_lengkap' => 'Calon Siswa Gemarang',
            'no_induk'     => 'RAGM-01-202627',
        ]);
    }

    public function test_formulir_cetak_dan_detail_hanya_menyertakan_persyaratan_yang_diaktifkan(): void
    {
        // 1. Event dengan Surat Pernyataan = Tidak Diminta, 3 lainnya Opsional
        $event = PenerimaanSantri::create([
            'judul'          => 'SPMB RA Syarat Dinamis',
            'slug'           => 'spmb-ra-syarat-dinamis',
            'tahun_ajaran'   => '2026/2027',
            'jenjang'        => 'SD IT',
            'jenjangs'       => ['SD IT'],
            'syarat_dokumen' => [
                'berkas_akta'             => 'opsional',
                'berkas_kk'               => 'opsional',
                'berkas_ktp_ortu'         => 'opsional',
                'berkas_surat_pernyataan' => 'tidak',
            ],
            'is_active'      => true,
            'created_by'     => $this->yayasanUser->id,
        ]);

        $this->post(route('spmb.store', $event->slug), [
            'jenjang'       => 'SD IT',
            'nama_lengkap'  => 'Aisyah Syarat',
            'jenis_kelamin' => 'Perempuan',
            'pj_biaya_hp'   => '08123456700',
        ])->assertRedirect();

        $p = Pendaftaran::where('nama_lengkap', 'Aisyah Syarat')->firstOrFail();

        // Formulir cetak publik (2 halaman) & cetak admin: 3 syarat tampil, Surat Sanggup Mentaati tidak ikut
        $this->get(route('spmb.cetak', [$event->slug, $p->public_token]))
            ->assertOk()
            ->assertSee('PERSYARATAN')
            ->assertSee('Foto Copy Akta Kelahiran')
            ->assertSee('Foto Copy Kartu Keluarga')
            ->assertSee('Foto Copy KTP Ayah &amp; Ibu', false)
            ->assertDontSee('Surat Sanggup Mentaati')
            ->assertDontSee('Peraturan Pondok');

        $this->actingAs($this->yayasanUser)
            ->get(route('pendaftaran.cetak', $p->id))
            ->assertOk()
            ->assertSee('Foto Copy Akta Kelahiran')
            ->assertSee('Foto Copy Kartu Keluarga')
            ->assertSee('Foto Copy KTP Ayah &amp; Ibu', false)
            ->assertDontSee('Surat Sanggup Mentaati');

        // Halaman detail & edit admin juga tidak menyertakan Surat Pernyataan yang tidak diminta
        $this->actingAs($this->yayasanUser)
            ->get(route('pendaftaran.show', $p->id))
            ->assertOk()
            ->assertSee('Akta Kelahiran')
            ->assertDontSee('Surat Pernyataan');

        $this->actingAs($this->yayasanUser)
            ->get(route('pendaftaran.edit', $p->id))
            ->assertOk()
            ->assertSee('Akta Kelahiran')
            ->assertDontSee('Surat Pernyataan / Rekomendasi');

        // 2. Jika seluruh 4 dokumen diset Tidak Diminta, seksi PERSYARATAN tidak muncul sama sekali
        // dan seksi INFORMASI TAMBAHAN otomatis bernomor 3.
        $event->update([
            'syarat_dokumen' => [
                'berkas_akta'             => 'tidak',
                'berkas_kk'               => 'tidak',
                'berkas_ktp_ortu'         => 'tidak',
                'berkas_surat_pernyataan' => 'tidak',
            ],
        ]);
        $p->update([
            'custom_responses' => [
                ['id' => 'cf_catatan', 'section' => 'Tambahan', 'label' => 'Hobi Anak', 'type' => 'text', 'value' => 'Membaca'],
            ],
        ]);

        $this->get(route('spmb.cetak', [$event->slug, $p->public_token]))
            ->assertOk()
            ->assertDontSee('PERSYARATAN')
            ->assertDontSee('Foto Copy Akta Kelahiran')
            ->assertDontSee('Surat Sanggup Mentaati')
            ->assertSee('3.&nbsp; INFORMASI TAMBAHAN', false)
            ->assertSee('Hobi Anak');

        // 3. Pendaftaran manual admin (tanpa link penerimaan) tetap menampilkan 4 syarat standar
        $manual = Pendaftaran::create([
            'jenjang'            => 'SMP',
            'no_induk'           => 'PSB-2026-0001',
            'nama_lengkap'       => 'Santri Manual',
            'status_pendaftaran' => 'Pending',
        ]);

        $this->actingAs($this->yayasanUser)
            ->get(route('pendaftaran.cetak', $manual->id))
            ->assertOk()
            ->assertSee('PERSYARATAN')
            ->assertSee('Foto Copy Akta Kelahiran')
            ->assertSee('Surat Sanggup Mentaati');
    }

    public function test_ubah_dokumen_di_edit_penerimaan_otomatis_sinkron_ke_form_kustom_dan_form_publik(): void
    {
        // Simpan form builder kustom terlebih dahulu (foto_akta aktif, foto_kk/ktp/surat_pernyataan nonaktif)
        $this->actingAs($this->yayasanUser)
            ->put(route('penerimaan.formulir.update', $this->eventSdit->id), [
                'form_schema_json' => json_encode($this->sampleCustomSchema()),
            ])
            ->assertRedirect();

        // Kemudian ubah pengaturan Dokumen & Syarat di halaman Edit Penerimaan:
        // Matikan Akta & Surat Pernyataan, aktifkan KK (wajib) & KTP Ortu (opsional)
        $this->actingAs($this->yayasanUser)
            ->put(route('penerimaan.update', $this->eventSdit->id), [
                'judul'          => $this->eventSdit->judul,
                'slug'           => $this->eventSdit->slug,
                'tahun_ajaran'   => $this->eventSdit->tahun_ajaran,
                'jenjangs'       => ['SD IT'],
                'is_active'      => '1',
                'syarat_dokumen' => [
                    'berkas_akta'             => 'tidak',
                    'berkas_kk'               => 'wajib',
                    'berkas_ktp_ortu'         => 'opsional',
                    'berkas_surat_pernyataan' => 'tidak',
                ],
            ])
            ->assertRedirect(route('penerimaan.index'));

        // Form online publik langsung mengikuti perubahan Dokumen & Syarat
        $this->get(route('spmb.show', $this->eventSdit->slug))
            ->assertOk()
            ->assertDontSee('name="foto_akta"', false)
            ->assertDontSee('name="foto_surat_pernyataan"', false)
            ->assertSee('name="foto_kk"', false)
            ->assertSee('name="foto_ktp_ortu"', false);
    }
}
