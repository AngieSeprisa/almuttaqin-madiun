<?php

namespace Tests\Feature;

use App\Models\Lembaga;
use App\Models\Pelanggaran;
use App\Models\PondokCapaianMateri;
use App\Models\PondokMateri;
use App\Models\Siswa;
use App\Models\User;
use App\Models\UserPermission;
use App\Support\MushafMadinah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CapaianMateriDanPelanggaranTest extends TestCase
{
    use RefreshDatabase;

    protected Lembaga $yayasan;
    protected Lembaga $smp;
    protected Lembaga $sdit;
    protected User $yayasanUser;
    protected User $adminSdit;
    protected Siswa $siswaSmp;
    protected Siswa $siswaSdit;

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
            'name' => 'Ketua Yayasan', 'email' => 'yayasan.cm@almuttaqin.id', 'password' => $pwd,
            'role' => 'yayasan', 'primary_lembaga_id' => $this->yayasan->id, 'is_active' => true,
        ]);
        $this->yayasanUser->lembagas()->sync([$this->yayasan->id, $this->smp->id, $this->sdit->id]);

        $this->adminSdit = User::create([
            'name' => 'Admin SDIT', 'email' => 'admin.sdit.cm@almuttaqin.id', 'password' => $pwd,
            'role' => 'admin_lembaga', 'primary_lembaga_id' => $this->sdit->id, 'is_active' => true,
        ]);
        $this->adminSdit->lembagas()->sync([$this->sdit->id]);
        foreach (['capaian_materi', 'pelanggaran_input', 'pelanggaran_rekap'] as $menuCode) {
            UserPermission::updateOrCreate(
                ['user_id' => $this->adminSdit->id, 'menu_code' => $menuCode],
                ['can_view' => true, 'can_create' => true, 'can_edit' => true, 'can_delete' => true]
            );
        }

        $this->siswaSmp = Siswa::create([
            'nama_lengkap' => 'Ahmad Fauzan',
            'jenjang'      => 'SMP',
            'kelas'        => '7A',
            'kamar'        => 'Abu Bakar',
            'status'       => 'aktif',
        ]);

        $this->siswaSdit = Siswa::create([
            'nama_lengkap' => 'Zaid Al Fatih',
            'jenjang'      => 'SD IT',
            'kelas'        => '3B',
            'kamar'        => 'Reguler',
            'status'       => 'aktif',
        ]);
    }

    public function test_pemetaan_30_juz_mushaf_madinah_tepat_604_halaman(): void
    {
        $juzList = MushafMadinah::daftarJuz();

        $this->assertCount(30, $juzList);
        $this->assertSame(1, $juzList[1]['halaman_awal']);
        $this->assertSame(21, $juzList[1]['halaman_akhir']);
        $this->assertSame(341, $juzList[17]['halaman_akhir']);
        $this->assertSame(342, $juzList[18]['halaman_awal']);
        $this->assertSame(582, $juzList[30]['halaman_awal']);
        $this->assertSame(604, $juzList[30]['halaman_akhir']);

        $total = array_sum(array_column($juzList, 'jumlah_halaman'));
        $this->assertSame(604, $total);
    }

    public function test_capaian_materi_al_quran_per_juz_dan_al_hadist_terpisah_dengan_matriks_lingkaran(): void
    {
        $kitabSholah = PondokMateri::create([
            'kode_materi'    => 'HAD-01',
            'nama_materi'    => 'K. Sholah',
            'kategori'       => PondokMateri::KATEGORI_AL_HADIST,
            'halaman_awal'   => 1,
            'halaman_akhir'  => 20,
            'jumlah_halaman' => 20,
            'jumlah_baris'   => 16,
            'is_active'      => true,
        ]);

        $kitabNawawi = PondokMateri::create([
            'kode_materi'    => 'HAD-02',
            'nama_materi'    => 'K. Nawawi',
            'kategori'       => PondokMateri::KATEGORI_AL_HADIST,
            'halaman_awal'   => 1,
            'halaman_akhir'  => 30,
            'jumlah_halaman' => 30,
            'jumlah_baris'   => 16,
            'is_active'      => true,
        ]);

        // 1. Halaman daftar siswa memuat kolom Al-Qur'an & Al-Hadist terpisah + tombol Lihat & Edit
        $this->actingAs($this->yayasanUser)
            ->get(route('pondok.capaian.index'))
            ->assertOk()
            ->assertSee('Capaian Materi')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('Al-Qur\'an', false)
            ->assertSee('Al-Hadist')
            ->assertSee('Lihat')
            ->assertSee('Edit');

        // 2. Halaman detail siswa memuat Juz 1 s/d Juz 30 & kitab Al-Hadist terdaftar dengan matriks lingkaran
        $this->actingAs($this->yayasanUser)
            ->get(route('pondok.capaian.show', ['siswa' => $this->siswaSmp->id, 'mode' => 'edit']))
            ->assertOk()
            ->assertSee('Juz 1')
            ->assertSee('Juz 30')
            ->assertSee('K. Sholah')
            ->assertSee('K. Nawawi')
            ->assertSee('cm-circle');

        // 3. Klik halaman 1 dan 2 pada Juz 1 Al-Qur'an -> persentase Al-Qur'an naik, Al-Hadist tetap 0%
        $res1 = $this->actingAs($this->yayasanUser)
            ->postJson(route('pondok.capaian.toggle', $this->siswaSmp->id), [
                'kategori' => 'al_quran',
                'juz'      => 1,
                'action'   => 'toggle_page',
                'halaman'  => 1,
            ]);
        $res1->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('item.selesai', 1)
            ->assertJsonPath('item.total', 21)
            ->assertJsonPath('quran.selesai', 1)
            ->assertJsonPath('quran.total', 604)
            ->assertJsonPath('hadist.selesai', 0);

        // Toggle seluruh Juz 1 (21 halaman)
        $resJuzAll = $this->actingAs($this->yayasanUser)
            ->postJson(route('pondok.capaian.toggle', $this->siswaSmp->id), [
                'kategori' => 'al_quran',
                'juz'      => 1,
                'action'   => 'select_all',
            ]);
        $resJuzAll->assertOk()
            ->assertJsonPath('item.selesai', 21)
            ->assertJsonPath('item.persen_label', '100%')
            ->assertJsonPath('quran.selesai', 21)
            ->assertJsonPath('hadist.selesai', 0);

        // 4. Klik halaman pada K. Sholah (total Al-Hadist = 20 + 30 = 50 halaman)
        // Tandai semua K. Sholah (20 halaman) -> 20/50 = 40% Al-Hadist, Al-Qur'an tetap 21 halaman
        $resHadistAll = $this->actingAs($this->yayasanUser)
            ->postJson(route('pondok.capaian.toggle', $this->siswaSmp->id), [
                'kategori'         => 'al_hadist',
                'pondok_materi_id' => $kitabSholah->id,
                'action'           => 'select_all',
            ]);
        $resHadistAll->assertOk()
            ->assertJsonPath('item.selesai', 20)
            ->assertJsonPath('item.persen_label', '100%')
            ->assertJsonPath('hadist.selesai', 20)
            ->assertJsonPath('hadist.total', 50)
            ->assertJsonPath('hadist.persen_label', '40%')
            ->assertJsonPath('quran.selesai', 21);

        // Uncheck 1 halaman K. Sholah (halaman 5) -> sisa 19/50 = 38%
        $resUncheck = $this->actingAs($this->yayasanUser)
            ->postJson(route('pondok.capaian.toggle', $this->siswaSmp->id), [
                'kategori'         => 'al_hadist',
                'pondok_materi_id' => $kitabSholah->id,
                'action'           => 'toggle_page',
                'halaman'          => 5,
            ]);
        $resUncheck->assertOk()
            ->assertJsonPath('item.selesai', 19)
            ->assertJsonPath('hadist.selesai', 19)
            ->assertJsonPath('hadist.persen_label', '38%');

        // Halaman di luar rentang ditolak 422
        $this->actingAs($this->yayasanUser)
            ->postJson(route('pondok.capaian.toggle', $this->siswaSmp->id), [
                'kategori'         => 'al_hadist',
                'pondok_materi_id' => $kitabNawawi->id,
                'action'           => 'toggle_page',
                'halaman'          => 99,
            ])
            ->assertStatus(422);

        // Daftar siswa menampilkan persentase terpisah terbaru
        $this->actingAs($this->yayasanUser)
            ->get(route('pondok.capaian.index'))
            ->assertOk()
            ->assertSee('21 / 604')
            ->assertSee('19 / 50')
            ->assertSee('38%');

        // Isolasi room: admin SD IT tidak bisa mengakses capaian siswa SMP
        $this->actingAs($this->adminSdit)
            ->get(route('pondok.capaian.show', $this->siswaSmp->id))
            ->assertStatus(403);

        $this->actingAs($this->adminSdit)
            ->postJson(route('pondok.capaian.toggle', $this->siswaSmp->id), [
                'kategori' => 'al_quran',
                'juz'      => 1,
                'action'   => 'toggle_page',
                'halaman'  => 1,
            ])
            ->assertStatus(403);
    }

    public function test_menu_pelanggaran_input_kelas_otomatis_rekap_edit_hapus_dan_isolasi_room(): void
    {
        // 1. Halaman Input Pelanggaran dapat diakses dan membawa daftar siswa + kelas otomatis
        $this->actingAs($this->yayasanUser)
            ->get(route('pelanggaran.input'))
            ->assertOk()
            ->assertSee('Input Pelanggaran')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('7A');

        // 2. Simpan pelanggaran -> kelas otomatis diambil dari data siswa
        $this->actingAs($this->yayasanUser)
            ->post(route('pelanggaran.store'), [
                'siswa_id'    => $this->siswaSmp->id,
                'pelanggaran' => 'Terlambat mengikuti jamaah subuh',
            ])
            ->assertRedirect(route('pelanggaran.input'))
            ->assertSessionHas('success');

        $record = Pelanggaran::where('siswa_id', $this->siswaSmp->id)->first();
        $this->assertNotNull($record);
        $this->assertSame('Ahmad Fauzan', $record->nama_siswa);
        $this->assertSame('7A', $record->kelas);
        $this->assertSame('Terlambat mengikuti jamaah subuh', $record->pelanggaran);

        // 3. Rekap pelanggaran menampilkan data dalam tabel + filter kelas & pencarian
        $this->actingAs($this->yayasanUser)
            ->get(route('pelanggaran.rekap'))
            ->assertOk()
            ->assertSee('Rekap Pelanggaran')
            ->assertSee('Ahmad Fauzan')
            ->assertSee('7A')
            ->assertSee('Terlambat mengikuti jamaah subuh');

        $this->actingAs($this->yayasanUser)
            ->get(route('pelanggaran.rekap', ['kelas' => '7A', 'search' => 'jamaah']))
            ->assertOk()
            ->assertSee('Ahmad Fauzan');

        // 4. Edit pelanggaran
        $this->actingAs($this->yayasanUser)
            ->put(route('pelanggaran.update', $record->id), [
                'pelanggaran' => 'Terlambat jamaah subuh dua kali',
            ])
            ->assertRedirect();

        $this->assertSame('Terlambat jamaah subuh dua kali', $record->fresh()->pelanggaran);

        // 5. Isolasi room: admin SD IT hanya bisa input & lihat pelanggaran siswa SD IT
        $this->actingAs($this->adminSdit)
            ->post(route('pelanggaran.store'), [
                'siswa_id'    => $this->siswaSmp->id,
                'pelanggaran' => 'Tidak boleh lintas lembaga',
            ])
            ->assertStatus(403);

        $this->actingAs($this->adminSdit)
            ->post(route('pelanggaran.store'), [
                'siswa_id'    => $this->siswaSdit->id,
                'pelanggaran' => 'Tidak memakai atribut lengkap',
            ])
            ->assertRedirect(route('pelanggaran.input'));

        $this->actingAs($this->adminSdit)
            ->get(route('pelanggaran.rekap'))
            ->assertOk()
            ->assertSee('Zaid Al Fatih')
            ->assertSee('3B')
            ->assertDontSee('Ahmad Fauzan');

        // Admin SD IT tidak bisa menghapus pelanggaran siswa SMP
        $this->actingAs($this->adminSdit)
            ->delete(route('pelanggaran.destroy', $record->id))
            ->assertStatus(403);

        // 6. Yayasan (di room yayasan) menghapus pelanggaran
        $this->actingAs($this->yayasanUser)
            ->withSession(['active_lembaga' => 'yayasan'])
            ->delete(route('pelanggaran.destroy', $record->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('pelanggarans', ['id' => $record->id]);
    }
}
