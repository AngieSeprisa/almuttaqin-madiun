<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanGelombang;
use App\Models\PenerimaanSantri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PenerimaanSantriController extends Controller
{
    public function index(Request $request)
    {
        $activeRoom = session('active_lembaga');

        $query = PenerimaanSantri::query()
            ->with(['gelombangs' => fn ($q) => $q->withCount('pendaftarans')])
            ->withCount([
                'pendaftarans',
                'pendaftarans as pending_count' => fn ($q) => $q->where('status_pendaftaran', 'Pending'),
                'pendaftarans as lulus_count' => fn ($q) => $q->where('status_pendaftaran', 'Lulus Seleksi'),
                'pendaftarans as diterima_count' => fn ($q) => $q->where('status_pendaftaran', 'Diterima'),
            ])
            ->latest();

        if ($activeRoom === 'smp') {
            $query->whereIn('jenjang', ['SMP', 'Semua']);
        } elseif ($activeRoom === 'sma') {
            $query->whereIn('jenjang', ['SMA', 'Semua']);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('tahun_ajaran', 'like', "%{$search}%");
            });
        }

        $penerimaans = $query->paginate(12)->withQueryString();

        $allEvents = PenerimaanSantri::query();
        if ($activeRoom === 'smp') {
            $allEvents->whereIn('jenjang', ['SMP', 'Semua']);
        } elseif ($activeRoom === 'sma') {
            $allEvents->whereIn('jenjang', ['SMA', 'Semua']);
        }

        $stats = [
            'total_event' => (clone $allEvents)->count(),
            'aktif'       => (clone $allEvents)->where('is_active', true)->count(),
            'tutup'       => (clone $allEvents)->where('is_active', false)->count(),
            'pendaftar'   => (clone $allEvents)->withCount('pendaftarans')->get()->sum('pendaftarans_count'),
        ];

        return view('penerimaan.index', compact('penerimaans', 'stats', 'activeRoom'));
    }

    public function create()
    {
        $activeRoom = session('active_lembaga');
        $defaultJenjang = match ($activeRoom) {
            'smp' => 'SMP',
            'sma' => 'SMA',
            default => 'Semua',
        };

        $penerimaan = new PenerimaanSantri([
            'tahun_ajaran'   => date('Y') . '/' . (date('Y') + 1),
            'jenjang'        => $defaultJenjang,
            'is_active'      => true,
            'syarat_dokumen' => PenerimaanSantri::DOKUMEN_DEFAULTS,
        ]);

        return view('penerimaan.form', [
            'penerimaan'     => $penerimaan,
            'isEdit'         => false,
            'dokumenMeta'    => PenerimaanSantri::DOKUMEN_META,
            'jenjangOptions' => PenerimaanSantri::JENJANG_OPTIONS,
            'gelombangs'     => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateEvent($request);

        $penerimaan = DB::transaction(function () use ($validated, $request) {
            $event = PenerimaanSantri::create([
                'judul'          => $validated['judul'],
                'slug'           => $validated['slug'],
                'tahun_ajaran'   => $validated['tahun_ajaran'],
                'jenjang'        => $validated['jenjang'],
                'kontak_wa'      => $validated['kontak_wa'] ?? null,
                'jadwal_seleksi' => $validated['jadwal_seleksi'] ?? null,
                'lokasi_seleksi' => $validated['lokasi_seleksi'] ?? null,
                'syarat_dokumen' => $this->normalizeDokumen($validated['syarat_dokumen'] ?? []),
                'catatan_syarat' => $validated['catatan_syarat'] ?? null,
                'is_active'      => $request->boolean('is_active', true),
                'created_by'     => auth()->id(),
            ]);

            $this->syncGelombangs($event, $validated['gelombangs'] ?? []);

            return $event;
        });

        return redirect()->route('penerimaan.index')
            ->with('success', 'Link penerimaan "' . $penerimaan->judul . '" berhasil dibuat.');
    }

    public function edit($id)
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')->findOrFail($id);

        return view('penerimaan.form', [
            'penerimaan'     => $penerimaan,
            'isEdit'         => true,
            'dokumenMeta'    => PenerimaanSantri::DOKUMEN_META,
            'jenjangOptions' => PenerimaanSantri::JENJANG_OPTIONS,
            'gelombangs'     => $penerimaan->gelombangs,
        ]);
    }

    public function update(Request $request, $id)
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')->findOrFail($id);
        $validated = $this->validateEvent($request, $penerimaan->id);

        DB::transaction(function () use ($penerimaan, $validated, $request) {
            $penerimaan->update([
                'judul'          => $validated['judul'],
                'slug'           => $validated['slug'],
                'tahun_ajaran'   => $validated['tahun_ajaran'],
                'jenjang'        => $validated['jenjang'],
                'kontak_wa'      => $validated['kontak_wa'] ?? null,
                'jadwal_seleksi' => $validated['jadwal_seleksi'] ?? null,
                'lokasi_seleksi' => $validated['lokasi_seleksi'] ?? null,
                'syarat_dokumen' => $this->normalizeDokumen($validated['syarat_dokumen'] ?? []),
                'catatan_syarat' => $validated['catatan_syarat'] ?? null,
                'is_active'      => $request->boolean('is_active'),
            ]);

            $this->syncGelombangs($penerimaan, $validated['gelombangs'] ?? []);
        });

        return redirect()->route('penerimaan.index')
            ->with('success', 'Link penerimaan "' . $penerimaan->judul . '" berhasil diperbarui.');
    }

    public function toggleStatus($id)
    {
        $penerimaan = PenerimaanSantri::findOrFail($id);
        $penerimaan->update([
            'is_active' => !$penerimaan->is_active,
        ]);

        $label = $penerimaan->is_active ? 'dibuka' : 'ditutup';

        return redirect()->back()
            ->with('success', 'Penerimaan "' . $penerimaan->judul . '" berhasil ' . $label . '.');
    }

    public function destroy($id)
    {
        $penerimaan = PenerimaanSantri::findOrFail($id);
        $judul = $penerimaan->judul;
        $penerimaan->delete();

        return redirect()->route('penerimaan.index')
            ->with('success', 'Link penerimaan "' . $judul . '" berhasil dihapus.');
    }

    protected function validateEvent(Request $request, ?int $ignoreId = null): array
    {
        $rawSlug = trim((string) $request->input('slug', ''));
        $normalizedSlug = Str::slug($rawSlug !== '' ? $rawSlug : (string) $request->input('judul', ''));

        $rawGelombangs = $request->input('gelombangs', []);
        $filteredGelombangs = [];
        if (is_array($rawGelombangs)) {
            foreach ($rawGelombangs as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $hasAny = filled($row['nama_gelombang'] ?? null)
                    || filled($row['tanggal_mulai'] ?? null)
                    || filled($row['tanggal_selesai'] ?? null)
                    || filled($row['kuota'] ?? null);
                if ($hasAny) {
                    $filteredGelombangs[] = [
                        'id'              => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                        'nama_gelombang'  => trim((string) ($row['nama_gelombang'] ?? '')),
                        'tanggal_mulai'   => $row['tanggal_mulai'] ?? null,
                        'tanggal_selesai' => $row['tanggal_selesai'] ?? null,
                        'kuota'           => filled($row['kuota'] ?? null) ? (int) $row['kuota'] : null,
                    ];
                }
            }
        }

        $request->merge([
            'slug'       => $normalizedSlug,
            'gelombangs' => $filteredGelombangs,
        ]);

        return $request->validate([
            'judul'                        => 'required|string|max:255',
            'slug'                         => [
                'required',
                'string',
                'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('penerimaan_santris', 'slug')->ignore($ignoreId),
            ],
            'tahun_ajaran'                 => 'required|string|max:30',
            'jenjang'                      => 'required|in:Semua,SMP,SMA,Reguler',
            'kontak_wa'                    => 'nullable|string|max:30',
            'jadwal_seleksi'               => 'nullable|string|max:255',
            'lokasi_seleksi'               => 'nullable|string|max:255',
            'syarat_dokumen'               => 'nullable|array',
            'syarat_dokumen.*'             => 'in:wajib,opsional,tidak',
            'catatan_syarat'               => 'nullable|string|max:5000',
            'gelombangs'                   => 'nullable|array',
            'gelombangs.*.id'              => 'nullable|integer',
            'gelombangs.*.nama_gelombang'  => 'required|string|max:100',
            'gelombangs.*.tanggal_mulai'   => 'required|date',
            'gelombangs.*.tanggal_selesai' => 'required|date|after_or_equal:gelombangs.*.tanggal_mulai',
            'gelombangs.*.kuota'           => 'nullable|integer|min:1',
        ], [
            'judul.required'                        => 'Judul pendaftaran wajib diisi.',
            'slug.required'                         => 'Slug wajib diisi.',
            'slug.unique'                           => 'Slug sudah digunakan oleh penerimaan lain.',
            'slug.regex'                            => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung (-).',
            'tahun_ajaran.required'                 => 'Tahun pendaftaran wajib diisi.',
            'gelombangs.*.nama_gelombang.required'  => 'Nama gelombang wajib diisi.',
            'gelombangs.*.tanggal_mulai.required'   => 'Tanggal mulai gelombang wajib diisi.',
            'gelombangs.*.tanggal_selesai.required' => 'Tanggal selesai gelombang wajib diisi.',
            'gelombangs.*.tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ]);
    }

    protected function normalizeDokumen(array $input): array
    {
        $result = [];
        foreach (PenerimaanSantri::DOKUMEN_DEFAULTS as $key => $default) {
            $val = $input[$key] ?? $default;
            $result[$key] = in_array($val, ['wajib', 'opsional', 'tidak'], true) ? $val : $default;
        }

        return $result;
    }

    protected function syncGelombangs(PenerimaanSantri $event, array $rows): void
    {
        $existingIds = $event->gelombangs()->pluck('id')->all();
        $keptIds = [];

        foreach ($rows as $index => $row) {
            $payload = [
                'nama_gelombang'  => $row['nama_gelombang'],
                'tanggal_mulai'   => $row['tanggal_mulai'],
                'tanggal_selesai' => $row['tanggal_selesai'],
                'kuota'           => $row['kuota'],
                'urutan'          => $index + 1,
            ];

            $rowId = $row['id'] ?? null;
            if ($rowId && in_array($rowId, $existingIds, true)) {
                PenerimaanGelombang::where('id', $rowId)
                    ->where('penerimaan_santri_id', $event->id)
                    ->update($payload);
                $keptIds[] = $rowId;
            } else {
                $created = $event->gelombangs()->create($payload);
                $keptIds[] = $created->id;
            }
        }

        $toDelete = array_diff($existingIds, $keptIds);
        if (!empty($toDelete)) {
            PenerimaanGelombang::where('penerimaan_santri_id', $event->id)
                ->whereIn('id', $toDelete)
                ->delete();
        }
    }
}
