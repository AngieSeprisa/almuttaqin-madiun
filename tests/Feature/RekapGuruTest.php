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
    protected Lembaga $sma;

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

        $this->sma = Lembaga::firstOrCreate(
            ['kode' => 'SMA'],
            ['nama' => 'SMA Al Muttaqin', 'singkatan' => 'SMA', 'kategori' => 'sekolah']
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
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
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

        // Buat guru/pegawai yang terdaftar di SMP yang melakukan tap pada alat RFID
        $guru = User::factory()->create([
            'name'               => 'Angie Seprisa Pamungkas',
            'email'              => 'angie.test@almuttaqin.id',
            'rfid_uid'           => '2819653521',
            'jabatan'            => 'Operator SMP',
            'primary_lembaga_id' => $this->lembaga->id,
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

        // Simulasikan tap di tabel presensis
        Presensi::create([
            'device_id'        => 'kantor smp',
            'sesi_id'          => $sesi->id,
            'tipe_entitas'     => 'guru_pegawai',
            'entitas_id'       => $guru->id,
            'rfid_uid'         => '2819653521',
            'waktu_tap'        => $today->copy()->setTime(21, 51, 34),
            'status_kehadiran' => 'terlambat',
        ]);

        // Akses halaman rekap presensi guru SMP
        $response = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
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

    public function test_guru_terdaftar_di_smp_hanya_tampil_di_smp_dan_tidak_tampil_di_sma(): void
    {
        $today = Carbon::today();

        // Guru terdaftar di SMP walaupun memiliki akun/akses yayasan
        $guruSmp = User::factory()->create([
            'name'               => 'Angie Seprisa Pamungkas',
            'email'              => 'angie.smp@almuttaqin.id',
            'role'               => 'yayasan',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $this->lembaga->id,
        ]);

        // Guru terdaftar di SMA
        $guruSma = User::factory()->create([
            'name'               => 'Budi SMA Pengajar',
            'email'              => 'budi.sma@almuttaqin.id',
            'role'               => 'guru',
            'is_guru_sekolah'    => true,
            'primary_lembaga_id' => $this->sma->id,
        ]);

        // Presensi kedua guru
        PresensiGuru::create([
            'user_id'    => $guruSmp->id,
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
            'jam_masuk'  => '07:15:00',
            'status'     => 'hadir',
        ]);

        PresensiGuru::create([
            'user_id'    => $guruSma->id,
            'lembaga_id' => $this->sma->id,
            'tanggal'    => $today->toDateString(),
            'jam_masuk'  => '07:20:00',
            'status'     => 'hadir',
        ]);

        // 1. Saat rekap SMP dibuka -> Guru SMP tampil, Guru SMA TIDAK tampil
        $responseSmp = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
        ]));
        $responseSmp->assertStatus(200);
        $responseSmp->assertSee('Angie Seprisa Pamungkas');
        $responseSmp->assertDontSee('Budi SMA Pengajar');

        // 2. Saat rekap SMA dibuka -> Guru SMA tampil, Guru SMP TIDAK tampil
        $responseSma = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'lembaga_id' => $this->sma->id,
            'tanggal'    => $today->toDateString(),
        ]));
        $responseSma->assertStatus(200);
        $responseSma->assertSee('Budi SMA Pengajar');
        $responseSma->assertDontSee('Angie Seprisa Pamungkas');
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

    public function test_guru_yayasan_dengan_jabatan_operator_smp_dan_mapel_informatika_otomatis_masuk_rekap_smp_bukan_sma(): void
    {
        $today = Carbon::today();

        // Mata pelajaran Informatika di SMP
        \App\Models\MataPelajaran::firstOrCreate(
            ['kode' => 'SMP-INF', 'lembaga_id' => $this->lembaga->id],
            ['mata_pelajaran' => 'Informatika', 'tingkat_kelas' => '7,8,9', 'is_active' => true]
        );

        // Kasus persis seperti di live VPS:
        // Akun memiliki role yayasan, primary_lembaga_id SMA atau Yayasan, lembagas berisi SMP dan SMA
        // Namun jabatan = 'Operator SMP' dan di Guru Sekolah terdaftar mapel 'Informatika'
        $angie = User::factory()->create([
            'name'               => 'Angie Seprisa Pamungkas',
            'email'              => 'angieseprisa@almuttaqin.id',
            'role'               => 'yayasan',
            'jabatan'            => 'Operator SMP',
            'is_guru_sekolah'    => true,
            'mata_pelajaran'     => 'Informatika',
            'mapel_status'       => 'disetujui',
            'primary_lembaga_id' => $this->sma->id, // Sebelumnya tersimpan SMA
        ]);
        $angie->lembagas()->sync([$this->yayasan->id, $this->sma->id, $this->lembaga->id]);

        $device = MasterDevice::create([
            'device_id'     => 'ESP32_MAIN',
            'nama_lokasi'   => 'Lobby Utama',
            'kategori_area' => 'umum',
            'status_aktif'  => true,
        ]);

        $sesi = MasterSesi::create([
            'kode_sesi'        => 'SESI_MASUK_PEGAWAI',
            'nama_sesi'        => 'Presensi Pegawai & Guru',
            'jam_mulai'        => '06:45:00',
            'jam_selesai'      => '22:45:00',
            'kategori'         => 'pegawai',
        ]);

        // Simulasikan tap masuk di tabel presensis (seperti di Dashboard Presensi)
        Presensi::create([
            'device_id'        => 'ESP32_MAIN',
            'sesi_id'          => $sesi->id,
            'tipe_entitas'     => 'guru_pegawai',
            'entitas_id'       => $angie->id,
            'rfid_uid'         => '2819653521',
            'waktu_tap'        => $today->copy()->setTime(21, 51, 34),
            'status_kehadiran' => 'terlambat',
        ]);

        // 1. Rekap presensi guru dibuka untuk SMP: Angie HARUS MUNCUL di SMP!
        $responseSmp = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
        ]));
        $responseSmp->assertStatus(200);
        $responseSmp->assertSee('Angie Seprisa Pamungkas');
        $responseSmp->assertSee('21:51:34 WIB');
        $responseSmp->assertSee('Terlambat');

        // 2. Rekap presensi guru dibuka untuk SMA: Angie TIDAK BOLEH MUNCUL di SMA!
        $responseSma = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'lembaga_id' => $this->sma->id,
            'tanggal'    => $today->toDateString(),
        ]));
        $responseSma->assertStatus(200);
        $responseSma->assertDontSee('Angie Seprisa Pamungkas');
    }

    public function test_existing_presensi_guru_yang_salah_lembaga_otomatis_dikoreksi_ke_smp(): void
    {
        $today = Carbon::today();

        \App\Models\MataPelajaran::firstOrCreate(
            ['kode' => 'SMP-INF', 'lembaga_id' => $this->lembaga->id],
            ['mata_pelajaran' => 'Informatika', 'tingkat_kelas' => '7,8,9', 'is_active' => true]
        );

        $angie = User::factory()->create([
            'name'               => 'Angie Seprisa Pamungkas',
            'email'              => 'angieseprisa2@almuttaqin.id',
            'role'               => 'yayasan',
            'jabatan'            => 'Operator SMP',
            'is_guru_sekolah'    => true,
            'mata_pelajaran'     => 'Informatika',
            'mapel_status'       => 'disetujui',
            'primary_lembaga_id' => $this->sma->id,
        ]);
        $angie->lembagas()->sync([$this->yayasan->id, $this->sma->id, $this->lembaga->id]);

        // Simulasikan presensi guru yang TERLANJUR tersimpan di database dengan lembaga SMA
        $presensiLama = PresensiGuru::create([
            'user_id'    => $angie->id,
            'lembaga_id' => $this->sma->id, // Salah tersimpan SMA
            'tanggal'    => $today->toDateString(),
            'jam_masuk'  => '21:51:34',
            'status'     => 'terlambat',
        ]);

        $sesi = MasterSesi::create([
            'kode_sesi'        => 'SESI_MASUK_PEGAWAI_2',
            'nama_sesi'        => 'Presensi Pegawai & Guru 2',
            'jam_mulai'        => '06:45:00',
            'jam_selesai'      => '22:45:00',
            'kategori'         => 'pegawai',
        ]);

        // Log tap di presensis
        Presensi::create([
            'device_id'        => 'ESP32_MAIN',
            'sesi_id'          => $sesi->id,
            'tipe_entitas'     => 'guru_pegawai',
            'entitas_id'       => $angie->id,
            'rfid_uid'         => '2819653521',
            'waktu_tap'        => $today->copy()->setTime(21, 51, 34),
            'status_kehadiran' => 'terlambat',
        ]);

        // Buka rekap presensi SMP
        $responseSmp = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
        ]));
        $responseSmp->assertStatus(200);
        $responseSmp->assertSee('Angie Seprisa Pamungkas');

        // Pastikan di database record sudah dikoreksi ke ID lembaga SMP
        $this->assertEquals($this->lembaga->id, $presensiLama->fresh()->lembaga_id);

        // Buka rekap presensi SMA: Angie TIDAK BOLEH tampil di SMA
        $responseSma = $this->actingAs($this->admin)->get(route('rekap-absensi.guru', [
            'lembaga_id' => $this->sma->id,
            'tanggal'    => $today->toDateString(),
        ]));
        $responseSma->assertStatus(200);
        $responseSma->assertDontSee('Angie Seprisa Pamungkas');
    }

    public function test_akses_rekap_guru_tanpa_parameter_dan_dengan_data_anomali_tetap_berhasil(): void
    {
        $today = Carbon::today();

        $device = MasterDevice::create([
            'device_id'     => 'device_anomali',
            'nama_lokasi'   => 'Kantor Guru Anomali',
            'kategori_area' => 'sekolah',
            'status_aktif'  => true,
        ]);

        $sesi = MasterSesi::create([
            'kode_sesi'        => 'SESI_ANOMALI',
            'nama_sesi'        => 'Presensi Anomali',
            'jam_mulai'        => '06:00:00',
            'jam_selesai'      => '23:00:00',
            'kategori'         => 'pegawai',
            'device_id_target' => $device->device_id,
        ]);

        // 1. Simulasikan tap RFID anomali di tabel presensis (user tidak ditemukan)
        Presensi::create([
            'device_id'        => $device->device_id,
            'sesi_id'          => $sesi->id,
            'tipe_entitas'     => 'guru_pegawai',
            'entitas_id'       => 999999, // user tidak ada
            'rfid_uid'         => '9999999999',
            'waktu_tap'        => $today->copy()->setTime(8, 0, 0),
            'status_kehadiran' => 'hadir',
        ]);

        // 2. Simulasikan presensi guru yang user-nya sudah dihapus
        $dummyUser = User::factory()->create([
            'primary_lembaga_id' => $this->lembaga->id,
        ]);
        $presensi = PresensiGuru::create([
            'user_id'    => $dummyUser->id,
            'lembaga_id' => $this->lembaga->id,
            'tanggal'    => $today->toDateString(),
            'jam_masuk'  => '07:00:00',
            'status'     => 'hadir',
        ]);
        // Hapus paksa user dari tabel users agar menjadi orphaned record
        \DB::table('users')->where('id', $dummyUser->id)->delete();

        // 3. Buka halaman rekap guru persis seperti di browser (tanpa query string apapun)
        $response = $this->actingAs($this->admin)->get(route('rekap-absensi.guru'));

        $response->assertStatus(200);
        $response->assertSee('HEADER PRESENSI HARIAN');
        $response->assertSee('Menampilkan');
    }
}
