<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\PondokCapaianMateri;
use App\Models\PondokMateri;
use App\Models\Siswa;
use App\Support\MushafMadinah;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PondokCapaianMateriController extends Controller
{
    protected function scopedFormal(): ?Lembaga
    {
        return Lembaga::activeScopedFormal(auth()->user());
    }

    protected function ensureSiswaInRoom(Siswa $siswa): void
    {
        $scoped = $this->scopedFormal();
        if (!$scoped) {
            return;
        }

        if (!Siswa::forLembaga($scoped->kode)->whereKey($siswa->id)->exists()) {
            abort(403, 'Anda tidak memiliki akses ke data siswa lembaga lain.');
        }
    }

    protected function activeSiswaQuery()
    {
        $scoped = $this->scopedFormal();
        $query = Siswa::query()
            ->with('kelasPengajian')
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhereNotIn('status', ['keluar', 'lulus', 'alumni', 'nonaktif', 'ditolak', 'Ditolak', 'Lulus', 'mutasi', 'Mutasi']);
            });

        if ($scoped) {
            $query->forLembaga($scoped->kode);
        }

        return $query;
    }

    protected function activeHadistMateris()
    {
        return PondokMateri::query()
            ->where('kategori', PondokMateri::KATEGORI_AL_HADIST)
            ->where('is_active', true)
            ->orderBy('id')
            ->get();
    }

    protected function normalizePages(?array $pages, int $awal, int $akhir): array
    {
        if (!is_array($pages)) {
            return [];
        }

        $clean = [];
        foreach ($pages as $p) {
            $num = (int) $p;
            if ($num >= $awal && $num <= $akhir) {
                $clean[$num] = $num;
            }
        }
        ksort($clean);

        return array_values($clean);
    }

    protected function calcPercent(int $done, int $total): float
    {
        if ($total <= 0 || $done <= 0) {
            return 0.0;
        }

        return round(min(100, ($done / $total) * 100), 1);
    }

    protected function formatPercent(float $val): string
    {
        if (abs($val - round($val)) < 0.05) {
            return (string) (int) round($val) . '%';
        }

        return number_format($val, 1, ',', '.') . '%';
    }

    /**
     * Daftar siswa beserta persentase capaian materi Al-Qur'an dan Al-Hadist secara terpisah.
     */
    public function index(Request $request)
    {
        $baseQuery = $this->activeSiswaQuery();

        $kelasList = (clone $baseQuery)
            ->whereNotNull('kelas')
            ->where('kelas', '!=', '')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        if ($request->filled('kelas')) {
            $baseQuery->where('kelas', trim((string) $request->kelas));
        }

        if ($request->filled('search')) {
            $s = trim((string) $request->search);
            $baseQuery->where(function ($q) use ($s) {
                $q->where('nama_lengkap', 'like', "%{$s}%")
                  ->orWhere('kelas', 'like', "%{$s}%")
                  ->orWhere('kamar', 'like', "%{$s}%");
            });
        }

        $siswas = $baseQuery->orderBy('nama_lengkap')->paginate(20)->withQueryString();
        $siswaIds = $siswas->pluck('id')->all();

        $juzList = MushafMadinah::daftarJuz();
        $totalQuran = MushafMadinah::TOTAL_HALAMAN;
        $hadistMateris = $this->activeHadistMateris();
        $hadistMap = $hadistMateris->keyBy('id');
        $totalHadist = (int) $hadistMateris->sum('jumlah_halaman');

        $capaianRows = empty($siswaIds)
            ? collect()
            : PondokCapaianMateri::whereIn('siswa_id', $siswaIds)->get()->groupBy('siswa_id');

        $summaryMap = [];
        foreach ($siswas as $siswa) {
            $rows = $capaianRows->get($siswa->id, collect());
            $quranDone = 0;
            $hadistDone = 0;

            foreach ($rows as $row) {
                if ($row->kategori === PondokMateri::KATEGORI_AL_QURAN && $row->juz && isset($juzList[$row->juz])) {
                    $meta = $juzList[$row->juz];
                    $quranDone += count($this->normalizePages($row->halaman_selesai, $meta['halaman_awal'], $meta['halaman_akhir']));
                } elseif ($row->kategori === PondokMateri::KATEGORI_AL_HADIST && $row->pondok_materi_id && $hadistMap->has($row->pondok_materi_id)) {
                    $mat = $hadistMap->get($row->pondok_materi_id);
                    $hadistDone += count($this->normalizePages($row->halaman_selesai, 1, max(1, (int) $mat->jumlah_halaman)));
                }
            }

            $quranPersen = $this->calcPercent($quranDone, $totalQuran);
            $hadistPersen = $this->calcPercent($hadistDone, $totalHadist);

            $summaryMap[$siswa->id] = [
                'quran_selesai'       => $quranDone,
                'quran_total'         => $totalQuran,
                'quran_persen'        => $quranPersen,
                'quran_persen_label'  => $this->formatPercent($quranPersen),
                'hadist_selesai'      => $hadistDone,
                'hadist_total'        => $totalHadist,
                'hadist_persen'       => $hadistPersen,
                'hadist_persen_label' => $this->formatPercent($hadistPersen),
            ];
        }

        $canEdit = !auth()->check()
            || auth()->user()->canDo('capaian_materi', 'edit')
            || auth()->user()->canDo('capaian_materi', 'create');

        return view('pondok.capaian_index', compact(
            'siswas',
            'kelasList',
            'summaryMap',
            'canEdit'
        ));
    }

    /**
     * Halaman detail & matriks lingkaran capaian materi per siswa.
     */
    public function show(Request $request, Siswa $siswa)
    {
        $this->ensureSiswaInRoom($siswa);
        $siswa->load('kelasPengajian');

        $canEdit = !auth()->check()
            || auth()->user()->canDo('capaian_materi', 'edit')
            || auth()->user()->canDo('capaian_materi', 'create');
        $isEditMode = $canEdit && $request->query('mode') !== 'lihat';

        $activeTab = $request->query('tab') === 'hadist' ? 'hadist' : 'quran';

        $existingByScope = PondokCapaianMateri::where('siswa_id', $siswa->id)
            ->get()
            ->keyBy('scope_key');

        // 1. Susun Al-Qur'an per Juz (Juz 1 - Juz 30)
        $juzItems = [];
        $quranTotalDone = 0;
        $quranTotalPages = MushafMadinah::TOTAL_HALAMAN;

        foreach (MushafMadinah::daftarJuz() as $juzNum => $meta) {
            $scopeKey = PondokCapaianMateri::scopeKeyForQuranJuz($juzNum);
            $record = $existingByScope->get($scopeKey);
            $donePages = $this->normalizePages(
                $record ? $record->halaman_selesai : [],
                $meta['halaman_awal'],
                $meta['halaman_akhir']
            );
            $doneCount = count($donePages);
            $totalCount = $meta['jumlah_halaman'];
            $persen = $this->calcPercent($doneCount, $totalCount);
            $quranTotalDone += $doneCount;

            $juzItems[] = [
                'juz'             => $juzNum,
                'label'           => $meta['label'],
                'halaman_awal'    => $meta['halaman_awal'],
                'halaman_akhir'   => $meta['halaman_akhir'],
                'jumlah_halaman'  => $totalCount,
                'halaman_selesai' => $donePages,
                'jumlah_selesai'  => $doneCount,
                'persen'          => $persen,
                'persen_label'    => $this->formatPercent($persen),
            ];
        }

        $quranPersen = $this->calcPercent($quranTotalDone, $quranTotalPages);
        $quranSummary = [
            'selesai'      => $quranTotalDone,
            'total'        => $quranTotalPages,
            'persen'       => $quranPersen,
            'persen_label' => $this->formatPercent($quranPersen),
        ];

        // 2. Susun Al-Hadist sesuai daftar kitab yang didaftarkan di aplikasi
        $hadistMateris = $this->activeHadistMateris();
        $hadistItems = [];
        $hadistTotalDone = 0;
        $hadistTotalPages = 0;

        foreach ($hadistMateris as $materi) {
            $totalCount = max(1, (int) $materi->jumlah_halaman);
            $scopeKey = PondokCapaianMateri::scopeKeyForHadist($materi->id);
            $record = $existingByScope->get($scopeKey);
            $donePages = $this->normalizePages(
                $record ? $record->halaman_selesai : [],
                1,
                $totalCount
            );
            $doneCount = count($donePages);
            $persen = $this->calcPercent($doneCount, $totalCount);

            $hadistTotalDone += $doneCount;
            $hadistTotalPages += $totalCount;

            $hadistItems[] = [
                'id'              => $materi->id,
                'kode'            => $materi->kode_materi,
                'nama'            => $materi->nama_materi,
                'jumlah_halaman'  => $totalCount,
                'halaman_selesai' => $donePages,
                'jumlah_selesai'  => $doneCount,
                'persen'          => $persen,
                'persen_label'    => $this->formatPercent($persen),
            ];
        }

        $hadistPersen = $this->calcPercent($hadistTotalDone, $hadistTotalPages);
        $hadistSummary = [
            'selesai'      => $hadistTotalDone,
            'total'        => $hadistTotalPages,
            'persen'       => $hadistPersen,
            'persen_label' => $this->formatPercent($hadistPersen),
        ];

        return view('pondok.capaian_show', compact(
            'siswa',
            'canEdit',
            'isEditMode',
            'activeTab',
            'juzItems',
            'quranSummary',
            'hadistItems',
            'hadistSummary'
        ));
    }

    /**
     * Toggle status halaman atau seluruh halaman pada 1 Juz Al-Qur'an / 1 Kitab Al-Hadist.
     */
    public function toggle(Request $request, Siswa $siswa): JsonResponse
    {
        $this->ensureSiswaInRoom($siswa);

        $validated = $request->validate([
            'kategori'         => ['required', Rule::in([PondokMateri::KATEGORI_AL_QURAN, PondokMateri::KATEGORI_AL_HADIST])],
            'juz'              => ['nullable', 'integer', 'min:1', 'max:30'],
            'pondok_materi_id' => ['nullable', 'integer', 'exists:pondok_materis,id'],
            'action'           => ['nullable', Rule::in(['toggle_page', 'select_all', 'clear_all'])],
            'halaman'          => ['nullable', 'integer', 'min:1'],
        ]);

        $kategori = $validated['kategori'];
        $action = $validated['action'] ?? 'toggle_page';

        if ($kategori === PondokMateri::KATEGORI_AL_QURAN) {
            $juzNum = (int) ($validated['juz'] ?? 0);
            $meta = MushafMadinah::getJuz($juzNum);
            if (!$meta) {
                return response()->json(['ok' => false, 'message' => 'Juz tidak valid.'], 422);
            }
            $awal = $meta['halaman_awal'];
            $akhir = $meta['halaman_akhir'];
            $totalItem = $meta['jumlah_halaman'];
            $scopeKey = PondokCapaianMateri::scopeKeyForQuranJuz($juzNum);
            $materiId = null;
        } else {
            $materi = PondokMateri::where('id', (int) ($validated['pondok_materi_id'] ?? 0))
                ->where('kategori', PondokMateri::KATEGORI_AL_HADIST)
                ->first();
            if (!$materi) {
                return response()->json(['ok' => false, 'message' => 'Materi Al-Hadist tidak ditemukan.'], 422);
            }
            $awal = 1;
            $akhir = max(1, (int) $materi->jumlah_halaman);
            $totalItem = $akhir;
            $scopeKey = PondokCapaianMateri::scopeKeyForHadist($materi->id);
            $materiId = $materi->id;
            $juzNum = null;
        }

        if ($action === 'toggle_page') {
            $halaman = (int) ($validated['halaman'] ?? 0);
            if ($halaman < $awal || $halaman > $akhir) {
                return response()->json(['ok' => false, 'message' => 'Nomor halaman berada di luar rentang materi.'], 422);
            }
        }

        $donePages = DB::transaction(function () use ($siswa, $scopeKey, $kategori, $materiId, $juzNum, $action, $validated, $awal, $akhir) {
            $record = PondokCapaianMateri::where('siswa_id', $siswa->id)
                ->where('scope_key', $scopeKey)
                ->lockForUpdate()
                ->first();

            $current = $this->normalizePages($record ? $record->halaman_selesai : [], $awal, $akhir);

            if ($action === 'select_all') {
                $next = range($awal, $akhir);
            } elseif ($action === 'clear_all') {
                $next = [];
            } else {
                $page = (int) $validated['halaman'];
                $lookup = array_flip($current);
                if (isset($lookup[$page])) {
                    unset($lookup[$page]);
                } else {
                    $lookup[$page] = true;
                }
                $next = array_keys($lookup);
                sort($next, SORT_NUMERIC);
            }

            if ($record) {
                $record->update([
                    'halaman_selesai' => $next,
                    'jumlah_selesai'  => count($next),
                    'updated_by'      => Auth::id(),
                ]);
            } else {
                PondokCapaianMateri::create([
                    'siswa_id'         => $siswa->id,
                    'scope_key'        => $scopeKey,
                    'kategori'         => $kategori,
                    'pondok_materi_id' => $materiId,
                    'juz'              => $juzNum,
                    'halaman_selesai'  => $next,
                    'jumlah_selesai'   => count($next),
                    'updated_by'       => Auth::id(),
                ]);
            }

            return $next;
        });

        // Hitung ulang total keseluruhan untuk Al-Qur'an dan Al-Hadist secara terpisah
        $allRecords = PondokCapaianMateri::where('siswa_id', $siswa->id)->get();
        $juzList = MushafMadinah::daftarJuz();
        $hadistMap = $this->activeHadistMateris()->keyBy('id');

        $quranDone = 0;
        $hadistDone = 0;
        foreach ($allRecords as $row) {
            if ($row->kategori === PondokMateri::KATEGORI_AL_QURAN && $row->juz && isset($juzList[$row->juz])) {
                $m = $juzList[$row->juz];
                $quranDone += count($this->normalizePages($row->halaman_selesai, $m['halaman_awal'], $m['halaman_akhir']));
            } elseif ($row->kategori === PondokMateri::KATEGORI_AL_HADIST && $row->pondok_materi_id && $hadistMap->has($row->pondok_materi_id)) {
                $mat = $hadistMap->get($row->pondok_materi_id);
                $hadistDone += count($this->normalizePages($row->halaman_selesai, 1, max(1, (int) $mat->jumlah_halaman)));
            }
        }

        $quranTotal = MushafMadinah::TOTAL_HALAMAN;
        $hadistTotal = (int) $hadistMap->sum('jumlah_halaman');
        $itemDone = count($donePages);

        $itemPersen = $this->calcPercent($itemDone, $totalItem);
        $quranPersen = $this->calcPercent($quranDone, $quranTotal);
        $hadistPersen = $this->calcPercent($hadistDone, $hadistTotal);

        return response()->json([
            'ok'   => true,
            'item' => [
                'selesai'         => $itemDone,
                'total'           => $totalItem,
                'persen'          => $itemPersen,
                'persen_label'    => $this->formatPercent($itemPersen),
                'halaman_selesai' => $donePages,
            ],
            'quran' => [
                'selesai'      => $quranDone,
                'total'        => $quranTotal,
                'persen'       => $quranPersen,
                'persen_label' => $this->formatPercent($quranPersen),
            ],
            'hadist' => [
                'selesai'      => $hadistDone,
                'total'        => $hadistTotal,
                'persen'       => $hadistPersen,
                'persen_label' => $this->formatPercent($hadistPersen),
            ],
        ]);
    }
}
