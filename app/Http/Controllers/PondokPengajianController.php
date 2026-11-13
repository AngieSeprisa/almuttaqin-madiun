<?php

namespace App\Http\Controllers;

use App\Models\MasterKelasPengajian;
use App\Models\PerizinanSakit;
use App\Models\PerizinanSambang;
use App\Models\PondokJurnalAbsensi;
use App\Models\PondokJurnalMengaji;
use App\Models\PondokMateri;
use App\Models\PondokTargetPengajian;
use App\Models\Siswa;
use App\Models\User;
use App\Support\MushafMadinah;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class PondokPengajianController extends Controller
{
    /* ======================================================================
     * 1. SUBMENU DAFTAR MATERI PONDOK (AL-QUR'AN & AL-HADIST)
     * ====================================================================== */

    public function materiIndex(Request $request)
    {
        $query = PondokMateri::query();

        if ($request->filled('kategori') && in_array($request->kategori, [PondokMateri::KATEGORI_AL_QURAN, PondokMateri::KATEGORI_AL_HADIST], true)) {
            $query->where('kategori', $request->kategori);
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('nama_materi', 'like', "%{$s}%")
                  ->orWhere('kode_materi', 'like', "%{$s}%")
                  ->orWhere('keterangan', 'like', "%{$s}%");
            });
        }

        $materis = $query->orderBy('kategori')->orderBy('id')->get();

        $totalMateri    = PondokMateri::count();
        $totalQuran     = PondokMateri::where('kategori', PondokMateri::KATEGORI_AL_QURAN)->count();
        $totalHadist    = PondokMateri::where('kategori', PondokMateri::KATEGORI_AL_HADIST)->count();
        $paketQuranList = MushafMadinah::daftarPaketQuran();

        return view('pondok.materi', compact(
            'materis',
            'totalMateri',
            'totalQuran',
            'totalHadist',
            'paketQuranList'
        ));
    }

    public function muatStandarMateri()
    {
        $added = PondokMateri::seedStandar();

        if ($added > 0) {
            return redirect()->route('pondok.materi.index')
                ->with('success', "{$added} materi standar pondok berhasil dimuat.");
        }

        return redirect()->route('pondok.materi.index')
            ->with('success', 'Seluruh materi standar pondok sudah tersedia.');
    }

    public function storeMateri(Request $request)
    {
        $validated = $request->validate([
            'kode_materi'    => ['nullable', 'string', 'max:50'],
            'nama_materi'    => ['required', 'string', 'max:150'],
            'kategori'       => ['required', Rule::in([PondokMateri::KATEGORI_AL_QURAN, PondokMateri::KATEGORI_AL_HADIST])],
            'paket_quran'    => ['nullable', 'string', 'max:30'],
            'halaman_awal'   => ['nullable', 'integer', 'min:1', 'max:604'],
            'halaman_akhir'  => ['nullable', 'integer', 'min:1', 'max:9999'],
            'jumlah_halaman' => ['nullable', 'integer', 'min:1'],
            'jumlah_baris'   => ['nullable', 'integer', 'min:0'],
            'keterangan'     => ['nullable', 'string', 'max:1000'],
        ], [
            'nama_materi.required' => 'Nama materi wajib diisi.',
            'kategori.required'    => 'Kategori materi wajib dipilih.',
        ]);

        if ($validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST && ($validated['jumlah_baris'] === null || $validated['jumlah_baris'] === '')) {
            return back()->withInput()->with('error', 'Untuk kategori Al-Hadist, jumlah baris wajib diisi.');
        }

        $paketQuran   = null;
        $halamanAwal  = 1;
        $halamanAkhir = 604;
        $jmlHalaman   = 604;

        if ($validated['kategori'] === PondokMateri::KATEGORI_AL_QURAN) {
            $paketList = MushafMadinah::daftarPaketQuran();
            $reqPaket  = $validated['paket_quran'] ?? null;

            if ($reqPaket && isset($paketList[$reqPaket])) {
                $paketQuran   = $reqPaket;
                $halamanAwal  = $paketList[$reqPaket]['halaman_awal'];
                $halamanAkhir = $paketList[$reqPaket]['halaman_akhir'];
                $jmlHalaman   = !empty($validated['jumlah_halaman'])
                    ? (int) $validated['jumlah_halaman']
                    : $paketList[$reqPaket]['jumlah_halaman'];
            } elseif (!empty($validated['halaman_awal']) && !empty($validated['halaman_akhir'])) {
                $paketQuran   = $reqPaket ?: MushafMadinah::PAKET_KUSTOM;
                $halamanAwal  = max(1, min(604, (int) $validated['halaman_awal']));
                $halamanAkhir = max($halamanAwal, min(604, (int) $validated['halaman_akhir']));
                $jmlHalaman   = !empty($validated['jumlah_halaman'])
                    ? (int) $validated['jumlah_halaman']
                    : ($halamanAkhir - $halamanAwal + 1);
            } else {
                $namaLower = mb_strtolower(trim($validated['nama_materi']));
                if (str_contains($namaLower, 'cepatan')) {
                    $paketQuran   = MushafMadinah::PAKET_CEPATAN;
                    $halamanAwal  = 1;
                    $halamanAkhir = 341;
                    $jmlHalaman   = !empty($validated['jumlah_halaman']) ? (int) $validated['jumlah_halaman'] : 341;
                } elseif (str_contains($namaLower, 'lambatan')) {
                    $paketQuran   = MushafMadinah::PAKET_LAMBATAN;
                    $halamanAwal  = 342;
                    $halamanAkhir = 604;
                    $jmlHalaman   = !empty($validated['jumlah_halaman']) ? (int) $validated['jumlah_halaman'] : 263;
                } else {
                    $paketQuran   = MushafMadinah::PAKET_REGULER;
                    $halamanAwal  = 1;
                    $halamanAkhir = 604;
                    $jmlHalaman   = !empty($validated['jumlah_halaman']) ? (int) $validated['jumlah_halaman'] : MushafMadinah::TOTAL_HALAMAN;
                }
            }
        } else {
            $jmlHalaman   = !empty($validated['jumlah_halaman']) ? (int) $validated['jumlah_halaman'] : 1;
            $halamanAwal  = 1;
            $halamanAkhir = $jmlHalaman;
        }

        $prefix = $validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST ? 'HAD' : 'QUR';
        $kode = !empty($validated['kode_materi'])
            ? strtoupper(trim($validated['kode_materi']))
            : sprintf('%s-%02d', $prefix, PondokMateri::where('kategori', $validated['kategori'])->count() + 1);

        PondokMateri::create([
            'kode_materi'    => $kode,
            'nama_materi'    => trim($validated['nama_materi']),
            'kategori'       => $validated['kategori'],
            'paket_quran'    => $paketQuran,
            'halaman_awal'   => $halamanAwal,
            'halaman_akhir'  => $halamanAkhir,
            'jumlah_halaman' => $jmlHalaman,
            'jumlah_baris'   => $validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST ? (int) ($validated['jumlah_baris'] ?? 0) : null,
            'keterangan'     => $validated['keterangan'] ?? null,
            'is_active'      => true,
        ]);

        return redirect()->route('pondok.materi.index')
            ->with('success', 'Materi pondok "' . trim($validated['nama_materi']) . '" berhasil ditambahkan.');
    }

    public function updateMateri(Request $request, PondokMateri $materi)
    {
        $validated = $request->validate([
            'kode_materi'    => ['nullable', 'string', 'max:50'],
            'nama_materi'    => ['required', 'string', 'max:150'],
            'kategori'       => ['required', Rule::in([PondokMateri::KATEGORI_AL_QURAN, PondokMateri::KATEGORI_AL_HADIST])],
            'paket_quran'    => ['nullable', 'string', 'max:30'],
            'halaman_awal'   => ['nullable', 'integer', 'min:1', 'max:604'],
            'halaman_akhir'  => ['nullable', 'integer', 'min:1', 'max:9999'],
            'jumlah_halaman' => ['nullable', 'integer', 'min:1'],
            'jumlah_baris'   => ['nullable', 'integer', 'min:0'],
            'keterangan'     => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST && ($validated['jumlah_baris'] === null || $validated['jumlah_baris'] === '')) {
            return back()->withInput()->with('error', 'Untuk kategori Al-Hadist, jumlah baris wajib diisi.');
        }

        $paketQuran   = null;
        $halamanAwal  = 1;
        $halamanAkhir = 604;
        $jmlHalaman   = (int) ($validated['jumlah_halaman'] ?? $materi->jumlah_halaman);

        if ($validated['kategori'] === PondokMateri::KATEGORI_AL_QURAN) {
            $paketList = MushafMadinah::daftarPaketQuran();
            $reqPaket  = $validated['paket_quran'] ?? null;

            if ($reqPaket && isset($paketList[$reqPaket])) {
                $paketQuran   = $reqPaket;
                $halamanAwal  = $paketList[$reqPaket]['halaman_awal'];
                $halamanAkhir = $paketList[$reqPaket]['halaman_akhir'];
                $jmlHalaman   = !empty($validated['jumlah_halaman'])
                    ? (int) $validated['jumlah_halaman']
                    : $paketList[$reqPaket]['jumlah_halaman'];
            } elseif (!empty($validated['halaman_awal']) && !empty($validated['halaman_akhir'])) {
                $paketQuran   = $reqPaket ?: MushafMadinah::PAKET_KUSTOM;
                $halamanAwal  = max(1, min(604, (int) $validated['halaman_awal']));
                $halamanAkhir = max($halamanAwal, min(604, (int) $validated['halaman_akhir']));
                $jmlHalaman   = !empty($validated['jumlah_halaman'])
                    ? (int) $validated['jumlah_halaman']
                    : ($halamanAkhir - $halamanAwal + 1);
            } else {
                $paketQuran   = $materi->paket_quran ?: MushafMadinah::PAKET_REGULER;
                $halamanAwal  = $materi->effective_halaman_awal;
                $halamanAkhir = $materi->effective_halaman_akhir;
            }
        } else {
            $halamanAwal  = 1;
            $halamanAkhir = $jmlHalaman;
        }

        $materi->update([
            'kode_materi'    => !empty($validated['kode_materi']) ? strtoupper(trim($validated['kode_materi'])) : $materi->kode_materi,
            'nama_materi'    => trim($validated['nama_materi']),
            'kategori'       => $validated['kategori'],
            'paket_quran'    => $paketQuran,
            'halaman_awal'   => $halamanAwal,
            'halaman_akhir'  => $halamanAkhir,
            'jumlah_halaman' => $jmlHalaman,
            'jumlah_baris'   => $validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST ? (int) ($validated['jumlah_baris'] ?? 0) : null,
            'keterangan'     => $validated['keterangan'] ?? null,
        ]);

        return redirect()->route('pondok.materi.index')
            ->with('success', 'Data materi "' . $materi->nama_materi . '" berhasil diperbarui.');
    }

    public function destroyMateri(PondokMateri $materi)
    {
        $nama = $materi->nama_materi;
        $materi->delete();

        return redirect()->route('pondok.materi.index')
            ->with('success', 'Materi "' . $nama . '" berhasil dihapus.');
    }

    /* ======================================================================
     * 2. SUBMENU TARGET PENGAJIAN (PER TANGGAL UNTUK MASING-MASING KELAS)
     * ====================================================================== */

    public function targetIndex(Request $request)
    {
        if (MasterKelasPengajian::count() === 0) {
            MasterKelasPengajian::seedStandar();
        }

        $kelasList   = MasterKelasPengajian::orderBy('id')->get();
        $materiList  = PondokMateri::where('is_active', true)->orderBy('kategori')->orderBy('id')->get();
        $daftarSurat = MushafMadinah::daftarSurat();
        $sesiList    = MushafMadinah::daftarSesiPengajian();

        $query = PondokTargetPengajian::with(['kelasPengajian', 'materi', 'jurnals']);

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('kelas_pengajian_id')) {
            $query->where('kelas_pengajian_id', $request->kelas_pengajian_id);
        }

        if ($request->filled('kategori') && in_array($request->kategori, [PondokMateri::KATEGORI_AL_QURAN, PondokMateri::KATEGORI_AL_HADIST], true)) {
            $kat = $request->kategori;
            $query->whereHas('materi', fn ($q) => $q->where('kategori', $kat));
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('keterangan', 'like', "%{$s}%")
                  ->orWhere('nama_surat', 'like', "%{$s}%")
                  ->orWhereHas('materi', fn ($qm) => $qm->where('nama_materi', 'like', "%{$s}%"))
                  ->orWhereHas('kelasPengajian', fn ($qk) => $qk->where('nama_kelas', 'like', "%{$s}%"));
            });
        }

        $targets = $query->orderByDesc('tanggal')->orderBy('kelas_pengajian_id')->orderByDesc('id')->get();

        return view('pondok.target_pengajian', compact(
            'targets',
            'kelasList',
            'materiList',
            'daftarSurat',
            'sesiList'
        ));
    }

    public function storeTarget(Request $request)
    {
        $validated = $request->validate([
            'tanggal'            => ['required', 'date'],
            'sesi_pengajian'     => ['nullable', 'string', 'max:40'],
            'kelas_pengajian_id' => ['required', 'exists:master_kelas_pengajians,id'],
            'pondok_materi_id'   => ['required', 'exists:pondok_materis,id'],
            'surat_nomor'        => ['nullable', 'integer', 'min:1', 'max:114'],
            'ayat'               => ['nullable', 'integer', 'min:1', 'max:286'],
            'surat_akhir_nomor'  => ['nullable', 'integer', 'min:1', 'max:114'],
            'ayat_akhir'         => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_halaman'     => ['nullable', 'integer', 'min:0'],
            'target_baris'       => ['nullable', 'integer', 'min:0'],
            'halaman'            => ['nullable', 'integer', 'min:1'],
            'baris'              => ['nullable', 'integer', 'min:0'],
            'halaman_awal'       => ['nullable', 'integer', 'min:1'],
            'baris_awal'         => ['nullable', 'integer', 'min:1'],
            'halaman_akhir'      => ['nullable', 'integer', 'min:1'],
            'baris_akhir'        => ['nullable', 'integer', 'min:1'],
            'keterangan'         => ['nullable', 'string', 'max:1000'],
        ], [
            'tanggal.required'            => 'Tanggal target pengajian wajib diisi.',
            'kelas_pengajian_id.required' => 'Kelas pengajian wajib dipilih.',
            'pondok_materi_id.required'   => 'Materi pondok wajib dipilih.',
        ]);

        $materi = PondokMateri::findOrFail($validated['pondok_materi_id']);

        $suratNomor      = null;
        $namaSurat       = null;
        $ayat            = null;
        $suratAkhirNomor = null;
        $namaSuratAkhir  = null;
        $ayatAkhir       = null;

        if ($materi->isAlQuran()) {
            if (!empty($validated['surat_nomor'])) {
                $suratNomor = (int) $validated['surat_nomor'];
                $suratData  = MushafMadinah::getSurat($suratNomor);
                $namaSurat  = $suratData['nama'] ?? null;
                $maxAyat    = (int) ($suratData['jumlah_ayat'] ?? 286);
                $ayat       = max(1, min($maxAyat, (int) ($validated['ayat'] ?? 1)));

                $suratAkhirNomor = !empty($validated['surat_akhir_nomor'])
                    ? (int) $validated['surat_akhir_nomor']
                    : $suratNomor;
                $suratAkhirData = MushafMadinah::getSurat($suratAkhirNomor);
                $namaSuratAkhir = $suratAkhirData['nama'] ?? $namaSurat;
                $maxAyatAkhir   = (int) ($suratAkhirData['jumlah_ayat'] ?? 286);
                $rawAyatAkhir   = !empty($validated['ayat_akhir']) ? (int) $validated['ayat_akhir'] : $ayat;
                $ayatAkhir      = max(1, min($maxAyatAkhir, $rawAyatAkhir));

                $targetHalaman = MushafMadinah::estimasiHalaman($suratAkhirNomor, $ayatAkhir);
            } else {
                $targetHalaman = (int) ($validated['target_halaman'] ?? $validated['halaman'] ?? $materi->effective_halaman_awal);
            }
            $targetBaris = null;
        } else {
            $rawHalAwal  = $validated['halaman_awal'] ?? $validated['halaman'] ?? $validated['target_halaman'] ?? 1;
            $rawBarAwal  = $validated['baris_awal'] ?? $validated['baris'] ?? $validated['target_baris'] ?? 1;
            $rawHalAkhir = $validated['halaman_akhir'] ?? $rawHalAwal;
            $rawBarAkhir = $validated['baris_akhir'] ?? $rawBarAwal;

            $maxHal = max(1, (int) $materi->jumlah_halaman);
            $maxBar = max(1, (int) ($materi->jumlah_baris ?: 9999));

            $hAwal  = max(1, min($maxHal, (int) $rawHalAwal));
            $bAwal  = max(1, min($maxBar, (int) $rawBarAwal));
            $hAkhir = max($hAwal, min($maxHal, (int) $rawHalAkhir));
            $bAkhir = ($hAkhir === $hAwal) ? max($bAwal, min($maxBar, (int) $rawBarAkhir)) : max(1, min($maxBar, (int) $rawBarAkhir));

            $targetHalaman = $hAkhir;
            $targetBaris   = $bAkhir;
        }

        PondokTargetPengajian::create([
            'tanggal'            => $validated['tanggal'],
            'sesi_pengajian'     => $validated['sesi_pengajian'] ?? 'bada_maghrib',
            'kelas_pengajian_id' => $validated['kelas_pengajian_id'],
            'pondok_materi_id'   => $validated['pondok_materi_id'],
            'surat_nomor'        => $suratNomor,
            'nama_surat'         => $namaSurat,
            'ayat'               => $ayat,
            'surat_akhir_nomor'  => $suratAkhirNomor,
            'nama_surat_akhir'   => $namaSuratAkhir,
            'ayat_akhir'         => $ayatAkhir,
            'halaman_awal'       => $materi->isAlHadist() ? $hAwal : null,
            'baris_awal'         => $materi->isAlHadist() ? $bAwal : null,
            'halaman_akhir'      => $materi->isAlHadist() ? $hAkhir : null,
            'baris_akhir'        => $materi->isAlHadist() ? $bAkhir : null,
            'target_halaman'     => $targetHalaman,
            'target_baris'       => $targetBaris,
            'keterangan'         => $validated['keterangan'] ?? null,
            'created_by'         => Auth::id(),
        ]);

        return redirect()->route('pondok.target.index')
            ->with('success', 'Target pengajian berhasil disimpan.');
    }

    public function updateTarget(Request $request, PondokTargetPengajian $target)
    {
        $validated = $request->validate([
            'tanggal'            => ['required', 'date'],
            'sesi_pengajian'     => ['nullable', 'string', 'max:40'],
            'kelas_pengajian_id' => ['required', 'exists:master_kelas_pengajians,id'],
            'pondok_materi_id'   => ['required', 'exists:pondok_materis,id'],
            'surat_nomor'        => ['nullable', 'integer', 'min:1', 'max:114'],
            'ayat'               => ['nullable', 'integer', 'min:1', 'max:286'],
            'surat_akhir_nomor'  => ['nullable', 'integer', 'min:1', 'max:114'],
            'ayat_akhir'         => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_halaman'     => ['nullable', 'integer', 'min:0'],
            'target_baris'       => ['nullable', 'integer', 'min:0'],
            'halaman'            => ['nullable', 'integer', 'min:1'],
            'baris'              => ['nullable', 'integer', 'min:0'],
            'halaman_awal'       => ['nullable', 'integer', 'min:1'],
            'baris_awal'         => ['nullable', 'integer', 'min:1'],
            'halaman_akhir'      => ['nullable', 'integer', 'min:1'],
            'baris_akhir'        => ['nullable', 'integer', 'min:1'],
            'keterangan'         => ['nullable', 'string', 'max:1000'],
        ]);

        $materi = PondokMateri::findOrFail($validated['pondok_materi_id']);

        $suratNomor      = null;
        $namaSurat       = null;
        $ayat            = null;
        $suratAkhirNomor = null;
        $namaSuratAkhir  = null;
        $ayatAkhir       = null;

        if ($materi->isAlQuran()) {
            if (!empty($validated['surat_nomor'])) {
                $suratNomor = (int) $validated['surat_nomor'];
                $suratData  = MushafMadinah::getSurat($suratNomor);
                $namaSurat  = $suratData['nama'] ?? null;
                $maxAyat    = (int) ($suratData['jumlah_ayat'] ?? 286);
                $ayat       = max(1, min($maxAyat, (int) ($validated['ayat'] ?? 1)));

                $suratAkhirNomor = !empty($validated['surat_akhir_nomor'])
                    ? (int) $validated['surat_akhir_nomor']
                    : $suratNomor;
                $suratAkhirData = MushafMadinah::getSurat($suratAkhirNomor);
                $namaSuratAkhir = $suratAkhirData['nama'] ?? $namaSurat;
                $maxAyatAkhir   = (int) ($suratAkhirData['jumlah_ayat'] ?? 286);
                $rawAyatAkhir   = !empty($validated['ayat_akhir']) ? (int) $validated['ayat_akhir'] : $ayat;
                $ayatAkhir      = max(1, min($maxAyatAkhir, $rawAyatAkhir));

                $targetHalaman = MushafMadinah::estimasiHalaman($suratAkhirNomor, $ayatAkhir);
            } else {
                $targetHalaman = (int) ($validated['target_halaman'] ?? $validated['halaman'] ?? $target->target_halaman);
            }
            $targetBaris = null;
        } else {
            $rawHalAwal  = $validated['halaman_awal'] ?? $validated['halaman'] ?? $validated['target_halaman'] ?? $target->halaman_awal ?? $target->target_halaman ?? 1;
            $rawBarAwal  = $validated['baris_awal'] ?? $validated['baris'] ?? $validated['target_baris'] ?? $target->baris_awal ?? $target->target_baris ?? 1;
            $rawHalAkhir = $validated['halaman_akhir'] ?? $target->halaman_akhir ?? $rawHalAwal;
            $rawBarAkhir = $validated['baris_akhir'] ?? $target->baris_akhir ?? $rawBarAwal;

            $maxHal = max(1, (int) $materi->jumlah_halaman);
            $maxBar = max(1, (int) ($materi->jumlah_baris ?: 9999));

            $hAwal  = max(1, min($maxHal, (int) $rawHalAwal));
            $bAwal  = max(1, min($maxBar, (int) $rawBarAwal));
            $hAkhir = max($hAwal, min($maxHal, (int) $rawHalAkhir));
            $bAkhir = ($hAkhir === $hAwal) ? max($bAwal, min($maxBar, (int) $rawBarAkhir)) : max(1, min($maxBar, (int) $rawBarAkhir));

            $targetHalaman = $hAkhir;
            $targetBaris   = $bAkhir;
        }

        $target->update([
            'tanggal'            => $validated['tanggal'],
            'sesi_pengajian'     => $validated['sesi_pengajian'] ?? $target->sesi_pengajian ?? 'bada_maghrib',
            'kelas_pengajian_id' => $validated['kelas_pengajian_id'],
            'pondok_materi_id'   => $validated['pondok_materi_id'],
            'surat_nomor'        => $suratNomor,
            'nama_surat'         => $namaSurat,
            'ayat'               => $ayat,
            'surat_akhir_nomor'  => $suratAkhirNomor,
            'nama_surat_akhir'   => $namaSuratAkhir,
            'ayat_akhir'         => $ayatAkhir,
            'halaman_awal'       => $materi->isAlHadist() ? $hAwal : null,
            'baris_awal'         => $materi->isAlHadist() ? $bAwal : null,
            'halaman_akhir'      => $materi->isAlHadist() ? $hAkhir : null,
            'baris_akhir'        => $materi->isAlHadist() ? $bAkhir : null,
            'target_halaman'     => $targetHalaman,
            'target_baris'       => $targetBaris,
            'keterangan'         => $validated['keterangan'] ?? null,
        ]);

        // Sinkronkan ulang status ketercapaian pada jurnal yang terhubung dengan target ini
        foreach ($target->jurnals as $jurnal) {
            $cSuratEnd = (int) ($jurnal->capaian_surat_akhir_nomor ?: $jurnal->capaian_surat_nomor);
            $cAyatEnd  = (int) ($jurnal->capaian_ayat_akhir ?: $jurnal->capaian_ayat);

            $newStatus = PondokJurnalMengaji::hitungStatusKetercapaian(
                $materi->kategori,
                $targetHalaman,
                (int) ($targetBaris ?? 0),
                (int) $jurnal->capaian_halaman,
                (int) ($jurnal->capaian_baris ?? 0),
                $suratAkhirNomor ?: $suratNomor,
                $ayatAkhir ?: $ayat,
                $cSuratEnd ?: null,
                $cAyatEnd ?: null
            );
            $jurnal->update([
                'pondok_materi_id'         => $materi->id,
                'kelas_pengajian_id'       => $validated['kelas_pengajian_id'],
                'target_surat_nomor'       => $suratNomor,
                'target_nama_surat'        => $namaSurat,
                'target_ayat'              => $ayat,
                'target_surat_akhir_nomor' => $suratAkhirNomor,
                'target_nama_surat_akhir'  => $namaSuratAkhir,
                'target_ayat_akhir'        => $ayatAkhir,
                'target_halaman'           => $targetHalaman,
                'target_baris'             => $targetBaris,
                'status_ketercapaian'      => $newStatus,
            ]);
        }

        return redirect()->route('pondok.target.index')
            ->with('success', 'Target pengajian berhasil diperbarui.');
    }

    public function destroyTarget(PondokTargetPengajian $target)
    {
        $target->delete();

        return redirect()->route('pondok.target.index')
            ->with('success', 'Target pengajian berhasil dihapus.');
    }

    /* ======================================================================
     * 3. SUBMENU JURNAL MENGAJI & REKAP JURNAL (TERINTEGRASI ABSENSI SANTRI)
     * ====================================================================== */

    public function jurnalIndex(Request $request)
    {
        return $this->renderJurnalPage($request, false);
    }

    public function rekapJurnal(Request $request)
    {
        return redirect()->route('pondok.jurnal.index', $request->query());
    }

    protected function renderJurnalPage(Request $request, bool $isRekapPage = false)
    {
        if (MasterKelasPengajian::count() === 0) {
            MasterKelasPengajian::seedStandar();
        }

        $kelasList    = MasterKelasPengajian::orderBy('id')->get();
        $materiList   = PondokMateri::where('is_active', true)->orderBy('kategori')->orderBy('id')->get();
        $targetList   = PondokTargetPengajian::with(['kelasPengajian', 'materi'])
            ->orderByDesc('tanggal')
            ->orderBy('kelas_pengajian_id')
            ->get();
        $pengajarList = User::where('is_active', true)->orderBy('name')->get();
        $daftarSurat  = MushafMadinah::daftarSurat();
        $sesiList     = MushafMadinah::daftarSesiPengajian();

        // Ambil daftar santri aktif dikelompokkan per kelas_pengajian_id untuk form absensi pengajian
        $activeSiswas = Siswa::with('pendaftaran')
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '')
                  ->orWhereNotIn('status', ['keluar', 'lulus', 'alumni', 'nonaktif', 'ditolak', 'Ditolak', 'Lulus']);
            })
            ->orderBy('nama_lengkap')
            ->get(['id', 'pendaftaran_id', 'nama_lengkap', 'kamar', 'kelas', 'jenjang', 'kelas_pengajian_id']);

        $todayStr   = Carbon::today()->toDateString();
        $sakitIds   = [];
        $sambangIds = [];

        try {
            $sakitIds = PerizinanSakit::whereDate('tanggal_sakit', '<=', $todayStr)
                ->where('status', 'sakit')
                ->whereNotNull('siswa_id')
                ->pluck('siswa_id')
                ->all();
        } catch (\Throwable $e) {
            $sakitIds = [];
        }

        try {
            $sambangIds = PerizinanSambang::whereDate('tanggal_mulai', '<=', $todayStr)
                ->whereDate('tanggal_kembali', '>=', $todayStr)
                ->whereNotNull('siswa_id')
                ->pluck('siswa_id')
                ->all();
        } catch (\Throwable $e) {
            $sambangIds = [];
        }

        $formatSantriItem = function ($sw) use ($sakitIds, $sambangIds) {
            $defaultStatus = 'hadir';
            $infoIzin = '';
            if (in_array($sw->id, $sakitIds, true)) {
                $defaultStatus = 'sakit';
                $infoIzin = 'Perizinan Sakit';
            } elseif (in_array($sw->id, $sambangIds, true)) {
                $defaultStatus = 'izin';
                $infoIzin = 'Perizinan Sambang';
            }

            return [
                'id'             => $sw->id,
                'nama_lengkap'   => RekapPondokController::cleanStudentName($sw->nama_lengkap),
                'kamar'          => $sw->kamar ?: '-',
                'kelas'          => $sw->kelas ?: '-',
                'default_status' => $defaultStatus,
                'info_izin'      => $infoIzin,
            ];
        };

        $santriByKelas = [];
        foreach ($kelasList as $kls) {
            $santriByKelas[$kls->id] = [];
        }
        foreach ($activeSiswas as $sw) {
            $kId = (int) ($sw->kelas_pengajian_id ?: 0);
            if ($kId <= 0 || !isset($santriByKelas[$kId])) {
                continue;
            }
            $santriByKelas[$kId][] = $formatSantriItem($sw);
        }

        // Fallback untuk Kelas Reguler (atau kelas yang belum di-plot manual) agar santri tetap muncul di form absensi
        foreach ($kelasList as $kls) {
            if (!empty($santriByKelas[$kls->id])) {
                continue;
            }
            $jenjangKls = strtolower(trim((string) ($kls->jenjang ?: '')));
            $namaKls    = strtolower(trim((string) $kls->nama_kelas));
            $isReguler  = ($jenjangKls === 'reguler' || str_contains($namaKls, 'reguler'));

            foreach ($activeSiswas as $sw) {
                $jenjangSw = strtolower(trim((string) ($sw->jenjang ?: ($sw->pendaftaran?->jenjang ?: ''))));
                $kamarSw   = strtolower(trim((string) $sw->kamar));

                if ($isReguler) {
                    $matchReg = str_contains($jenjangSw, 'reguler')
                        || str_contains($kamarSw, 'reguler')
                        || (!str_contains($jenjangSw, 'smp') && !str_contains($jenjangSw, 'sma') && empty($sw->kelas_pengajian_id));
                    if ($matchReg) {
                        $santriByKelas[$kls->id][] = $formatSantriItem($sw);
                    }
                } elseif ($jenjangKls === 'smp' && empty($sw->kelas_pengajian_id)) {
                    if (str_contains($jenjangSw, 'smp') || !empty($sw->kelas)) {
                        $santriByKelas[$kls->id][] = $formatSantriItem($sw);
                    }
                }
            }
        }

        $query = PondokJurnalMengaji::with(['kelasPengajian', 'materi', 'targetPengajian', 'pengajar', 'absensis.siswa']);

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->filled('kelas_pengajian_id')) {
            $query->where('kelas_pengajian_id', $request->kelas_pengajian_id);
        }

        if ($request->filled('status_ketercapaian') && in_array($request->status_ketercapaian, [
            PondokJurnalMengaji::STATUS_TERCAPAI,
            PondokJurnalMengaji::STATUS_KURANG,
            PondokJurnalMengaji::STATUS_TERLAMPAUI,
        ], true)) {
            $query->where('status_ketercapaian', $request->status_ketercapaian);
        }

        if ($request->filled('kategori') && in_array($request->kategori, [PondokMateri::KATEGORI_AL_QURAN, PondokMateri::KATEGORI_AL_HADIST], true)) {
            $kat = $request->kategori;
            $query->whereHas('materi', fn ($q) => $q->where('kategori', $kat));
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $query->where(function ($q) use ($s) {
                $q->where('nama_pengajar', 'like', "%{$s}%")
                  ->orWhere('catatan', 'like', "%{$s}%")
                  ->orWhere('capaian_nama_surat', 'like', "%{$s}%")
                  ->orWhereHas('materi', fn ($qm) => $qm->where('nama_materi', 'like', "%{$s}%"))
                  ->orWhereHas('kelasPengajian', fn ($qk) => $qk->where('nama_kelas', 'like', "%{$s}%"));
            });
        }

        $jurnals = $query->orderByDesc('tanggal')->orderByDesc('id')->get();

        $stats = [
            'total'      => $jurnals->count(),
            'tercapai'   => $jurnals->where('status_ketercapaian', PondokJurnalMengaji::STATUS_TERCAPAI)->count(),
            'kurang'     => $jurnals->where('status_ketercapaian', PondokJurnalMengaji::STATUS_KURANG)->count(),
            'terlampaui' => $jurnals->where('status_ketercapaian', PondokJurnalMengaji::STATUS_TERLAMPAUI)->count(),
        ];

        return view('pondok.jurnal_mengaji', compact(
            'jurnals',
            'stats',
            'kelasList',
            'materiList',
            'targetList',
            'pengajarList',
            'daftarSurat',
            'sesiList',
            'santriByKelas',
            'isRekapPage'
        ));
    }

    public function storeJurnal(Request $request)
    {
        $validated = $request->validate([
            'tanggal'                   => ['required', 'date'],
            'sesi_pengajian'            => ['nullable', 'string', 'max:40'],
            'kelas_pengajian_id'        => ['required', 'exists:master_kelas_pengajians,id'],
            'pondok_materi_id'          => ['required', 'exists:pondok_materis,id'],
            'target_pengajian_id'       => ['nullable', 'exists:pondok_target_pengajians,id'],
            'pengajar_id'               => ['nullable', 'exists:users,id'],
            'nama_pengajar'             => ['nullable', 'string', 'max:150'],
            'target_surat_nomor'        => ['nullable', 'integer', 'min:1', 'max:114'],
            'target_ayat'               => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_surat_akhir_nomor'  => ['nullable', 'integer', 'min:1', 'max:114'],
            'target_ayat_akhir'         => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_halaman'            => ['nullable', 'integer', 'min:0'],
            'target_baris'              => ['nullable', 'integer', 'min:0'],
            'target_halaman_awal'       => ['nullable', 'integer', 'min:1'],
            'target_baris_awal'         => ['nullable', 'integer', 'min:1'],
            'target_halaman_akhir'      => ['nullable', 'integer', 'min:1'],
            'target_baris_akhir'        => ['nullable', 'integer', 'min:1'],
            'halaman_awal'              => ['nullable', 'integer', 'min:1'],
            'baris_awal'                => ['nullable', 'integer', 'min:1'],
            'halaman_akhir'             => ['nullable', 'integer', 'min:1'],
            'baris_akhir'               => ['nullable', 'integer', 'min:1'],
            'is_khatam'                 => ['nullable', 'boolean'],
            'lanjutan_materi_id'        => ['nullable', 'exists:pondok_materis,id'],
            'lanjutan_halaman_awal'     => ['nullable', 'integer', 'min:1'],
            'lanjutan_baris_awal'       => ['nullable', 'integer', 'min:1'],
            'lanjutan_halaman_akhir'    => ['nullable', 'integer', 'min:1'],
            'lanjutan_baris_akhir'      => ['nullable', 'integer', 'min:1'],
            'capaian_surat_nomor'       => ['nullable', 'integer', 'min:1', 'max:114'],
            'capaian_ayat'              => ['nullable', 'integer', 'min:1', 'max:286'],
            'capaian_surat_akhir_nomor' => ['nullable', 'integer', 'min:1', 'max:114'],
            'capaian_ayat_akhir'        => ['nullable', 'integer', 'min:1', 'max:286'],
            'capaian_halaman'           => ['nullable', 'integer', 'min:0'],
            'capaian_baris'             => ['nullable', 'integer', 'min:0'],
            'catatan'                   => ['nullable', 'string', 'max:1000'],
            'absensi'                   => ['nullable', 'array'],
            'absensi.*'                 => ['nullable', 'string', Rule::in(['hadir', 'terlambat', 'izin', 'sakit', 'alpa'])],
        ], [
            'tanggal.required'            => 'Tanggal jurnal wajib diisi.',
            'kelas_pengajian_id.required' => 'Kelas pengajian wajib dipilih.',
            'pondok_materi_id.required'   => 'Materi pengajian wajib dipilih.',
        ]);

        $materi = PondokMateri::findOrFail($validated['pondok_materi_id']);

        $targetModel = null;
        if (!empty($validated['target_pengajian_id'])) {
            $targetModel = PondokTargetPengajian::find($validated['target_pengajian_id']);
        }
        if (!$targetModel) {
            $targetModel = PondokTargetPengajian::whereDate('tanggal', $validated['tanggal'])
                ->where('kelas_pengajian_id', $validated['kelas_pengajian_id'])
                ->where('pondok_materi_id', $validated['pondok_materi_id'])
                ->latest('id')
                ->first();
        }
        if (!$targetModel) {
            $targetModel = PondokTargetPengajian::where('kelas_pengajian_id', $validated['kelas_pengajian_id'])
                ->where('pondok_materi_id', $validated['pondok_materi_id'])
                ->orderByDesc('tanggal')
                ->latest('id')
                ->first();
        }

        $targetSuratNomor       = null;
        $targetNamaSurat        = null;
        $targetAyat             = null;
        $targetSuratAkhirNomor  = null;
        $targetNamaSuratAkhir   = null;
        $targetAyatAkhir        = null;
        $capaianSuratNomor      = null;
        $capaianNamaSurat       = null;
        $capaianAyat            = null;
        $capaianSuratAkhirNomor = null;
        $capaianNamaSuratAkhir  = null;
        $capaianAyatAkhir       = null;

        if ($materi->isAlQuran()) {
            $targetSuratNomor = !empty($validated['target_surat_nomor'])
                ? (int) $validated['target_surat_nomor']
                : ($targetModel?->surat_nomor);
            if ($targetSuratNomor) {
                $tSurat = MushafMadinah::getSurat($targetSuratNomor);
                $targetNamaSurat = $tSurat['nama'] ?? null;
                $targetAyat = !empty($validated['target_ayat'])
                    ? (int) $validated['target_ayat']
                    : ($targetModel?->ayat ?? 1);

                $targetSuratAkhirNomor = !empty($validated['target_surat_akhir_nomor'])
                    ? (int) $validated['target_surat_akhir_nomor']
                    : ($targetModel?->surat_akhir_nomor ?: $targetSuratNomor);
                $tSuratAkhir = MushafMadinah::getSurat($targetSuratAkhirNomor);
                $targetNamaSuratAkhir = $tSuratAkhir['nama'] ?? $targetNamaSurat;
                $targetAyatAkhir = !empty($validated['target_ayat_akhir'])
                    ? (int) $validated['target_ayat_akhir']
                    : ($targetModel?->ayat_akhir ?: $targetAyat);

                $targetHalaman = MushafMadinah::estimasiHalaman($targetSuratAkhirNomor, $targetAyatAkhir);
            } else {
                $targetHalaman = $targetModel
                    ? (int) $targetModel->target_halaman
                    : (int) ($validated['target_halaman'] ?? 0);
            }

            if (!empty($validated['capaian_surat_nomor'])) {
                $capaianSuratNomor = (int) $validated['capaian_surat_nomor'];
                $cSurat = MushafMadinah::getSurat($capaianSuratNomor);
                $capaianNamaSurat = $cSurat['nama'] ?? null;
                $capaianAyat = max(1, (int) ($validated['capaian_ayat'] ?? 1));

                $capaianSuratAkhirNomor = !empty($validated['capaian_surat_akhir_nomor'])
                    ? (int) $validated['capaian_surat_akhir_nomor']
                    : $capaianSuratNomor;
                $cSuratAkhir = MushafMadinah::getSurat($capaianSuratAkhirNomor);
                $capaianNamaSuratAkhir = $cSuratAkhir['nama'] ?? $capaianNamaSurat;
                $capaianAyatAkhir = !empty($validated['capaian_ayat_akhir'])
                    ? max(1, (int) $validated['capaian_ayat_akhir'])
                    : $capaianAyat;

                $capaianHalaman = MushafMadinah::estimasiHalaman($capaianSuratAkhirNomor, $capaianAyatAkhir);
            } else {
                $capaianHalaman = (int) ($validated['capaian_halaman'] ?? 0);
            }

            $targetBaris  = 0;
            $capaianBaris = 0;
        } else {
            $targetHAwal  = (int) ($validated['target_halaman_awal'] ?? $targetModel?->halaman_awal ?: $targetModel?->target_halaman ?: 1);
            $targetBAwal  = (int) ($validated['target_baris_awal'] ?? $targetModel?->baris_awal ?: $targetModel?->target_baris ?: 1);
            $targetHAkhir = (int) ($validated['target_halaman_akhir'] ?? $targetModel?->halaman_akhir ?: $targetModel?->target_halaman ?: $targetHAwal);
            $targetBAkhir = (int) ($validated['target_baris_akhir'] ?? $targetModel?->baris_akhir ?: $targetModel?->target_baris ?: $targetBAwal);

            $targetHalaman = $targetHAkhir;
            $targetBaris   = $targetBAkhir;

            $rawCapaianHAwal  = $validated['halaman_awal'] ?? $validated['capaian_halaman'] ?? 1;
            $rawCapaianBAwal  = $validated['baris_awal'] ?? $validated['capaian_baris'] ?? 1;
            $rawCapaianHAkhir = $validated['halaman_akhir'] ?? $validated['capaian_halaman'] ?? $rawCapaianHAwal;
            $rawCapaianBAkhir = $validated['baris_akhir'] ?? $validated['capaian_baris'] ?? $rawCapaianBAwal;

            $capaianHAwal  = max(1, (int) $rawCapaianHAwal);
            $capaianBAwal  = max(1, (int) $rawCapaianBAwal);
            $capaianHAkhir = max(1, (int) $rawCapaianHAkhir);
            $capaianBAkhir = max(1, (int) $rawCapaianBAkhir);

            $capaianHalaman = $capaianHAkhir;
            $capaianBaris   = $capaianBAkhir;

            $isKhatam          = !empty($validated['is_khatam']);
            $lanjutanMateriId  = $isKhatam ? ($validated['lanjutan_materi_id'] ?? null) : null;
            $lanjutanHAwal     = $isKhatam ? (int) ($validated['lanjutan_halaman_awal'] ?? 1) : null;
            $lanjutanBAwal     = $isKhatam ? (int) ($validated['lanjutan_baris_awal'] ?? 1) : null;
            $lanjutanHAkhir    = $isKhatam ? (int) ($validated['lanjutan_halaman_akhir'] ?? $lanjutanHAwal) : null;
            $lanjutanBAkhir    = $isKhatam ? (int) ($validated['lanjutan_baris_akhir'] ?? $lanjutanBAwal) : null;
        }

        $statusKetercapaian = PondokJurnalMengaji::hitungStatusKetercapaian(
            $materi->kategori,
            $targetHalaman,
            $targetBaris,
            $capaianHalaman,
            $capaianBaris,
            $targetSuratAkhirNomor ?: $targetSuratNomor,
            $targetAyatAkhir ?: $targetAyat,
            $capaianSuratAkhirNomor ?: $capaianSuratNomor,
            $capaianAyatAkhir ?: $capaianAyat,
            $isKhatam ?? false
        );

        $pengajarUser = !empty($validated['pengajar_id']) ? User::find($validated['pengajar_id']) : Auth::user();
        $namaPengajar = !empty($validated['nama_pengajar'])
            ? trim($validated['nama_pengajar'])
            : ($pengajarUser?->name ?? 'Pengajar Pondok');

        $sesiPengajian = !empty($validated['sesi_pengajian'])
            ? $validated['sesi_pengajian']
            : ($targetModel?->sesi_pengajian ?: 'bada_maghrib');

        $jurnal = PondokJurnalMengaji::create([
            'tanggal'                   => $validated['tanggal'],
            'sesi_pengajian'            => $sesiPengajian,
            'kelas_pengajian_id'        => $validated['kelas_pengajian_id'],
            'pondok_materi_id'          => $validated['pondok_materi_id'],
            'target_pengajian_id'       => $targetModel?->id,
            'pengajar_id'               => $pengajarUser?->id,
            'nama_pengajar'             => $namaPengajar,
            'target_surat_nomor'        => $targetSuratNomor,
            'target_nama_surat'         => $targetNamaSurat,
            'target_ayat'               => $targetAyat,
            'target_surat_akhir_nomor'  => $targetSuratAkhirNomor,
            'target_nama_surat_akhir'   => $targetNamaSuratAkhir,
            'target_ayat_akhir'         => $targetAyatAkhir,
            'target_halaman'            => $targetHalaman,
            'target_baris'              => $materi->isAlHadist() ? $targetBaris : null,
            'target_halaman_awal'       => $materi->isAlHadist() ? $targetHAwal : null,
            'target_halaman_akhir'      => $materi->isAlHadist() ? $targetHAkhir : null,
            'target_baris_awal'         => $materi->isAlHadist() ? $targetBAwal : null,
            'target_baris_akhir'        => $materi->isAlHadist() ? $targetBAkhir : null,
            'capaian_surat_nomor'       => $capaianSuratNomor,
            'capaian_nama_surat'        => $capaianNamaSurat,
            'capaian_ayat'              => $capaianAyat,
            'capaian_surat_akhir_nomor' => $capaianSuratAkhirNomor,
            'capaian_nama_surat_akhir'  => $capaianNamaSuratAkhir,
            'capaian_ayat_akhir'        => $capaianAyatAkhir,
            'capaian_halaman'           => $capaianHalaman,
            'capaian_baris'             => $materi->isAlHadist() ? $capaianBaris : null,
            'halaman_awal'              => $materi->isAlHadist() ? $capaianHAwal : null,
            'halaman_akhir'             => $materi->isAlHadist() ? $capaianHAkhir : null,
            'baris_awal'                => $materi->isAlHadist() ? $capaianBAwal : null,
            'baris_akhir'               => $materi->isAlHadist() ? $capaianBAkhir : null,
            'is_khatam'                 => $isKhatam ?? false,
            'lanjutan_materi_id'        => $lanjutanMateriId ?? null,
            'lanjutan_halaman_awal'     => $lanjutanHAwal ?? null,
            'lanjutan_baris_awal'       => $lanjutanBAwal ?? null,
            'lanjutan_halaman_akhir'    => $lanjutanHAkhir ?? null,
            'lanjutan_baris_akhir'      => $lanjutanBAkhir ?? null,
            'status_ketercapaian'       => $statusKetercapaian,
            'catatan'                   => $validated['catatan'] ?? null,
        ]);

        // Simpan absensi santri pengajian & sinkronkan rekap kehadiran
        $this->syncJurnalAbsensi($jurnal, $validated['absensi'] ?? null);

        return redirect()->route('pondok.jurnal.index')
            ->with('success', 'Jurnal & absensi pengajian berhasil disimpan dengan status ketercapaian: ' . ucfirst($statusKetercapaian) . '.');
    }

    public function updateJurnal(Request $request, PondokJurnalMengaji $jurnal)
    {
        $validated = $request->validate([
            'tanggal'                   => ['required', 'date'],
            'sesi_pengajian'            => ['nullable', 'string', 'max:40'],
            'kelas_pengajian_id'        => ['required', 'exists:master_kelas_pengajians,id'],
            'pondok_materi_id'          => ['required', 'exists:pondok_materis,id'],
            'target_pengajian_id'       => ['nullable', 'exists:pondok_target_pengajians,id'],
            'pengajar_id'               => ['nullable', 'exists:users,id'],
            'nama_pengajar'             => ['nullable', 'string', 'max:150'],
            'target_surat_nomor'        => ['nullable', 'integer', 'min:1', 'max:114'],
            'target_ayat'               => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_surat_akhir_nomor'  => ['nullable', 'integer', 'min:1', 'max:114'],
            'target_ayat_akhir'         => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_halaman'            => ['nullable', 'integer', 'min:0'],
            'target_baris'              => ['nullable', 'integer', 'min:0'],
            'target_halaman_awal'       => ['nullable', 'integer', 'min:1'],
            'target_baris_awal'         => ['nullable', 'integer', 'min:1'],
            'target_halaman_akhir'      => ['nullable', 'integer', 'min:1'],
            'target_baris_akhir'        => ['nullable', 'integer', 'min:1'],
            'halaman_awal'              => ['nullable', 'integer', 'min:1'],
            'baris_awal'                => ['nullable', 'integer', 'min:1'],
            'halaman_akhir'             => ['nullable', 'integer', 'min:1'],
            'baris_akhir'               => ['nullable', 'integer', 'min:1'],
            'is_khatam'                 => ['nullable', 'boolean'],
            'lanjutan_materi_id'        => ['nullable', 'exists:pondok_materis,id'],
            'lanjutan_halaman_awal'     => ['nullable', 'integer', 'min:1'],
            'lanjutan_baris_awal'       => ['nullable', 'integer', 'min:1'],
            'lanjutan_halaman_akhir'    => ['nullable', 'integer', 'min:1'],
            'lanjutan_baris_akhir'      => ['nullable', 'integer', 'min:1'],
            'capaian_surat_nomor'       => ['nullable', 'integer', 'min:1', 'max:114'],
            'capaian_ayat'              => ['nullable', 'integer', 'min:1', 'max:286'],
            'capaian_surat_akhir_nomor' => ['nullable', 'integer', 'min:1', 'max:114'],
            'capaian_ayat_akhir'        => ['nullable', 'integer', 'min:1', 'max:286'],
            'capaian_halaman'           => ['nullable', 'integer', 'min:0'],
            'capaian_baris'             => ['nullable', 'integer', 'min:0'],
            'catatan'                   => ['nullable', 'string', 'max:1000'],
            'absensi'                   => ['nullable', 'array'],
            'absensi.*'                 => ['nullable', 'string', Rule::in(['hadir', 'terlambat', 'izin', 'sakit', 'alpa'])],
        ]);

        $materi = PondokMateri::findOrFail($validated['pondok_materi_id']);

        $targetModel = null;
        if (!empty($validated['target_pengajian_id'])) {
            $targetModel = PondokTargetPengajian::find($validated['target_pengajian_id']);
        }
        if (!$targetModel) {
            $targetModel = PondokTargetPengajian::whereDate('tanggal', $validated['tanggal'])
                ->where('kelas_pengajian_id', $validated['kelas_pengajian_id'])
                ->where('pondok_materi_id', $validated['pondok_materi_id'])
                ->latest('id')
                ->first();
        }
        if (!$targetModel) {
            $targetModel = PondokTargetPengajian::where('kelas_pengajian_id', $validated['kelas_pengajian_id'])
                ->where('pondok_materi_id', $validated['pondok_materi_id'])
                ->orderByDesc('tanggal')
                ->latest('id')
                ->first();
        }

        $targetSuratNomor       = null;
        $targetNamaSurat        = null;
        $targetAyat             = null;
        $targetSuratAkhirNomor  = null;
        $targetNamaSuratAkhir   = null;
        $targetAyatAkhir        = null;
        $capaianSuratNomor      = null;
        $capaianNamaSurat       = null;
        $capaianAyat            = null;
        $capaianSuratAkhirNomor = null;
        $capaianNamaSuratAkhir  = null;
        $capaianAyatAkhir       = null;

        if ($materi->isAlQuran()) {
            $targetSuratNomor = !empty($validated['target_surat_nomor'])
                ? (int) $validated['target_surat_nomor']
                : ($targetModel?->surat_nomor ?? $jurnal->target_surat_nomor);
            if ($targetSuratNomor) {
                $tSurat = MushafMadinah::getSurat($targetSuratNomor);
                $targetNamaSurat = $tSurat['nama'] ?? null;
                $targetAyat = !empty($validated['target_ayat'])
                    ? (int) $validated['target_ayat']
                    : ($targetModel?->ayat ?? $jurnal->target_ayat ?? 1);

                $targetSuratAkhirNomor = !empty($validated['target_surat_akhir_nomor'])
                    ? (int) $validated['target_surat_akhir_nomor']
                    : ($targetModel?->surat_akhir_nomor ?: ($jurnal->target_surat_akhir_nomor ?: $targetSuratNomor));
                $tSuratAkhir = MushafMadinah::getSurat($targetSuratAkhirNomor);
                $targetNamaSuratAkhir = $tSuratAkhir['nama'] ?? $targetNamaSurat;
                $targetAyatAkhir = !empty($validated['target_ayat_akhir'])
                    ? (int) $validated['target_ayat_akhir']
                    : ($targetModel?->ayat_akhir ?: ($jurnal->target_ayat_akhir ?: $targetAyat));

                $targetHalaman = MushafMadinah::estimasiHalaman($targetSuratAkhirNomor, $targetAyatAkhir);
            } else {
                $targetHalaman = isset($validated['target_halaman'])
                    ? (int) $validated['target_halaman']
                    : ($targetModel ? (int) $targetModel->target_halaman : (int) $jurnal->target_halaman);
            }

            if (!empty($validated['capaian_surat_nomor'])) {
                $capaianSuratNomor = (int) $validated['capaian_surat_nomor'];
                $cSurat = MushafMadinah::getSurat($capaianSuratNomor);
                $capaianNamaSurat = $cSurat['nama'] ?? null;
                $capaianAyat = max(1, (int) ($validated['capaian_ayat'] ?? 1));

                $capaianSuratAkhirNomor = !empty($validated['capaian_surat_akhir_nomor'])
                    ? (int) $validated['capaian_surat_akhir_nomor']
                    : $capaianSuratNomor;
                $cSuratAkhir = MushafMadinah::getSurat($capaianSuratAkhirNomor);
                $capaianNamaSuratAkhir = $cSuratAkhir['nama'] ?? $capaianNamaSurat;
                $capaianAyatAkhir = !empty($validated['capaian_ayat_akhir'])
                    ? max(1, (int) $validated['capaian_ayat_akhir'])
                    : $capaianAyat;

                $capaianHalaman = MushafMadinah::estimasiHalaman($capaianSuratAkhirNomor, $capaianAyatAkhir);
            } else {
                $capaianHalaman = (int) ($validated['capaian_halaman'] ?? $jurnal->capaian_halaman);
            }

            $targetBaris  = 0;
            $capaianBaris = 0;
        } else {
            $targetHAwal  = (int) ($validated['target_halaman_awal'] ?? $targetModel?->halaman_awal ?: ($jurnal->target_halaman_awal ?: $jurnal->target_halaman ?: 1));
            $targetBAwal  = (int) ($validated['target_baris_awal'] ?? $targetModel?->baris_awal ?: ($jurnal->target_baris_awal ?: $jurnal->target_baris ?: 1));
            $targetHAkhir = (int) ($validated['target_halaman_akhir'] ?? $targetModel?->halaman_akhir ?: ($jurnal->target_halaman_akhir ?: $jurnal->target_halaman ?: $targetHAwal));
            $targetBAkhir = (int) ($validated['target_baris_akhir'] ?? $targetModel?->baris_akhir ?: ($jurnal->target_baris_akhir ?: $jurnal->target_baris ?: $targetBAwal));

            $targetHalaman = $targetHAkhir;
            $targetBaris   = $targetBAkhir;

            $rawCapaianHAwal  = $validated['halaman_awal'] ?? $validated['capaian_halaman'] ?? ($jurnal->halaman_awal ?: $jurnal->capaian_halaman ?: 1);
            $rawCapaianBAwal  = $validated['baris_awal'] ?? $validated['capaian_baris'] ?? ($jurnal->baris_awal ?: $jurnal->capaian_baris ?: 1);
            $rawCapaianHAkhir = $validated['halaman_akhir'] ?? $validated['capaian_halaman'] ?? ($jurnal->halaman_akhir ?: $jurnal->capaian_halaman ?: $rawCapaianHAwal);
            $rawCapaianBAkhir = $validated['baris_akhir'] ?? $validated['capaian_baris'] ?? ($jurnal->baris_akhir ?: $jurnal->capaian_baris ?: $rawCapaianBAwal);

            $capaianHAwal  = max(1, (int) $rawCapaianHAwal);
            $capaianBAwal  = max(1, (int) $rawCapaianBAwal);
            $capaianHAkhir = max(1, (int) $rawCapaianHAkhir);
            $capaianBAkhir = max(1, (int) $rawCapaianBAkhir);

            $capaianHalaman = $capaianHAkhir;
            $capaianBaris   = $capaianBAkhir;

            $isKhatam          = !empty($validated['is_khatam']);
            $lanjutanMateriId  = $isKhatam ? ($validated['lanjutan_materi_id'] ?? null) : null;
            $lanjutanHAwal     = $isKhatam ? (int) ($validated['lanjutan_halaman_awal'] ?? 1) : null;
            $lanjutanBAwal     = $isKhatam ? (int) ($validated['lanjutan_baris_awal'] ?? 1) : null;
            $lanjutanHAkhir    = $isKhatam ? (int) ($validated['lanjutan_halaman_akhir'] ?? $lanjutanHAwal) : null;
            $lanjutanBAkhir    = $isKhatam ? (int) ($validated['lanjutan_baris_akhir'] ?? $lanjutanBAwal) : null;
        }

        $statusKetercapaian = PondokJurnalMengaji::hitungStatusKetercapaian(
            $materi->kategori,
            $targetHalaman,
            $targetBaris,
            $capaianHalaman,
            $capaianBaris,
            $targetSuratAkhirNomor ?: $targetSuratNomor,
            $targetAyatAkhir ?: $targetAyat,
            $capaianSuratAkhirNomor ?: $capaianSuratNomor,
            $capaianAyatAkhir ?: $capaianAyat,
            $isKhatam ?? false
        );

        $pengajarUser = !empty($validated['pengajar_id']) ? User::find($validated['pengajar_id']) : $jurnal->pengajar;
        $namaPengajar = !empty($validated['nama_pengajar'])
            ? trim($validated['nama_pengajar'])
            : ($pengajarUser?->name ?? $jurnal->nama_pengajar);

        $jurnal->update([
            'tanggal'                   => $validated['tanggal'],
            'sesi_pengajian'            => $validated['sesi_pengajian'] ?? $jurnal->sesi_pengajian ?? 'bada_maghrib',
            'kelas_pengajian_id'        => $validated['kelas_pengajian_id'],
            'pondok_materi_id'          => $validated['pondok_materi_id'],
            'target_pengajian_id'       => $targetModel?->id,
            'pengajar_id'               => $pengajarUser?->id,
            'nama_pengajar'             => $namaPengajar,
            'target_surat_nomor'        => $targetSuratNomor,
            'target_nama_surat'         => $targetNamaSurat,
            'target_ayat'               => $targetAyat,
            'target_surat_akhir_nomor'  => $targetSuratAkhirNomor,
            'target_nama_surat_akhir'   => $targetNamaSuratAkhir,
            'target_ayat_akhir'         => $targetAyatAkhir,
            'target_halaman'            => $targetHalaman,
            'target_baris'              => $materi->isAlHadist() ? $targetBaris : null,
            'target_halaman_awal'       => $materi->isAlHadist() ? $targetHAwal : null,
            'target_halaman_akhir'      => $materi->isAlHadist() ? $targetHAkhir : null,
            'target_baris_awal'         => $materi->isAlHadist() ? $targetBAwal : null,
            'target_baris_akhir'        => $materi->isAlHadist() ? $targetBAkhir : null,
            'capaian_surat_nomor'       => $capaianSuratNomor,
            'capaian_nama_surat'        => $capaianNamaSurat,
            'capaian_ayat'              => $capaianAyat,
            'capaian_surat_akhir_nomor' => $capaianSuratAkhirNomor,
            'capaian_nama_surat_akhir'  => $capaianNamaSuratAkhir,
            'capaian_ayat_akhir'        => $capaianAyatAkhir,
            'capaian_halaman'           => $capaianHalaman,
            'capaian_baris'             => $materi->isAlHadist() ? $capaianBaris : null,
            'halaman_awal'              => $materi->isAlHadist() ? $capaianHAwal : null,
            'halaman_akhir'             => $materi->isAlHadist() ? $capaianHAkhir : null,
            'baris_awal'                => $materi->isAlHadist() ? $capaianBAwal : null,
            'baris_akhir'               => $materi->isAlHadist() ? $capaianBAkhir : null,
            'is_khatam'                 => $isKhatam ?? false,
            'lanjutan_materi_id'        => $lanjutanMateriId ?? null,
            'lanjutan_halaman_awal'     => $lanjutanHAwal ?? null,
            'lanjutan_baris_awal'       => $lanjutanBAwal ?? null,
            'lanjutan_halaman_akhir'    => $lanjutanHAkhir ?? null,
            'lanjutan_baris_akhir'      => $lanjutanBAkhir ?? null,
            'status_ketercapaian'       => $statusKetercapaian,
            'catatan'                   => $validated['catatan'] ?? null,
        ]);

        $this->syncJurnalAbsensi($jurnal, $validated['absensi'] ?? null);

        return redirect()->route('pondok.jurnal.index')
            ->with('success', 'Jurnal & absensi pengajian berhasil diperbarui.');
    }

    /**
     * Simpan detail absensi santri untuk jurnal pengajian dan hitung ringkasan total kehadiran.
     */
    protected function syncJurnalAbsensi(PondokJurnalMengaji $jurnal, ?array $absensiInput): void
    {
        $santris = Siswa::where('kelas_pengajian_id', $jurnal->kelas_pengajian_id)
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '')
                  ->orWhereNotIn('status', ['keluar', 'lulus', 'alumni', 'nonaktif', 'ditolak', 'Ditolak', 'Lulus']);
            })
            ->get(['id']);

        $tglStr     = Carbon::parse($jurnal->tanggal)->toDateString();
        $sakitIds   = [];
        $sambangIds = [];

        try {
            $sakitIds = PerizinanSakit::whereDate('tanggal_sakit', '<=', $tglStr)
                ->where('status', 'sakit')
                ->whereNotNull('siswa_id')
                ->pluck('siswa_id')
                ->all();
        } catch (\Throwable $e) {
            $sakitIds = [];
        }

        try {
            $sambangIds = PerizinanSambang::whereDate('tanggal_mulai', '<=', $tglStr)
                ->whereDate('tanggal_kembali', '>=', $tglStr)
                ->whereNotNull('siswa_id')
                ->pluck('siswa_id')
                ->all();
        } catch (\Throwable $e) {
            $sambangIds = [];
        }

        $targetMap = [];

        foreach ($santris as $sw) {
            $sId = (int) $sw->id;
            if (is_array($absensiInput) && isset($absensiInput[$sId])) {
                $status = strtolower(trim((string) $absensiInput[$sId]));
            } elseif (in_array($sId, $sakitIds, true)) {
                $status = PondokJurnalAbsensi::STATUS_SAKIT;
            } elseif (in_array($sId, $sambangIds, true)) {
                $status = PondokJurnalAbsensi::STATUS_IZIN;
            } else {
                $status = PondokJurnalAbsensi::STATUS_HADIR;
            }

            if (!in_array($status, ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'], true)) {
                $status = PondokJurnalAbsensi::STATUS_HADIR;
            }
            $targetMap[$sId] = $status;
        }

        // Juga dukung jika ada siswa_id yang dikirim langsung pada $absensiInput meskipun belum di-plot
        if (is_array($absensiInput)) {
            foreach ($absensiInput as $sIdKey => $rawSt) {
                $sId = (int) $sIdKey;
                if ($sId > 0 && !isset($targetMap[$sId]) && Siswa::where('id', $sId)->exists()) {
                    $st = strtolower(trim((string) $rawSt));
                    if (in_array($st, ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'], true)) {
                        $targetMap[$sId] = $st;
                    }
                }
            }
        }

        $jurnal->absensis()->delete();

        $totalHadir     = 0;
        $totalTerlambat = 0;
        $totalIzin      = 0;
        $totalSakit     = 0;
        $totalAlpa      = 0;

        foreach ($targetMap as $sId => $status) {
            PondokJurnalAbsensi::create([
                'jurnal_mengaji_id' => $jurnal->id,
                'siswa_id'          => $sId,
                'status'            => $status,
            ]);

            match ($status) {
                'terlambat' => $totalTerlambat++,
                'izin'      => $totalIzin++,
                'sakit'     => $totalSakit++,
                'alpa'      => $totalAlpa++,
                default     => $totalHadir++,
            };
        }

        $jurnal->update([
            'total_santri'    => count($targetMap),
            'total_hadir'     => $totalHadir,
            'total_terlambat' => $totalTerlambat,
            'total_izin'      => $totalIzin,
            'total_sakit'     => $totalSakit,
            'total_alpa'      => $totalAlpa,
        ]);
    }

    public function destroyJurnal(PondokJurnalMengaji $jurnal)
    {
        $jurnal->delete();

        return redirect()->route('pondok.jurnal.index')
            ->with('success', 'Data jurnal mengaji berhasil dihapus.');
    }
}
