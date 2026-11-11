<?php

namespace App\Http\Controllers;

use App\Models\MasterKelasPengajian;
use App\Models\PondokJurnalMengaji;
use App\Models\PondokMateri;
use App\Models\PondokTargetPengajian;
use App\Models\User;
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
            'jumlah_halaman' => ['required', 'integer', 'min:1'],
            'jumlah_baris'   => ['nullable', 'integer', 'min:0'],
            'keterangan'     => ['nullable', 'string', 'max:1000'],
        ], [
            'nama_materi.required'    => 'Nama materi wajib diisi.',
            'kategori.required'       => 'Kategori materi (Al-Qur\'an / Al-Hadist) wajib dipilih.',
            'jumlah_halaman.required' => 'Jumlah halaman wajib diisi.',
            'jumlah_halaman.min'      => 'Jumlah halaman minimal 1 halaman.',
        ]);

        if ($validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST && ($validated['jumlah_baris'] === null || $validated['jumlah_baris'] === '')) {
            return back()->withInput()->with('error', 'Untuk kategori Al-Hadist, jumlah baris wajib diisi.');
        }

        $prefix = $validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST ? 'HAD' : 'QUR';
        $kode = !empty($validated['kode_materi'])
            ? strtoupper(trim($validated['kode_materi']))
            : sprintf('%s-%02d', $prefix, PondokMateri::where('kategori', $validated['kategori'])->count() + 1);

        PondokMateri::create([
            'kode_materi'    => $kode,
            'nama_materi'    => trim($validated['nama_materi']),
            'kategori'       => $validated['kategori'],
            'jumlah_halaman' => (int) $validated['jumlah_halaman'],
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
            'jumlah_halaman' => ['required', 'integer', 'min:1'],
            'jumlah_baris'   => ['nullable', 'integer', 'min:0'],
            'keterangan'     => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validated['kategori'] === PondokMateri::KATEGORI_AL_HADIST && ($validated['jumlah_baris'] === null || $validated['jumlah_baris'] === '')) {
            return back()->withInput()->with('error', 'Untuk kategori Al-Hadist, jumlah baris wajib diisi.');
        }

        $materi->update([
            'kode_materi'    => !empty($validated['kode_materi']) ? strtoupper(trim($validated['kode_materi'])) : $materi->kode_materi,
            'nama_materi'    => trim($validated['nama_materi']),
            'kategori'       => $validated['kategori'],
            'jumlah_halaman' => (int) $validated['jumlah_halaman'],
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

        $kelasList  = MasterKelasPengajian::orderBy('id')->get();
        $materiList = PondokMateri::where('is_active', true)->orderBy('kategori')->orderBy('nama_materi')->get();

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
                  ->orWhereHas('materi', fn ($qm) => $qm->where('nama_materi', 'like', "%{$s}%"))
                  ->orWhereHas('kelasPengajian', fn ($qk) => $qk->where('nama_kelas', 'like', "%{$s}%"));
            });
        }

        $targets = $query->orderByDesc('tanggal')->orderBy('kelas_pengajian_id')->orderByDesc('id')->get();

        return view('pondok.target_pengajian', compact(
            'targets',
            'kelasList',
            'materiList'
        ));
    }

    public function storeTarget(Request $request)
    {
        $validated = $request->validate([
            'tanggal'            => ['required', 'date'],
            'kelas_pengajian_id' => ['required', 'exists:master_kelas_pengajians,id'],
            'pondok_materi_id'   => ['required', 'exists:pondok_materis,id'],
            'target_halaman'     => ['required', 'integer', 'min:0'],
            'target_baris'       => ['nullable', 'integer', 'min:0'],
            'halaman_awal'       => ['nullable', 'integer', 'min:1'],
            'halaman_akhir'      => ['nullable', 'integer', 'min:1'],
            'keterangan'         => ['nullable', 'string', 'max:1000'],
        ], [
            'tanggal.required'            => 'Tanggal target pengajian wajib diisi.',
            'kelas_pengajian_id.required' => 'Kelas pengajian wajib dipilih.',
            'pondok_materi_id.required'   => 'Materi pondok wajib dipilih.',
            'target_halaman.required'     => 'Target halaman wajib diisi.',
        ]);

        $materi = PondokMateri::findOrFail($validated['pondok_materi_id']);
        $targetBaris = $materi->isAlHadist() ? (int) ($validated['target_baris'] ?? 0) : null;

        PondokTargetPengajian::create([
            'tanggal'            => $validated['tanggal'],
            'kelas_pengajian_id' => $validated['kelas_pengajian_id'],
            'pondok_materi_id'   => $validated['pondok_materi_id'],
            'target_halaman'     => (int) $validated['target_halaman'],
            'target_baris'       => $targetBaris,
            'halaman_awal'       => $validated['halaman_awal'] ?? null,
            'halaman_akhir'      => $validated['halaman_akhir'] ?? null,
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
            'target_halaman'     => ['required', 'integer', 'min:0'],
            'target_baris'       => ['nullable', 'integer', 'min:0'],
            'halaman_awal'       => ['nullable', 'integer', 'min:1'],
            'halaman_akhir'      => ['nullable', 'integer', 'min:1'],
            'keterangan'         => ['nullable', 'string', 'max:1000'],
        ]);

        $materi = PondokMateri::findOrFail($validated['pondok_materi_id']);
        $targetHalaman = (int) $validated['target_halaman'];
        $targetBaris   = $materi->isAlHadist() ? (int) ($validated['target_baris'] ?? 0) : null;

        $target->update([
            'tanggal'            => $validated['tanggal'],
            'kelas_pengajian_id' => $validated['kelas_pengajian_id'],
            'pondok_materi_id'   => $validated['pondok_materi_id'],
            'target_halaman'     => $targetHalaman,
            'target_baris'       => $targetBaris,
            'halaman_awal'       => $validated['halaman_awal'] ?? null,
            'halaman_akhir'      => $validated['halaman_akhir'] ?? null,
            'keterangan'         => $validated['keterangan'] ?? null,
        ]);

        // Sinkronkan ulang status ketercapaian pada jurnal yang terhubung dengan target ini
        foreach ($target->jurnals as $jurnal) {
            $newStatus = PondokJurnalMengaji::hitungStatusKetercapaian(
                $materi->kategori,
                $targetHalaman,
                (int) ($targetBaris ?? 0),
                (int) $jurnal->capaian_halaman,
                (int) ($jurnal->capaian_baris ?? 0)
            );
            $jurnal->update([
                'pondok_materi_id'    => $materi->id,
                'kelas_pengajian_id'  => $validated['kelas_pengajian_id'],
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

        $kelasList   = MasterKelasPengajian::orderBy('id')->get();
        $materiList  = PondokMateri::where('is_active', true)->orderBy('kategori')->orderBy('nama_materi')->get();
        $targetList  = PondokTargetPengajian::with(['kelasPengajian', 'materi'])
            ->orderByDesc('tanggal')
            ->orderBy('kelas_pengajian_id')
            ->get();
        $pengajarList = User::where('is_active', true)->orderBy('name')->get();

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
            'pengajarList'
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
            'target_halaman'      => ['nullable', 'integer', 'min:0'],
            'target_baris'        => ['nullable', 'integer', 'min:0'],
            'capaian_halaman'     => ['required', 'integer', 'min:0'],
            'capaian_baris'       => ['nullable', 'integer', 'min:0'],
            'halaman_awal'        => ['nullable', 'integer', 'min:1'],
            'halaman_akhir'       => ['nullable', 'integer', 'min:1'],
            'catatan'             => ['nullable', 'string', 'max:1000'],
        ], [
            'tanggal.required'            => 'Tanggal jurnal wajib diisi.',
            'kelas_pengajian_id.required' => 'Kelas pengajian wajib dipilih.',
            'pondok_materi_id.required'   => 'Materi pengajian wajib dipilih.',
            'capaian_halaman.required'    => 'Pencapaian halaman wajib diisi.',
        ]);

        $materi = PondokMateri::findOrFail($validated['pondok_materi_id']);

        // Cari target pengajian secara otomatis jika tidak dipilih langsung namun ada pada tanggal, kelas & materi yang sama
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

        $targetHalaman = $targetModel
            ? (int) $targetModel->target_halaman
            : (int) ($validated['target_halaman'] ?? 0);

        $targetBaris = $materi->isAlHadist()
            ? ($targetModel ? (int) ($targetModel->target_baris ?? 0) : (int) ($validated['target_baris'] ?? 0))
            : 0;

        $capaianHalaman = (int) $validated['capaian_halaman'];
        $capaianBaris   = $materi->isAlHadist() ? (int) ($validated['capaian_baris'] ?? 0) : 0;

        $statusKetercapaian = PondokJurnalMengaji::hitungStatusKetercapaian(
            $materi->kategori,
            $targetHalaman,
            $targetBaris,
            $capaianHalaman,
            $capaianBaris
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
            'target_halaman'      => $targetHalaman,
            'target_baris'        => $materi->isAlHadist() ? $targetBaris : null,
            'capaian_halaman'     => $capaianHalaman,
            'capaian_baris'       => $materi->isAlHadist() ? $capaianBaris : null,
            'halaman_awal'        => $validated['halaman_awal'] ?? null,
            'halaman_akhir'       => $validated['halaman_akhir'] ?? null,
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
            'target_halaman'      => ['nullable', 'integer', 'min:0'],
            'target_baris'        => ['nullable', 'integer', 'min:0'],
            'capaian_halaman'     => ['required', 'integer', 'min:0'],
            'capaian_baris'       => ['nullable', 'integer', 'min:0'],
            'halaman_awal'        => ['nullable', 'integer', 'min:1'],
            'halaman_akhir'       => ['nullable', 'integer', 'min:1'],
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

        $targetHalaman = isset($validated['target_halaman']) && $validated['target_halaman'] !== null
            ? (int) $validated['target_halaman']
            : ($targetModel ? (int) $targetModel->target_halaman : (int) $jurnal->target_halaman);

        $targetBaris = $materi->isAlHadist()
            ? (isset($validated['target_baris']) && $validated['target_baris'] !== null
                ? (int) $validated['target_baris']
                : ($targetModel ? (int) ($targetModel->target_baris ?? 0) : (int) ($jurnal->target_baris ?? 0)))
            : 0;

        $capaianHalaman = (int) $validated['capaian_halaman'];
        $capaianBaris   = $materi->isAlHadist() ? (int) ($validated['capaian_baris'] ?? 0) : 0;

        $statusKetercapaian = PondokJurnalMengaji::hitungStatusKetercapaian(
            $materi->kategori,
            $targetHalaman,
            $targetBaris,
            $capaianHalaman,
            $capaianBaris
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
            'target_halaman'      => $targetHalaman,
            'target_baris'        => $materi->isAlHadist() ? $targetBaris : null,
            'capaian_halaman'     => $capaianHalaman,
            'capaian_baris'       => $materi->isAlHadist() ? $capaianBaris : null,
            'halaman_awal'        => $validated['halaman_awal'] ?? null,
            'halaman_akhir'       => $validated['halaman_akhir'] ?? null,
            'status_ketercapaian' => $statusKetercapaian,
            'catatan'             => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('pondok.jurnal.index')
            ->with('success', 'Jurnal mengaji berhasil diperbarui (Indikator: ' . ucfirst($statusKetercapaian) . ').');
    }

    public function destroyJurnal(PondokJurnalMengaji $jurnal)
    {
        $jurnal->delete();

        return redirect()->route('pondok.jurnal.index')
            ->with('success', 'Data jurnal mengaji berhasil dihapus.');
    }
}

