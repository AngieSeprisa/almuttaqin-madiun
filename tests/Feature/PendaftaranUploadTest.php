<?php

namespace Tests\Feature;

use App\Models\Pendaftaran;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PendaftaranUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LembagaAndUserSeeder::class);
        $this->admin = User::where('email', 'yayasan@almuttaqin.id')->first();
    }

    protected function payload(array $extra = []): array
    {
        return array_merge([
            'jenjang' => 'SMP',
            'nik' => '3507010101010001',
            'nama_lengkap' => 'Ahmad Fulan',
            'nama_panggilan' => 'Fulan',
            'tempat_lahir' => 'Malang',
            'tanggal_lahir' => '2010-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'anak_ke' => 1,
            'jumlah_saudara' => 2,
            'tinggal_bersama' => 'Orang Tua',
            'alamat_jalan' => 'Jl. Mawar 1',
            'rt_rw' => '01/02',
            'desa_kelurahan' => 'Sukun',
            'kecamatan' => 'Sukun',
            'kab_kota' => 'Malang',
            'kode_pos' => '65147',
            'provinsi' => 'Jawa Timur',
            'warga_negara' => 'Indonesia',
            'kelompok' => 'A',
            'desa' => 'B',
            'daerah' => 'C',
            'pendidikan_terakhir' => 'SD',
            'status_mondok' => 'Person',
            'pj_biaya_nama' => 'Bapak Fulan',
            'pj_biaya_hp' => '08123456789',
            'ayah_nama' => 'Bapak Fulan',
            'ayah_status' => 'Hidup',
            'ibu_nama' => 'Ibu Fulan',
            'ibu_status' => 'Hidup',
        ], $extra);
    }

    public function test_foto_berkas_dikompresi_dan_disimpan(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('pendaftaran.store'), $this->payload([
                'foto_kk' => UploadedFile::fake()->image('kk.png', 3000, 2000),
            ]));

        $p = Pendaftaran::where('nama_lengkap', 'Ahmad Fulan')->firstOrFail();
        $response->assertRedirect(route('pendaftaran.show', $p->id));

        $this->assertNotNull($p->foto_kk);
        $this->assertNull($p->foto_akta);
        $this->assertTrue((bool)$p->berkas_kk, 'Checklist otomatis ditandai saat foto diunggah.');
        Storage::disk('public')->assertExists($p->foto_kk);

        // Hasil: JPEG dengan sisi terpanjang <= 1600 px.
        [$w, $h, $type] = getimagesizefromstring(Storage::disk('public')->get($p->foto_kk));
        $this->assertSame(IMAGETYPE_JPEG, $type);
        $this->assertLessThanOrEqual(1600, max($w, $h));
    }

    public function test_pendaftaran_tanpa_foto_tetap_berhasil(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('pendaftaran.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertNull(Pendaftaran::where('nama_lengkap', 'Ahmad Fulan')->firstOrFail()->foto_kk);
    }

    public function test_file_bukan_gambar_ditolak(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('pendaftaran.store'), $this->payload([
                'foto_akta' => UploadedFile::fake()->create('virus.pdf', 100, 'application/pdf'),
            ]))
            ->assertSessionHasErrors('foto_akta');
    }
}
