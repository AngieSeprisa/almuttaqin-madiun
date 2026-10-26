<?php

namespace Tests\Feature;

use App\Models\Pendaftaran;
use App\Models\Siswa;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ImportSiswaTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LembagaAndUserSeeder::class);
        $this->admin = User::where('email', 'yayasan@almuttaqin.id')->first();
    }

    /**
     * Test halaman import siswa dapat diakses.
     */
    public function test_halaman_import_siswa_dapat_diakses()
    {
        $response = $this->actingAs($this->admin)->get(route('master.import-siswa.index'));

        $response->assertStatus(200);
        $response->assertSee('Import Data Siswa');
        $response->assertSee('Unduh Template (.XLSX)');
    }

    protected function makeXlsx(array $rows, string $name = 'santri_import.xlsx'): UploadedFile
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A1', true);
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.uniqid('imp_').'.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    /**
     * Test template Excel memuat seluruh kolom registrasi pendaftaran.
     */
    public function test_download_template_excel_berhasil()
    {
        $response = $this->actingAs($this->admin)->get(route('master.import-siswa.template'));

        $response->assertStatus(200);
        $response->assertDownload('template_import_siswa_almuttaqin.xlsx');
    }

    /**
     * Test import Excel menambahkan santri baru beserta seluruh biodata pendaftarannya.
     */
    public function test_import_siswa_excel_berhasil_membuat_data_baru()
    {
        $file = $this->makeXlsx([
            ['nama_lengkap', 'jenjang', 'kamar', 'status', 'nik', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'ayah_nama', 'ibu_nama', 'pj_biaya_hp', 'kecamatan', 'gol_darah', 'anak_ke', 'berkas_kk'],
            ['Ahmad Fauzi Mubarak', 'SMP', 'Abu Bakar 01', 'aktif', '3519010101080001', 'Laki-laki', 'Madiun', '2008-05-15', 'Budi Santoso', 'Siti Aminah', '081234567890', 'Taman', 'O', 2, 'Ya'],
            ['Fatimah Az-Zahra', 'SMA', 'Aisyah 02', 'aktif', '3519010101080002', 'Perempuan', 'Surabaya', '2007-08-20', 'Ahmad Dahlan', 'Khadijah', '081234567891', '', '', '', 'Tidak'],
        ]);

        $response = $this->actingAs($this->admin)->post(route('master.import-siswa.store'), [
            'file_siswa' => $file,
            'update_existing' => 1,
        ]);

        $response->assertRedirect(route('siswa'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('siswas', [
            'nama_lengkap' => 'Ahmad Fauzi Mubarak',
            'jenjang' => 'SMP',
            'kamar' => 'Abu Bakar 01',
            'status' => 'aktif',
        ]);

        $this->assertDatabaseHas('siswas', [
            'nama_lengkap' => 'Fatimah Az-Zahra',
            'jenjang' => 'SMA',
            'kamar' => 'Aisyah 02',
            'status' => 'aktif',
        ]);

        $this->assertDatabaseHas('pendaftarans', [
            'nama_lengkap' => 'Ahmad Fauzi Mubarak',
            'nik' => '3519010101080001',
            'ayah_nama' => 'Budi Santoso',
            'kecamatan' => 'Taman',
            'gol_darah' => 'O',
            'anak_ke' => 2,
            'berkas_kk' => 1,
        ]);
    }

    public function test_import_menolak_file_csv()
    {
        $file = UploadedFile::fake()->createWithContent('x.csv', "nama_lengkap\nA\n");

        $this->actingAs($this->admin)->post(route('master.import-siswa.store'), ['file_siswa' => $file])
            ->assertSessionHasErrors('file_siswa');
    }

    /**
     * Test import ulang memperbarui siswa yang sudah ada.
     */
    public function test_import_excel_memperbarui_siswa_yang_sudah_ada()
    {
        $header = ['nama_lengkap', 'kamar', 'ayah_nama'];
        $this->actingAs($this->admin)->post(route('master.import-siswa.store'), [
            'file_siswa' => $this->makeXlsx([$header, ['Muhammad Ridwan', 'Kamar 1', 'Hasan']]),
        ]);
        $this->actingAs($this->admin)->post(route('master.import-siswa.store'), [
            'file_siswa' => $this->makeXlsx([$header, ['Muhammad Ridwan', 'Kamar 2', 'Husain']]),
            'update_existing' => 1,
        ])->assertRedirect(route('siswa'));

        $this->assertDatabaseCount('siswas', 1);
        $this->assertDatabaseHas('siswas', ['nama_lengkap' => 'Muhammad Ridwan', 'kamar' => 'Kamar 2']);
        $this->assertDatabaseHas('pendaftarans', ['nama_lengkap' => 'Muhammad Ridwan', 'ayah_nama' => 'Husain']);
    }

    /**
     * Test identifikasi dan penandaan baris siswa yang data dirinya belum lengkap (warna merah tipis).
     */
    public function test_tabel_siswa_menandai_baris_kurang_lengkap_dengan_merah_tipis()
    {
        // 1. Siswa dengan data lengkap
        $pendaftaranLengkap = Pendaftaran::create([
            'nama_lengkap' => 'Santri Lengkap',
            'jenjang' => 'SMP',
            'no_induk' => 'PSB-2026-0001',
            'nik' => '3519010101080010',
            'tempat_lahir' => 'Madiun',
            'tanggal_lahir' => '2008-01-01',
            'jenis_kelamin' => 'Laki-laki',
            'alamat_jalan' => 'Jl. Pahlawan No. 1',
            'ayah_nama' => 'Ayah Santri',
            'ibu_nama' => 'Ibu Santri',
            'pj_biaya_hp' => '081234567890',
            'status_pendaftaran' => 'Diterima',
        ]);

        $siswaLengkap = Siswa::create([
            'nama_lengkap' => 'Santri Lengkap',
            'jenjang' => 'SMP',
            'kamar' => 'Kamar A',
            'status' => 'aktif',
            'pendaftaran_id' => $pendaftaranLengkap->id,
        ]);

        // 2. Siswa dengan data belum lengkap (hanya nama dan kamar, tanpa NIK / ayah / no HP)
        $pendaftaranKurang = Pendaftaran::create([
            'nama_lengkap' => 'Santri Belum Lengkap',
            'jenjang' => 'SMA',
            'no_induk' => 'PSB-2026-0002',
            'status_pendaftaran' => 'Diterima',
        ]);

        $siswaKurang = Siswa::create([
            'nama_lengkap' => 'Santri Belum Lengkap',
            'jenjang' => 'SMA',
            'kamar' => 'Kamar B',
            'status' => 'aktif',
            'pendaftaran_id' => $pendaftaranKurang->id,
        ]);

        $this->assertTrue($siswaLengkap->isDataLengkap());
        $this->assertFalse($siswaKurang->isDataLengkap());
        $this->assertNotEmpty($siswaKurang->field_belum_lengkap);

        // Kunjungi halaman index siswa
        $response = $this->actingAs($this->admin)->get(route('siswa'));

        $response->assertStatus(200);
        $response->assertSee('row-incomplete');
        $response->assertSee('Perlu Dilengkapi');
        $response->assertSee('Santri Belum Lengkap');

        // Test filter kelengkapan
        $filterKurang = $this->actingAs($this->admin)->get(route('siswa', ['kelengkapan' => 'belum_lengkap']));
        $filterKurang->assertStatus(200);
        $filterKurang->assertSee('Santri Belum Lengkap');
        $filterKurang->assertDontSee('Santri Lengkap');

        $filterLengkap = $this->actingAs($this->admin)->get(route('siswa', ['kelengkapan' => 'lengkap']));
        $filterLengkap->assertStatus(200);
        $filterLengkap->assertSee('Santri Lengkap');
        $filterLengkap->assertDontSee('Santri Belum Lengkap');
    }
}
