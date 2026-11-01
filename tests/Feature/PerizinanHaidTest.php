<?php

namespace Tests\Feature;

use App\Models\Pendaftaran;
use App\Models\PerizinanHaid;
use App\Models\Presensi;
use App\Models\Siswa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerizinanHaidTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role'      => 'yayasan',
            'is_active' => true,
        ]);
    }

    private function createSantriwati(array $attrs = []): Siswa
    {
        $pendaftaran = Pendaftaran::create([
            'nama_lengkap'  => $attrs['nama_lengkap'] ?? 'Fatimah Az Zahra',
            'nisn'          => '00' . rand(10000000, 99999999),
            'nik'           => (string) rand(1000000000000000, 9999999999999999),
            'jenis_kelamin' => 'Perempuan',
            'tempat_lahir'  => 'Madiun',
            'tanggal_lahir' => '2010-05-15',
            'alamat_rumah'  => 'Jl. Pesantren No. 10',
            'jenjang'       => 'SMP AL MUTTAQIN',
            'status'        => 'diterima',
        ]);

        return Siswa::create(array_merge([
            'pendaftaran_id' => $pendaftaran->id,
            'nama_lengkap'   => $pendaftaran->nama_lengkap,
            'kamar'          => 'Kamar Khadijah',
            'jenjang'        => 'SMP AL MUTTAQIN',
            'kelas'          => '8B',
            'jenis_kelamin'  => 'Perempuan',
            'status'         => 'aktif',
        ], $attrs));
    }

    public function test_halaman_index_perizinan_haid_dapat_diakses(): void
    {
        $santri = $this->createSantriwati();
        PerizinanHaid::create([
            'siswa_id'       => $santri->id,
            'nama'           => $santri->nama_lengkap,
            'kamar'          => $santri->kamar,
            'tanggal_mulai'  => '2026-11-01',
            'status'         => 'sedang_haid',
            'keterangan'     => 'Udzur syar\'i sholat',
        ]);

        $response = $this->actingAs($this->admin)->get(route('perizinan.haid'));
        $response->assertStatus(200);
        $response->assertSee('Perizinan Haid (Udzur Sholat)');
        $response->assertSee('Fatimah Az Zahra');
        $response->assertSee('Sedang Haid');
    }

    public function test_halaman_create_perizinan_haid_menampilkan_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('perizinan.haid.create'));
        $response->assertStatus(200);
        $response->assertSee('Catat Santriwati Haid');
    }

    public function test_store_perizinan_haid_berhasil_menyimpan_data(): void
    {
        $santri = $this->createSantriwati();

        $payload = [
            'siswa_id'       => $santri->id,
            'nama'           => $santri->nama_lengkap,
            'kamar'          => $santri->kamar,
            'tanggal_mulai'  => '2026-11-01',
            'status'         => 'sedang_haid',
            'keterangan'     => 'Haid hari pertama',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('perizinan.haid.store'), $payload);

        $response->assertRedirect(route('perizinan.haid'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('perizinan_haids', [
            'siswa_id'   => $santri->id,
            'status'     => 'sedang_haid',
            'keterangan' => 'Haid hari pertama',
        ]);
    }

    public function test_suci_perizinan_haid_menandai_sudah_suci(): void
    {
        $santri = $this->createSantriwati();
        $haid = PerizinanHaid::create([
            'siswa_id'       => $santri->id,
            'nama'           => $santri->nama_lengkap,
            'kamar'          => $santri->kamar,
            'tanggal_mulai'  => '2026-10-25',
            'status'         => 'sedang_haid',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('perizinan.haid.suci', $haid->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $haid->refresh();
        $this->assertEquals('sudah_suci', $haid->status);
        $this->assertNotNull($haid->tanggal_selesai);
    }

    public function test_santriwati_sedang_haid_otomatis_diakui_sebagai_haid_dan_bukan_alpa_pada_rekap_pondok(): void
    {
        $santri = $this->createSantriwati(['nama_lengkap' => 'Aisyah Putri']);
        $tanggal = '2026-11-01';

        // Buat izin haid aktif untuk hari ini
        PerizinanHaid::create([
            'siswa_id'       => $santri->id,
            'nama'           => $santri->nama_lengkap,
            'kamar'          => $santri->kamar,
            'tanggal_mulai'  => '2026-11-01',
            'status'         => 'sedang_haid',
            'keterangan'     => 'Udzur syar\'i sholat',
        ]);

        $response = $this->actingAs($this->admin)->get(route('rekap.sholat.index', [
            'kategori' => 'sholat',
            'rentang'  => 'harian',
            'tanggal'  => $tanggal,
            'search'   => 'Aisyah Putri',
        ]));

        $response->assertStatus(200);

        // Siswi ini harus tercatat Izin (total_i = 5), dan Alpa = 0
        $rows = $response->viewData('rows');
        $this->assertNotEmpty($rows);

        $row = collect($rows)->firstWhere('siswa_id', $santri->id);
        $this->assertNotNull($row);

        // 5 sesi sholat semuanya HAID (diakumulasikan ke total_i / izin)
        $this->assertEquals(5, $row['total_i']);
        $this->assertEquals(0, $row['total_a']);

        // Riwayat tiap sesi bersubjek HAID
        foreach ($row['riwayat'] as $rw) {
            $this->assertEquals('HAID', $rw['status']);
            $this->assertEquals('status-badge-haid', $rw['badge']);
        }

        // WhatsApp report berisi emoji 🌸
        $waText = $response->viewData('waText');
        $this->assertNotEmpty($waText);
        $this->assertStringContainsString('🌸', $waText);
        $this->assertStringContainsString('Aisyah Putri', $waText);
        $this->assertStringContainsString('haid - subuh', $waText);
    }

    public function test_update_status_sesi_ajax_mendukung_status_haid(): void
    {
        $santri = $this->createSantriwati(['nama_lengkap' => 'Zahra Humaira']);
        $tanggal = '2026-11-01';

        $payload = [
            'siswa_id' => $santri->id,
            'tanggal'  => $tanggal,
            'slot_key' => 'dzuhur',
            'status'   => 'haid',
        ];

        $response = $this->actingAs($this->admin)
            ->postJson(route('rekap.pondok.update-status'), $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data'    => [
                'siswa_id' => $santri->id,
                'status'   => 'HAID',
                'badge'    => 'status-badge-haid',
                'wa_emoji' => '🌸 - HAID',
            ],
        ]);

        $this->assertDatabaseHas('presensis', [
            'entitas_id'       => $santri->id,
            'status_kehadiran' => 'haid',
        ]);
    }
}
