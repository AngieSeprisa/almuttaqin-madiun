<?php

namespace App\Http\Controllers;

use App\Models\MasterKelasPengajian;
use App\Models\PondokJurnalMengaji;
use App\Models\PondokMateri;
use App\Models\PondokTargetPengajian;
use App\Models\User;
use App\Support\MushafMadinah;
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

        $materis = $query->orderBy('kategori')->orderBy('nama_materi')->get();

        $totalMateri = PondokMateri::count();
        $totalQuran  = PondokMateri::where('kategori', PondokMateri::KATEGORI_AL_QURAN)->count();
        $totalHadist = PondokMateri::where('kategori', PondokMateri::KATEGORI_AL_HADIST)->count();

        return view('pondok.materi', compact(
            'materis',
            'totalMateri',
            'totalQuran',
            'totalHadist'
        ));
    }

    public function storeMateri(Request $request)
    {
        $validated = $request->validate([
            'kode_materi'    => ['nullable', 'string', 'max:50'],
            'nama_materi'    => ['required', 'string', 'max:150'],
            'kategori'       => ['required', Rule::in([PondokMateri::KATEGORI_AL_QURAN, PondokMateri::KATEGORI_AL_HADIST])],
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

        $jmlHalaman = !empty($validated['jumlah_halaman'])
            ? (int) $validated['jumlah_halaman']
            : ($validated['kategori'] === PondokMateri::KATEGORI_AL_QURAN ? MushafMadinah::TOTAL_HALAMAN : 1);

        $prefix = $validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST ? 'HAD' : 'QUR';
        $kode = !empty($validated['kode_materi'])
            ? strtoupper(trim($validated['kode_materi']))
            : sprintf('%s-%02d', $prefix, PondokMateri::where('kategori', $validated['kategori'])->count() + 1);

        PondokMateri::create([
            'kode_materi'    => $kode,
            'nama_materi'    => trim($validated['nama_materi']),
            'kategori'       => $validated['kategori'],
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
            'jumlah_halaman' => ['nullable', 'integer', 'min:1'],
            'jumlah_baris'   => ['nullable', 'integer', 'min:0'],
            'keterangan'     => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST && ($validated['jumlah_baris'] === null || $validated['jumlah_baris'] === '')) {
            return back()->withInput()->with('error', 'Untuk kategori Al-Hadist, jumlah baris wajib diisi.');
        }

        $jmlHalaman = !empty($validated['jumlah_halaman'])
            ? (int) $validated['jumlah_halaman']
            : ($validated['kategori'] === PondokMateri::KATEGORI_AL_QURAN ? MushafMadinah::TOTAL_HALAMAN : $materi->jumlah_halaman);

        $materi->update([
            'kode_materi'    => !empty($validated['kode_materi']) ? strtoupper(trim($validated['kode_materi'])) : $materi->kode_materi,
            'nama_materi'    => trim($validated['nama_materi']),
            'kategori'       => $validated['kategori'],
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
        $materiList  = PondokMateri::where('is_active', true)->orderBy('kategori')->orderBy('nama_materi')->get();
        $daftarSurat = MushafMadinah::daftarSurat();

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
            'daftarSurat'
        ));
    }

    public function storeTarget(Request $request)
    {
        $validated = $request->validate([
            'tanggal'            => ['required', 'date'],
            'kelas_pengajian_id' => ['required', 'exists:master_kelas_pengajians,id'],
            'pondok_materi_id'   => ['required', 'exists:pondok_materis,id'],
            'surat_nomor'        => ['nullable', 'integer', 'min:1', 'max:114'],
            'ayat'               => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_halaman'     => ['nullable', 'integer', 'min:0'],
            'target_baris'       => ['nullable', 'integer', 'min:0'],
            'halaman'            => ['nullable', 'integer', 'min:1'],
            'baris'              => ['nullable', 'integer', 'min:0'],
            'keterangan'         => ['nullable', 'string', 'max:1000'],
        ], [
            'tanggal.required'            => 'Tanggal target pengajian wajib diisi.',
            'kelas_pengajian_id.required' => 'Kelas pengajian wajib dipilih.',
            'pondok_materi_id.required'   => 'Materi pondok wajib dipilih.',
        ]);

        $materi = PondokMateri::findOrFail($validated['pondok_materi_id']);

        $suratNomor = null;
        $namaSurat  = null;
        $ayat       = null;

        if ($materi->isAlQuran()) {
            if (!empty($validated['surat_nomor'])) {
                $suratNomor = (int) $validated['surat_nomor'];
                $suratData  = MushafMadinah::getSurat($suratNomor);
                $namaSurat  = $suratData['nama'] ?? null;
                $maxAyat    = (int) ($suratData['jumlah_ayat'] ?? 286);
                $ayat       = max(1, min($maxAyat, (int) ($validated['ayat'] ?? 1)));
                $targetHalaman = MushafMadinah::estimasiHalaman($suratNomor, $ayat);
            } else {
                $targetHalaman = (int) ($validated['target_halaman'] ?? $validated['halaman'] ?? 1);
            }
            $targetBaris = null;
        } else {
            $rawHal = $validated['halaman'] ?? $validated['target_halaman'] ?? 1;
            $rawBar = $validated['baris'] ?? $validated['target_baris'] ?? 1;
            $maxHal = max(1, (int) $materi->jumlah_halaman);
            $maxBar = max(1, (int) ($materi->jumlah_baris ?: 9999));

            $targetHalaman = max(1, min($maxHal, (int) $rawHal));
            $targetBaris   = max(0, min($maxBar, (int) $rawBar));
        }

        PondokTargetPengajian::create([
            'tanggal'            => $validated['tanggal'],
            'kelas_pengajian_id' => $validated['kelas_pengajian_id'],
            'pondok_materi_id'   => $validated['pondok_materi_id'],
            'surat_nomor'        => $suratNomor,
            'nama_surat'         => $namaSurat,
            'ayat'               => $ayat,
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
            'kelas_pengajian_id' => ['required', 'exists:master_kelas_pengajians,id'],
            'pondok_materi_id'   => ['required', 'exists:pondok_materis,id'],
            'surat_nomor'        => ['nullable', 'integer', 'min:1', 'max:114'],
            'ayat'               => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_halaman'     => ['nullable', 'integer', 'min:0'],
            'target_baris'       => ['nullable', 'integer', 'min:0'],
            'halaman'            => ['nullable', 'integer', 'min:1'],
            'baris'              => ['nullable', 'integer', 'min:0'],
            'keterangan'         => ['nullable', 'string', 'max:1000'],
        ]);

        $materi = PondokMateri::findOrFail($validated['pondok_materi_id']);

        $suratNomor = null;
        $namaSurat  = null;
        $ayat       = null;

        if ($materi->isAlQuran()) {
            if (!empty($validated['surat_nomor'])) {
                $suratNomor = (int) $validated['surat_nomor'];
                $suratData  = MushafMadinah::getSurat($suratNomor);
                $namaSurat  = $suratData['nama'] ?? null;
                $maxAyat    = (int) ($suratData['jumlah_ayat'] ?? 286);
                $ayat       = max(1, min($maxAyat, (int) ($validated['ayat'] ?? 1)));
                $targetHalaman = MushafMadinah::estimasiHalaman($suratNomor, $ayat);
            } else {
                $targetHalaman = (int) ($validated['target_halaman'] ?? $validated['halaman'] ?? $target->target_halaman);
            }
            $targetBaris = null;
        } else {
            $rawHal = $validated['halaman'] ?? $validated['target_halaman'] ?? $target->target_halaman;
            $rawBar = $validated['baris'] ?? $validated['target_baris'] ?? $target->target_baris;
            $maxHal = max(1, (int) $materi->jumlah_halaman);
            $maxBar = max(1, (int) ($materi->jumlah_baris ?: 9999));

            $targetHalaman = max(1, min($maxHal, (int) $rawHal));
            $targetBaris   = max(0, min($maxBar, (int) $rawBar));
        }

        $target->update([
            'tanggal'            => $validated['tanggal'],
            'kelas_pengajian_id' => $validated['kelas_pengajian_id'],
            'pondok_materi_id'   => $validated['pondok_materi_id'],
            'surat_nomor'        => $suratNomor,
            'nama_surat'         => $namaSurat,
            'ayat'               => $ayat,
            'target_halaman'     => $targetHalaman,
            'target_baris'       => $targetBaris,
            'keterangan'         => $validated['keterangan'] ?? null,
        ]);

        // Sinkronkan ulang status ketercapaian pada jurnal yang terhubung dengan target ini
        foreach ($target->jurnals as $jurnal) {
            $newStatus = PondokJurnalMengaji::hitungStatusKetercapaian(
                $materi->kategori,
                $targetHalaman,
                (int) ($targetBaris ?? 0),
                (int) $jurnal->capaian_halaman,
                (int) ($jurnal->capaian_baris ?? 0),
                $suratNomor,
                $ayat,
                $jurnal->capaian_surat_nomor,
                $jurnal->capaian_ayat
            );
            $jurnal->update([
                'pondok_materi_id'    => $materi->id,
                'kelas_pengajian_id'  => $validated['kelas_pengajian_id'],
                'target_surat_nomor'  => $suratNomor,
                'target_nama_surat'   => $namaSurat,
                'target_ayat'         => $ayat,
                'target_halaman'      => $targetHalaman,
                'target_baris'        => $targetBaris,
                'status_ketercapaian' => $newStatus,
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
     * 3. SUBMENU JURNAL MENGAJI (PENCAPAIAN PER KELAS & INDIKATOR KETERCAPAIAN)
     * ====================================================================== */

    public function jurnalIndex(Request $request)
    {
        if (MasterKelasPengajian::count() === 0) {
            MasterKelasPengajian::seedStandar();
        }

        $kelasList    = MasterKelasPengajian::orderBy('id')->get();
        $materiList   = PondokMateri::where('is_active', true)->orderBy('kategori')->orderBy('nama_materi')->get();
        $targetList   = PondokTargetPengajian::with(['kelasPengajian', 'materi'])
            ->orderByDesc('tanggal')
            ->orderBy('kelas_pengajian_id')
            ->get();
        $pengajarList = User::where('is_active', true)->orderBy('name')->get();
        $daftarSurat  = MushafMadinah::daftarSurat();

        $query = PondokJurnalMengaji::with(['kelasPengajian', 'materi', 'targetPengajian', 'pengajar']);

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
            'daftarSurat'
        ));
    }

    public function storeJurnal(Request $request)
    {
        $validated = $request->validate([
            'tanggal'             => ['required', 'date'],
            'kelas_pengajian_id'  => ['required', 'exists:master_kelas_pengajians,id'],
            'pondok_materi_id'    => ['required', 'exists:pondok_materis,id'],
            'target_pengajian_id' => ['nullable', 'exists:pondok_target_pengajians,id'],
            'pengajar_id'         => ['nullable', 'exists:users,id'],
            'nama_pengajar'       => ['nullable', 'string', 'max:150'],
            'target_surat_nomor'  => ['nullable', 'integer', 'min:1', 'max:114'],
            'target_ayat'         => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_halaman'      => ['nullable', 'integer', 'min:0'],
            'target_baris'        => ['nullable', 'integer', 'min:0'],
            'capaian_surat_nomor' => ['nullable', 'integer', 'min:1', 'max:114'],
            'capaian_ayat'        => ['nullable', 'integer', 'min:1', 'max:286'],
            'capaian_halaman'     => ['nullable', 'integer', 'min:0'],
            'capaian_baris'       => ['nullable', 'integer', 'min:0'],
            'catatan'             => ['nullable', 'string', 'max:1000'],
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

        $targetSuratNomor  = null;
        $targetNamaSurat   = null;
        $targetAyat        = null;
        $capaianSuratNomor = null;
        $capaianNamaSurat  = null;
        $capaianAyat       = null;

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
                $targetHalaman = MushafMadinah::estimasiHalaman($targetSuratNomor, $targetAyat);
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
                $capaianHalaman = MushafMadinah::estimasiHalaman($capaianSuratNomor, $capaianAyat);
            } else {
                $capaianHalaman = (int) ($validated['capaian_halaman'] ?? 0);
            }

            $targetBaris  = 0;
            $capaianBaris = 0;
        } else {
            $targetHalaman = $targetModel
                ? (int) $targetModel->target_halaman
                : (int) ($validated['target_halaman'] ?? 0);
            $targetBaris = $targetModel
                ? (int) ($targetModel->target_baris ?? 0)
                : (int) ($validated['target_baris'] ?? 0);

            $capaianHalaman = (int) ($validated['capaian_halaman'] ?? 0);
            $capaianBaris   = (int) ($validated['capaian_baris'] ?? 0);
        }

        $statusKetercapaian = PondokJurnalMengaji::hitungStatusKetercapaian(
            $materi->kategori,
            $targetHalaman,
            $targetBaris,
            $capaianHalaman,
            $capaianBaris,
            $targetSuratNomor,
            $targetAyat,
            $capaianSuratNomor,
            $capaianAyat
        );

        $pengajarUser = !empty($validated['pengajar_id']) ? User::find($validated['pengajar_id']) : Auth::user();
        $namaPengajar = !empty($validated['nama_pengajar'])
            ? trim($validated['nama_pengajar'])
            : ($pengajarUser?->name ?? 'Pengajar Pondok');

        PondokJurnalMengaji::create([
            'tanggal'             => $validated['tanggal'],
            'kelas_pengajian_id'  => $validated['kelas_pengajian_id'],
            'pondok_materi_id'    => $validated['pondok_materi_id'],
            'target_pengajian_id' => $targetModel?->id,
            'pengajar_id'         => $pengajarUser?->id,
            'nama_pengajar'       => $namaPengajar,
            'target_surat_nomor'  => $targetSuratNomor,
            'target_nama_surat'   => $targetNamaSurat,
            'target_ayat'         => $targetAyat,
            'target_halaman'      => $targetHalaman,
            'target_baris'        => $materi->isAlHadist() ? $targetBaris : null,
            'capaian_surat_nomor' => $capaianSuratNomor,
            'capaian_nama_surat'  => $capaianNamaSurat,
            'capaian_ayat'        => $capaianAyat,
            'capaian_halaman'     => $capaianHalaman,
            'capaian_baris'       => $materi->isAlHadist() ? $capaianBaris : null,
            'status_ketercapaian' => $statusKetercapaian,
            'catatan'             => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('pondok.jurnal.index')
            ->with('success', 'Jurnal mengaji berhasil disimpan dengan status ketercapaian: ' . ucfirst($statusKetercapaian) . '.');
    }

    public function updateJurnal(Request $request, PondokJurnalMengaji $jurnal)
    {
        $validated = $request->validate([
            'tanggal'             => ['required', 'date'],
            'kelas_pengajian_id'  => ['required', 'exists:master_kelas_pengajians,id'],
            'pondok_materi_id'    => ['required', 'exists:pondok_materis,id'],
            'target_pengajian_id' => ['nullable', 'exists:pondok_target_pengajians,id'],
            'pengajar_id'         => ['nullable', 'exists:users,id'],
            'nama_pengajar'       => ['nullable', 'string', 'max:150'],
            'target_surat_nomor'  => ['nullable', 'integer', 'min:1', 'max:114'],
            'target_ayat'         => ['nullable', 'integer', 'min:1', 'max:286'],
            'target_halaman'      => ['nullable', 'integer', 'min:0'],
            'target_baris'        => ['nullable', 'integer', 'min:0'],
            'capaian_surat_nomor' => ['nullable', 'integer', 'min:1', 'max:114'],
            'capaian_ayat'        => ['nullable', 'integer', 'min:1', 'max:286'],
            'capaian_halaman'     => ['nullable', 'integer', 'min:0'],
            'capaian_baris'       => ['nullable', 'integer', 'min:0'],
            'catatan'             => ['nullable', 'string', 'max:1000'],
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

        $targetSuratNomor  = null;
        $targetNamaSurat   = null;
        $targetAyat        = null;
        $capaianSuratNomor = null;
        $capaianNamaSurat  = null;
        $capaianAyat       = null;

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
                $targetHalaman = MushafMadinah::estimasiHalaman($targetSuratNomor, $targetAyat);
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
                $capaianHalaman = MushafMadinah::estimasiHalaman($capaianSuratNomor, $capaianAyat);
            } else {
                $capaianHalaman = (int) ($validated['capaian_halaman'] ?? $jurnal->capaian_halaman);
            }

            $targetBaris  = 0;
            $capaianBaris = 0;
        } else {
            $targetHalaman = isset($validated['target_halaman'])
                ? (int) $validated['target_halaman']
                : ($targetModel ? (int) $targetModel->target_halaman : (int) $jurnal->target_halaman);
            $targetBaris = isset($validated['target_baris'])
                ? (int) $validated['target_baris']
                : ($targetModel ? (int) ($targetModel->target_baris ?? 0) : (int) ($jurnal->target_baris ?? 0));

            $capaianHalaman = (int) ($validated['capaian_halaman'] ?? $jurnal->capaian_halaman);
            $capaianBaris   = (int) ($validated['capaian_baris'] ?? $jurnal->capaian_baris);
        }

        $statusKetercapaian = PondokJurnalMengaji::hitungStatusKetercapaian(
            $materi->kategori,
            $targetHalaman,
            $targetBaris,
            $capaianHalaman,
            $capaianBaris,
            $targetSuratNomor,
            $targetAyat,
            $capaianSuratNomor,
            $capaianAyat
        );

        $pengajarUser = !empty($validated['pengajar_id']) ? User::find($validated['pengajar_id']) : $jurnal->pengajar;
        $namaPengajar = !empty($validated['nama_pengajar'])
            ? trim($validated['nama_pengajar'])
            : ($pengajarUser?->name ?? $jurnal->nama_pengajar);

        $jurnal->update([
            'tanggal'             => $validated['tanggal'],
            'kelas_pengajian_id'  => $validated['kelas_pengajian_id'],
            'pondok_materi_id'    => $validated['pondok_materi_id'],
            'target_pengajian_id' => $targetModel?->id,
            'pengajar_id'         => $pengajarUser?->id,
            'nama_pengajar'       => $namaPengajar,
            'target_surat_nomor'  => $targetSuratNomor,
            'target_nama_surat'   => $targetNamaSurat,
            'target_ayat'         => $targetAyat,
            'target_halaman'      => $targetHalaman,
            'target_baris'        => $materi->isAlHadist() ? $targetBaris : null,
            'capaian_surat_nomor' => $capaianSuratNomor,
            'capaian_nama_surat'  => $capaianNamaSurat,
            'capaian_ayat'        => $capaianAyat,
            'capaian_halaman'     => $capaianHalaman,
            'capaian_baris'       => $materi->isAlHadist() ? $capaianBaris : null,
            'status_ketercapaian' => $statusKetercapaian,
            'catatan'             => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('pondok.jurnal.index')
            ->with('success', 'Jurnal mengaji berhasil diperbarui.');
    }

    public function destroyJurnal(PondokJurnalMengaji $jurnal)
    {
        $jurnal->delete();

        return redirect()->route('pondok.jurnal.index')
            ->with('success', 'Data jurnal mengaji berhasil dihapus.');
    }
}
