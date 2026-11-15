<?php

namespace App\Http\Controllers;

use App\Models\PerpustakaanBuku;
use App\Models\PerpustakaanPeminjaman;
use App\Models\Siswa;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PerpustakaanController extends Controller
{
    /**
     * 1. SUBMENU DAFTAR BUKU (INDEX)
     */
    public function bukuIndex(Request $request): View
    {
        $query = PerpustakaanBuku::query()->withCount([
            'peminjamans as dipinjam_count' => function ($q) {
                $q->where('status', 'dipinjam');
            },
        ]);

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('judul_buku', 'like', "%{$s}%")
                  ->orWhere('kode_buku', 'like', "%{$s}%")
                  ->orWhere('barcode', 'like', "%{$s}%")
                  ->orWhere('penulis', 'like', "%{$s}%")
                  ->orWhere('penerbit', 'like', "%{$s}%")
                  ->orWhere('kategori', 'like', "%{$s}%")
                  ->orWhere('rak', 'like', "%{$s}%");
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->input('kategori'));
        }

        if ($request->filled('ketersediaan')) {
            if ($request->input('ketersediaan') === 'tersedia') {
                $query->where('stok_tersedia', '>', 0);
            } elseif ($request->input('ketersediaan') === 'habis') {
                $query->where('stok_tersedia', '<=', 0);
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $bukus = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $totalJudul = PerpustakaanBuku::count();
        $totalEksemplar = (int) PerpustakaanBuku::sum('stok_total');
        $totalTersedia = (int) PerpustakaanBuku::sum('stok_tersedia');
        $totalDipinjam = (int) PerpustakaanPeminjaman::where('status', 'dipinjam')->sum('jumlah');

        $kategoriList = PerpustakaanBuku::whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->orderBy('kategori')
            ->pluck('kategori');

        return view('perpustakaan.buku_index', compact(
            'bukus',
            'totalJudul',
            'totalEksemplar',
            'totalTersedia',
            'totalDipinjam',
            'kategoriList',
            'perPage'
        ));
    }

    /**
     * HALAMAN BARU: TAMBAH BUKU (Scan Barcode HP, Nama Buku, Kode Buku Otomatis)
     */
    public function createBuku(): View
    {
        $nextKodeBuku = PerpustakaanBuku::generateKodeBuku();

        return view('perpustakaan.buku_create', compact('nextKodeBuku'));
    }

    /**
     * Endpoint JSON untuk membuat kode buku otomatis
     */
    public function generateKodeBuku(): JsonResponse
    {
        return response()->json([
            'kode_buku' => PerpustakaanBuku::generateKodeBuku(),
        ]);
    }

    /**
     * SIMPAN BUKU BARU
     */
    public function storeBuku(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'barcode'    => ['nullable', 'string', 'max:100'],
            'judul_buku' => ['required', 'string', 'max:255'],
            'kode_buku'  => ['nullable', 'string', 'max:50'],
            'kategori'   => ['nullable', 'string', 'max:100'],
            'penulis'    => ['nullable', 'string', 'max:255'],
            'penerbit'   => ['nullable', 'string', 'max:255'],
            'stok_total' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'rak'        => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], [
            'judul_buku.required' => 'Nama / judul buku wajib diisi.',
        ]);

        $kodeBuku = trim((string) ($validated['kode_buku'] ?? ''));
        if ($kodeBuku === '' || PerpustakaanBuku::where('kode_buku', $kodeBuku)->exists()) {
            $kodeBuku = PerpustakaanBuku::generateKodeBuku();
        }

        $stokTotal = max(1, (int) ($validated['stok_total'] ?? 1));

        $buku = PerpustakaanBuku::create([
            'kode_buku'     => $kodeBuku,
            'barcode'       => !empty($validated['barcode']) ? trim($validated['barcode']) : null,
            'judul_buku'    => trim($validated['judul_buku']),
            'kategori'      => !empty($validated['kategori']) ? trim($validated['kategori']) : null,
            'penulis'       => !empty($validated['penulis']) ? trim($validated['penulis']) : null,
            'penerbit'      => !empty($validated['penerbit']) ? trim($validated['penerbit']) : null,
            'stok_total'    => $stokTotal,
            'stok_tersedia' => $stokTotal,
            'rak'           => !empty($validated['rak']) ? trim($validated['rak']) : null,
            'keterangan'    => !empty($validated['keterangan']) ? trim($validated['keterangan']) : null,
            'created_by'    => Auth::id(),
        ]);

        return redirect()
            ->route('perpustakaan.buku.index')
            ->with('success', "Buku \"{$buku->judul_buku}\" ({$buku->kode_buku}) berhasil ditambahkan.");
    }

    /**
     * UPDATE DATA BUKU
     */
    public function updateBuku(Request $request, PerpustakaanBuku $buku): RedirectResponse
    {
        $validated = $request->validate([
            'barcode'    => ['nullable', 'string', 'max:100'],
            'judul_buku' => ['required', 'string', 'max:255'],
            'kode_buku'  => ['required', 'string', 'max:50', 'unique:perpustakaan_bukus,kode_buku,' . $buku->id],
            'kategori'   => ['nullable', 'string', 'max:100'],
            'penulis'    => ['nullable', 'string', 'max:255'],
            'penerbit'   => ['nullable', 'string', 'max:255'],
            'stok_total' => ['required', 'integer', 'min:1', 'max:10000'],
            'rak'        => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ], [
            'judul_buku.required' => 'Nama / judul buku wajib diisi.',
            'kode_buku.required'  => 'Kode buku wajib diisi.',
            'kode_buku.unique'    => 'Kode buku sudah digunakan oleh buku lain.',
        ]);

        $buku->update([
            'kode_buku'  => trim($validated['kode_buku']),
            'barcode'    => !empty($validated['barcode']) ? trim($validated['barcode']) : null,
            'judul_buku' => trim($validated['judul_buku']),
            'kategori'   => !empty($validated['kategori']) ? trim($validated['kategori']) : null,
            'penulis'    => !empty($validated['penulis']) ? trim($validated['penulis']) : null,
            'penerbit'   => !empty($validated['penerbit']) ? trim($validated['penerbit']) : null,
            'stok_total' => (int) $validated['stok_total'],
            'rak'        => !empty($validated['rak']) ? trim($validated['rak']) : null,
            'keterangan' => !empty($validated['keterangan']) ? trim($validated['keterangan']) : null,
        ]);

        $buku->syncStokTersedia();

        return redirect()
            ->route('perpustakaan.buku.index')
            ->with('success', "Data buku \"{$buku->judul_buku}\" berhasil diperbarui.");
    }

    /**
     * HAPUS DATA BUKU
     */
    public function destroyBuku(PerpustakaanBuku $buku): RedirectResponse
    {
        $judul = $buku->judul_buku;
        $buku->delete();

        return redirect()
            ->route('perpustakaan.buku.index')
            ->with('success', "Buku \"{$judul}\" berhasil dihapus dari daftar.");
    }

    /**
     * 2. SUBMENU PINJAM BUKU (INDEX)
     */
    public function pinjamIndex(Request $request): View
    {
        $query = PerpustakaanPeminjaman::with(['buku', 'siswa']);

        if ($request->filled('search')) {
            $s = trim((string) $request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('nama_peminjam', 'like', "%{$s}%")
                  ->orWhere('kode_pinjam', 'like', "%{$s}%")
                  ->orWhere('kelas_kamar', 'like', "%{$s}%")
                  ->orWhereHas('buku', function ($bq) use ($s) {
                      $bq->where('judul_buku', 'like', "%{$s}%")
                         ->orWhere('kode_buku', 'like', "%{$s}%")
                         ->orWhere('barcode', 'like', "%{$s}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $statusFilter = $request->input('status');
            if ($statusFilter === 'dikembalikan') {
                $query->where('status', 'dikembalikan');
            } elseif ($statusFilter === 'terlambat') {
                $query->where('status', 'dipinjam')
                      ->whereNotNull('tanggal_jatuh_tempo')
                      ->whereDate('tanggal_jatuh_tempo', '<', Carbon::today());
            } elseif ($statusFilter === 'dipinjam') {
                $query->where('status', 'dipinjam');
            }
        }

        $perPage = (int) $request->input('per_page', 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) {
            $perPage = 15;
        }

        $peminjamans = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        $totalTransaksi = PerpustakaanPeminjaman::count();
        $sedangDipinjam = PerpustakaanPeminjaman::where('status', 'dipinjam')->count();
        $sudahKembali = PerpustakaanPeminjaman::where('status', 'dikembalikan')->count();
        $terlambatCount = PerpustakaanPeminjaman::where('status', 'dipinjam')
            ->whereNotNull('tanggal_jatuh_tempo')
            ->whereDate('tanggal_jatuh_tempo', '<', Carbon::today())
            ->count();

        return view('perpustakaan.pinjam_index', compact(
            'peminjamans',
            'totalTransaksi',
            'sedangDipinjam',
            'sudahKembali',
            'terlambatCount',
            'perPage'
        ));
    }

    /**
     * HALAMAN BARU: FORM PINJAM BUKU
     */
    public function createPinjam(Request $request): View
    {
        $bukuList = PerpustakaanBuku::orderBy('judul_buku')->get();
        $daftarSiswa = Siswa::orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'kelas', 'kamar']);
        $today = Carbon::today()->toDateString();
        $defaultJatuhTempo = Carbon::today()->addDays(7)->toDateString();
        $selectedBukuId = $request->input('buku_id');

        return view('perpustakaan.pinjam_create', compact(
            'bukuList',
            'daftarSiswa',
            'today',
            'defaultJatuhTempo',
            'selectedBukuId'
        ));
    }

    /**
     * SIMPAN TRANSAKSI PEMINJAMAN BUKU
     */
    public function storePinjam(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'perpustakaan_buku_id' => ['required', 'exists:perpustakaan_bukus,id'],
            'siswa_id'             => ['nullable', 'exists:siswas,id'],
            'nama_peminjam'        => ['nullable', 'string', 'max:255'],
            'kelas_kamar'          => ['nullable', 'string', 'max:120'],
            'jumlah'               => ['nullable', 'integer', 'min:1', 'max:100'],
            'tanggal_pinjam'       => ['required', 'date'],
            'tanggal_jatuh_tempo'  => ['nullable', 'date', 'after_or_equal:tanggal_pinjam'],
            'catatan'              => ['nullable', 'string', 'max:1000'],
        ], [
            'perpustakaan_buku_id.required'       => 'Silakan pilih buku yang akan dipinjam.',
            'perpustakaan_buku_id.exists'         => 'Buku yang dipilih tidak ditemukan.',
            'tanggal_pinjam.required'             => 'Tanggal pinjam wajib diisi.',
            'tanggal_jatuh_tempo.after_or_equal'  => 'Tanggal estimasi kembali tidak boleh sebelum tanggal pinjam.',
        ]);

        $buku = PerpustakaanBuku::findOrFail($validated['perpustakaan_buku_id']);
        $jumlah = max(1, (int) ($validated['jumlah'] ?? 1));

        if ($buku->stok_tersedia < $jumlah) {
            return back()
                ->withErrors(['perpustakaan_buku_id' => "Stok buku \"{$buku->judul_buku}\" tidak mencukupi (tersedia: {$buku->stok_tersedia})."])
                ->withInput();
        }

        $siswa = !empty($validated['siswa_id']) ? Siswa::find($validated['siswa_id']) : null;
        $namaPeminjam = trim((string) ($validated['nama_peminjam'] ?? ''));
        $kelasKamar = trim((string) ($validated['kelas_kamar'] ?? ''));

        if ($siswa) {
            if ($namaPeminjam === '') {
                $namaPeminjam = $siswa->nama_lengkap;
            }
            if ($kelasKamar === '') {
                $parts = array_filter([$siswa->kelas, $siswa->kamar]);
                $kelasKamar = implode(' • ', $parts);
            }
        }

        if ($namaPeminjam === '') {
            return back()
                ->withErrors(['nama_peminjam' => 'Nama peminjam wajib diisi atau dipilih dari daftar santri/siswa.'])
                ->withInput();
        }

        $peminjaman = PerpustakaanPeminjaman::create([
            'kode_pinjam'          => PerpustakaanPeminjaman::generateKodePinjam(),
            'perpustakaan_buku_id' => $buku->id,
            'siswa_id'             => $siswa?->id,
            'nama_peminjam'        => $namaPeminjam,
            'kelas_kamar'          => $kelasKamar !== '' ? $kelasKamar : null,
            'jumlah'               => $jumlah,
            'tanggal_pinjam'       => $validated['tanggal_pinjam'],
            'tanggal_jatuh_tempo'  => $validated['tanggal_jatuh_tempo'] ?? null,
            'status'               => 'dipinjam',
            'catatan'              => !empty($validated['catatan']) ? trim($validated['catatan']) : null,
            'created_by'           => Auth::id(),
        ]);

        $buku->syncStokTersedia();

        return redirect()
            ->route('perpustakaan.pinjam.index')
            ->with('success', "Peminjaman buku \"{$buku->judul_buku}\" oleh {$peminjaman->nama_peminjam} berhasil disimpan.");
    }

    /**
     * TANDAI BUKU SUDAH DIKEMBALIKAN
     */
    public function kembalikanPinjam(PerpustakaanPeminjaman $pinjam): RedirectResponse
    {
        if ($pinjam->status !== 'dikembalikan') {
            $pinjam->update([
                'status'          => 'dikembalikan',
                'tanggal_kembali' => Carbon::today()->toDateString(),
            ]);

            if ($pinjam->buku) {
                $pinjam->buku->syncStokTersedia();
            }
        }

        return redirect()
            ->route('perpustakaan.pinjam.index')
            ->with('success', "Buku \"{$pinjam->buku?->judul_buku}\" yang dipinjam oleh {$pinjam->nama_peminjam} telah ditandai sudah dikembalikan.");
    }

    /**
     * UPDATE DATA PEMINJAMAN BUKU
     */
    public function updatePinjam(Request $request, PerpustakaanPeminjaman $pinjam): RedirectResponse
    {
        $validated = $request->validate([
            'nama_peminjam'       => ['required', 'string', 'max:255'],
            'kelas_kamar'         => ['nullable', 'string', 'max:120'],
            'tanggal_pinjam'      => ['required', 'date'],
            'tanggal_jatuh_tempo' => ['nullable', 'date', 'after_or_equal:tanggal_pinjam'],
            'status'              => ['required', 'in:dipinjam,dikembalikan'],
            'catatan'             => ['nullable', 'string', 'max:1000'],
        ]);

        $tanggalKembali = $pinjam->tanggal_kembali;
        if ($validated['status'] === 'dikembalikan' && !$tanggalKembali) {
            $tanggalKembali = Carbon::today()->toDateString();
        } elseif ($validated['status'] === 'dipinjam') {
            $tanggalKembali = null;
        }

        $pinjam->update([
            'nama_peminjam'       => trim($validated['nama_peminjam']),
            'kelas_kamar'         => !empty($validated['kelas_kamar']) ? trim($validated['kelas_kamar']) : null,
            'tanggal_pinjam'      => $validated['tanggal_pinjam'],
            'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'] ?? null,
            'tanggal_kembali'     => $tanggalKembali,
            'status'              => $validated['status'],
            'catatan'             => !empty($validated['catatan']) ? trim($validated['catatan']) : null,
        ]);

        if ($pinjam->buku) {
            $pinjam->buku->syncStokTersedia();
        }

        return redirect()
            ->route('perpustakaan.pinjam.index')
            ->with('success', "Data peminjaman {$pinjam->kode_pinjam} berhasil diperbarui.");
    }

    /**
     * HAPUS DATA PEMINJAMAN BUKU
     */
    public function destroyPinjam(PerpustakaanPeminjaman $pinjam): RedirectResponse
    {
        $buku = $pinjam->buku;
        $nama = $pinjam->nama_peminjam;
        $pinjam->delete();

        if ($buku) {
            $buku->syncStokTersedia();
        }

        return redirect()
            ->route('perpustakaan.pinjam.index')
            ->with('success', "Data peminjaman atas nama {$nama} berhasil dihapus.");
    }
}

