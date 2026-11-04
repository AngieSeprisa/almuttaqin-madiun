<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\MasterDevice;
use App\Models\MasterSesi;
use App\Models\Presensi;
use App\Models\PresensiGuru;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapGuruTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Lembaga $lembaga;
    protected Lembaga $yayasan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->yayasan = Lembaga::firstOrCreate(
            ['kode' => 'yayasan'],
            ['nama' => 'Yayasan Pondok Pesantren Muttaqin', 'singkatan' => 'Yayasan', 'kategori' => 'yayasan']
        );

        $this->lembaga = Lembaga::firstOrCreate(
            ['kode' => 'SMP'],
            ['nama' => 'SMP Al Muttaqin', 'singkatan' => 'SMP', 'kategori' => 'sekolah']
        );

        $this->admin = User::factory()->create([
            'email'              => 'admin.guru.test@almuttaqin.id',
            'role'               => 'yayasan',
            'is_active'          => true,
            'primary_lembaga_id' => $this->lembaga->id,
        ]);
    }

    public function test_halaman_rekap_presensi_guru_dapat_diakses_dan_menampilkan_header_harian(): void
    {
        $today = Carbon::today();

        // Buat guru dan data presensi hari ini
        $guru = User::factory()->create([
            'name'               => 'Adi Kara Prabowo, S. Pd., Gr.',
            'primary_lembaga_id' => $this->lembaga->id,
        ]);

        PresensiGuru::create([
            'user_id'    => $guru->id,
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
            'jam_masuk'  => '07:10:10',
            'status'     => 'hadir',
        ]);

        $response = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'tanggal' => $today->toDateString(),
        ]));

        $response->assertStatus(200);

        // Header presensi harian sesuai Gambar 3
        $response->assertSee('HEADER PRESENSI HARIAN');
        $response->assertSee('Tepat Waktu');
        $response->assertSee('Total Guru');

        // Kolom tabel sesuai Gambar 3
        $response->assertSee('NAMA GURU');
        $response->assertSee('JAM MASUK');
        $response->assertSee('JAM PULANG');
        $response->assertSee('STATUS KEHADIRAN');
        $response->assertSee('AKSI');

        // Data guru
        $response->assertSee('Adi Kara Prabowo, S. Pd., Gr.');
        $response->assertSee('07:10:10 WIB');
        $response->assertSee('Tidak Absen Pulang');
    }

    public function test_rfid_tap_di_tabel_presensis_otomatis_masuk_ke_rekap_guru(): void
    {
        $today = Carbon::today();

        // Buat guru/pegawai yang tap pada alat RFID
        $guru = User::factory()->create([
            'name'               => 'Angie Seprisa Pamungkas',
            'email'              => 'angie.test@almuttaqin.id',
            'rfid_uid'           => '2819653521',
            'jabatan'            => 'Operator SMP',
            'primary_lembaga_id' => $this->yayasan->id,
        ]);

        $device = MasterDevice::create([
            'device_id'     => 'kantor smp',
            'nama_lokasi'   => 'Kantor Guru SMP',
            'kategori_area' => 'sekolah',
            'status_aktif'  => true,
        ]);

        $sesi = MasterSesi::create([
            'kode_sesi'        => 'SESI_PAGI_TEST',
            'nama_sesi'        => 'Presensi Guru Pagi',
            'jam_mulai'        => '06:00:00',
            'jam_selesai'      => '23:00:00',
            'kategori'         => 'pegawai',
            'device_id_target' => $device->device_id,
        ]);

        // Simulasikan tap di tabel presensis (seperti yang dicatat oleh ESP32 / dashboard presensi)
        Presensi::create([
            'device_id'        => 'kantor smp',
            'sesi_id'          => $sesi->id,
            'tipe_entitas'     => 'guru_pegawai',
            'entitas_id'       => $guru->id,
            'rfid_uid'         => '2819653521',
            'waktu_tap'        => $today->copy()->setTime(21, 51, 34),
            'status_kehadiran' => 'terlambat',
        ]);

        // Akses halaman rekap presensi guru
        $response = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'tanggal' => $today->toDateString(),
        ]));

        $response->assertStatus(200);

        // Pastikan otomatis tersinkronisasi ke database presensi_gurus
        $this->assertDatabaseHas('presensi_gurus', [
            'user_id' => $guru->id,
            'status'  => 'terlambat',
        ]);

        // Pastikan nama guru dan statusnya tampil di halaman rekap
        $response->assertSee('Angie Seprisa Pamungkas');
        $response->assertSee('21:51:34 WIB');
        $response->assertSee('Terlambat');
    }

    public function test_guru_yayasan_yang_mengajar_di_smp_tampil_pada_rekap_smp(): void
    {
        $today = Carbon::today();

        // Guru dengan primary lembaga Yayasan tapi memiliki peran/jabatan di SMP
        $guru = User::factory()->create([
            'name'               => 'Guru Afiliasi SMP',
            'email'              => 'guru.afiliasi@almuttaqin.id',
            'jabatan'            => 'Guru Informatika SMP',
            'primary_lembaga_id' => $this->yayasan->id,
        ]);

        // Presensi tercatat dengan ID Yayasan
        PresensiGuru::create([
            'user_id'    => $guru->id,
            'lembaga_id' => $this->yayasan->id,
            'tanggal'    => $today->toDateString(),
            'jam_masuk'  => '07:15:00',
            'status'     => 'hadir',
        ]);

        // Admin mengakses rekap presensi khusus lembaga SMP
        $response = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Guru Afiliasi SMP');
    }

    public function test_pilihan_semua_lembaga_menampilkan_seluruh_presensi(): void
    {
        $today = Carbon::today();

        $guru1 = User::factory()->create(['name' => 'Guru SMP 1', 'primary_lembaga_id' => $this->lembaga->id]);
        $guru2 = User::factory()->create(['name' => 'Guru Yayasan 1', 'primary_lembaga_id' => $this->yayasan->id]);

        PresensiGuru::create([
            'user_id'    => $guru1->id,
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
            'jam_masuk'  => '07:00:00',
            'status'     => 'hadir',
        ]);

        PresensiGuru::create([
            'user_id'    => $guru2->id,
            'lembaga_id' => $this->yayasan->id,
            'tanggal'    => $today->toDateString(),
            'jam_masuk'  => '07:05:00',
            'status'     => 'hadir',
        ]);

        // Akses dengan lembaga_id="" (Semua Lembaga)
        $response = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'lembaga_id' => '',
            'tanggal'    => $today->toDateString(),
        ]));

        $response->assertStatus(200);
        $response->assertSee('Guru SMP 1');
        $response->assertSee('Guru Yayasan 1');
    }
}
