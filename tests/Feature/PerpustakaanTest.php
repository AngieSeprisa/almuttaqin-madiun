<?php

namespace Tests\Feature;

use App\Models\PerpustakaanBuku;
use App\Models\PerpustakaanPeminjaman;
use App\Models\User;
use Database\Seeders\LembagaAndUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerpustakaanTest extends TestCase
{
    use RefreshDatabase;

    private function actingAdmin(): User
    {
        $this->seed(LembagaAndUserSeeder::class);
        return User::where('email', 'yayasan@almuttaqin.id')->firstOrFail();
    }

    public function test_daftar_buku_and_tambah_buku_flow(): void
    {
        $user = $this->actingAdmin();

        // 1. Halaman Daftar Buku menampilkan tombol Tambah Buku ke halaman baru
        $resIndex = $this->actingAs($user)->get(route('perpustakaan.buku.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Daftar Buku');
        $resIndex->assertSee(route('perpustakaan.buku.create'));

        // 2. Halaman Tambah Buku menampilkan scanner barcode, input nama buku, dan kode buku otomatis
        $resCreate = $this->actingAs($user)->get(route('perpustakaan.buku.create'));
        $resCreate->assertStatus(200);
        $resCreate->assertSee('Scan Barcode Buku');
        $resCreate->assertSee('BK-0001');

        // 3. Endpoint generate kode buku otomatis
        $resKode = $this->actingAs($user)->getJson(route('perpustakaan.buku.generate-kode'));
        $resKode->assertStatus(200)->assertJson(['kode_buku' => 'BK-0001']);

        // 4. Simpan buku baru dari hasil scan barcode (barcode otomatis menjadi kode_buku)
        $resStore = $this->actingAs($user)->post(route('perpustakaan.buku.store'), [
            'barcode'    => '9786020332956',
            'judul_buku' => 'Riyadhus Shalihin Jilid 1',
            'kode_buku'  => '9786020332956',
            'stok_total' => 3,
        ]);

        $resStore->assertRedirect(route('perpustakaan.buku.index'));
        $this->assertDatabaseHas('perpustakaan_bukus', [
            'kode_buku'     => '9786020332956',
            'barcode'       => '9786020332956',
            'judul_buku'    => 'Riyadhus Shalihin Jilid 1',
            'stok_total'    => 3,
            'stok_tersedia' => 3,
        ]);

        // 5. Simpan buku kedua tanpa barcode akan otomatis dibuatkan kode BK-0002
        $resStoreAuto = $this->actingAs($user)->post(route('perpustakaan.buku.store'), [
            'barcode'    => '',
            'judul_buku' => 'Bulughul Maram',
            'kode_buku'  => '',
            'stok_total' => 2,
        ]);

        $resStoreAuto->assertRedirect(route('perpustakaan.buku.index'));
        $this->assertDatabaseHas('perpustakaan_bukus', [
            'kode_buku'  => 'BK-0002',
            'judul_buku' => 'Bulughul Maram',
        ]);
    }

    public function test_pinjam_buku_and_kembalikan_flow(): void
    {
        $user = $this->actingAdmin();

        $buku = PerpustakaanBuku::create([
            'kode_buku'     => 'BK-0001',
            'barcode'       => '9781234567890',
            'judul_buku'    => 'Tafsir Jalalain',
            'penulis'       => 'Imam As-Suyuthi',
            'stok_total'    => 2,
            'stok_tersedia' => 2,
        ]);

        // 1. Halaman Pinjam Buku menampilkan tombol pindah halaman Pinjam Buku
        $resPinjamIndex = $this->actingAs($user)->get(route('perpustakaan.pinjam.index'));
        $resPinjamIndex->assertStatus(200);
        $resPinjamIndex->assertSee('Pinjam Buku');
        $resPinjamIndex->assertSee(route('perpustakaan.pinjam.create'));

        // 2. Halaman baru form Pinjam Buku: Menampilkan nama buku, nama pengarang, dan stok (tanpa kode buku)
        $resPinjamCreate = $this->actingAs($user)->get(route('perpustakaan.pinjam.create'));
        $resPinjamCreate->assertStatus(200);
        $resPinjamCreate->assertSee('Formulir Peminjaman Buku');
        $resPinjamCreate->assertSee('Tafsir Jalalain — Imam As-Suyuthi — Stok: 2');
        $resPinjamCreate->assertDontSee('[BK-0001]');

        // 3. Simpan peminjaman buku
        $resStorePinjam = $this->actingAs($user)->post(route('perpustakaan.pinjam.store'), [
            'perpustakaan_buku_id' => $buku->id,
            'nama_peminjam'        => 'Ahmad Fauzi',
            'kelas_kamar'          => 'Kelas 8A • Kamar Al-Fatih',
            'jumlah'               => 1,
            'tanggal_pinjam'       => now()->toDateString(),
            'tanggal_jatuh_tempo'  => now()->addDays(7)->toDateString(),
            'catatan'              => 'Dipinjam untuk kajian',
        ]);

        $resStorePinjam->assertRedirect(route('perpustakaan.pinjam.index'));
        $this->assertDatabaseHas('perpustakaan_peminjamans', [
            'perpustakaan_buku_id' => $buku->id,
            'nama_peminjam'        => 'Ahmad Fauzi',
            'status'               => 'dipinjam',
        ]);

        $buku->refresh();
        $this->assertSame(1, $buku->stok_tersedia);

        // 4. Kembalikan buku
        $pinjam = PerpustakaanPeminjaman::firstOrFail();
        $resKembali = $this->actingAs($user)->patch(route('perpustakaan.pinjam.kembali', $pinjam->id));
        $resKembali->assertRedirect(route('perpustakaan.pinjam.index'));

        $pinjam->refresh();
        $buku->refresh();
        $this->assertSame('dikembalikan', $pinjam->status);
        $this->assertSame(2, $buku->stok_tersedia);
    }
}

