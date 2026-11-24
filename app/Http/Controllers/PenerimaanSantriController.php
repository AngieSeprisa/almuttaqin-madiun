<?php

namespace App\Http\Controllers;

use App\Models\Lembaga;
use App\Models\PenerimaanGelombang;
use App\Models\PenerimaanSantri;
use App\Support\SpmbFormSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PenerimaanSantriController extends Controller
{
    protected function ensureEventInActiveRoom(PenerimaanSantri $event): void
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        if (!$scopedFormal) {
            return;
        }

        $q = PenerimaanSantri::whereKey($event->id);
        $this->applyRoomScope($q, $scopedFormal->kode);
        if (!$q->exists()) {
            abort(403, 'Anda tidak memiliki akses ke link penerimaan lembaga lain.');
        }
    }

    public function index(Request $request)
    {
        $scopedFormal = Lembaga::activeScopedFormal(auth()->user());
        $activeRoom = $scopedFormal ? $scopedFormal->kode : session('active_lembaga');

        $pendaftarScope = fn ($q) => $q->forLembaga($activeRoom);

        $query = PenerimaanSantri::query()
            ->with(['gelombangs'])
            ->withCount([
                'pendaftarans' => $pendaftarScope,
                'pendaftarans as pending_count' => fn ($q) => $q->forLembaga($activeRoom)->where('status_pendaftaran', 'Pending'),
                'pendaftarans as lulus_count' => fn ($q) => $q->forLembaga($activeRoom)->where('status_pendaftaran', 'Lulus Seleksi'),
                'pendaftarans as diterima_count' => fn ($q) => $q->forLembaga($activeRoom)->where('status_pendaftaran', 'Diterima'),
            ])
            ->latest();

        $this->applyRoomScope($query, $activeRoom);

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
        $this->applyRoomScope($allEvents, $activeRoom);

        $stats = [
            'total_event' => (clone $allEvents)->count(),
            'aktif'       => (clone $allEvents)->where('is_active', true)->count(),
            'tutup'       => (clone $allEvents)->where('is_active', false)->count(),
            'pendaftar'   => (clone $allEvents)->withCount(['pendaftarans' => $pendaftarScope])->get()->sum('pendaftarans_count'),
        ];

        return view('penerimaan.index', compact('penerimaans', 'stats', 'activeRoom'));
    }

    public function create()
    {
        $authUser = auth()->user();
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $formalOptions = PenerimaanSantri::formalJenjangOptions();

        if ($scopedFormal && $authUser && !$authUser->isYayasan()) {
            $formalOptions = [$scopedFormal->jenjang_key => $scopedFormal->nama];
        }

        $formalKeys = array_keys($formalOptions);
        $activeRoom = $scopedFormal ? $scopedFormal->kode : session('active_lembaga');

        $defaultJenjangs = $formalKeys;
        if ($scopedFormal) {
            $aliasesLower = array_map('strtolower', Lembaga::jenjangAliasesForRoom($scopedFormal->kode));
            $matched = array_values(array_filter(
                $formalKeys,
                fn ($k) => in_array(strtolower(trim((string) $k)), $aliasesLower, true)
            ));
            if (!empty($matched)) {
                $defaultJenjangs = $matched;
            }
        }

        $penerimaan = new PenerimaanSantri([
            'tahun_ajaran'               => date('Y') . '/' . (date('Y') + 1),
            'jenjang'                    => implode(', ', $defaultJenjangs),
            'jenjangs'                   => $defaultJenjangs,
            'kontak_wa_per_jenjang'      => [],
            'lokasi_seleksi_per_jenjang' => [],
            'is_active'                  => true,
            'syarat_dokumen'             => PenerimaanSantri::DOKUMEN_DEFAULTS,
        ]);

        return view('penerimaan.form', [
            'penerimaan'   => $penerimaan,
            'isEdit'       => false,
            'dokumenMeta'  => PenerimaanSantri::DOKUMEN_META,
            'jenjangItems' => $formalOptions,
            'gelombangs'   => collect(),
        ]);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $formalOptions = PenerimaanSantri::formalJenjangOptions();
        if ($scopedFormal && $authUser && !$authUser->isYayasan()) {
            $formalOptions = [$scopedFormal->jenjang_key => $scopedFormal->nama];
        }

        $validated = $this->validateEvent($request, null, array_keys($formalOptions));
        [$jenjangs, $jenjangSummary, $waPerJenjang, $fallbackWa, $lokasiPerJenjang, $fallbackLokasi] = $this->extractPerLembagaData($validated, array_keys($formalOptions));

        $penerimaan = DB::transaction(function () use ($validated, $request, $jenjangs, $jenjangSummary, $waPerJenjang, $fallbackWa, $lokasiPerJenjang, $fallbackLokasi) {
            $event = PenerimaanSantri::create([
                'judul'                      => $validated['judul'],
                'slug'                       => $validated['slug'],
                'tahun_ajaran'               => $validated['tahun_ajaran'],
                'jenjang'                    => $jenjangSummary,
                'jenjangs'                   => $jenjangs,
                'kontak_wa'                  => $fallbackWa,
                'kontak_wa_per_jenjang'      => $waPerJenjang,
                'jadwal_seleksi'             => $validated['jadwal_seleksi'] ?? null,
                'lokasi_seleksi'             => $fallbackLokasi,
                'lokasi_seleksi_per_jenjang' => $lokasiPerJenjang,
                'syarat_dokumen'             => $this->normalizeDokumen($validated['syarat_dokumen'] ?? []),
                'catatan_syarat'             => $validated['catatan_syarat'] ?? null,
                'is_active'                  => $request->boolean('is_active', true),
                'created_by'                 => auth()->id(),
            ]);

            $this->syncGelombangs($event, $validated['gelombangs'] ?? [], $jenjangs);

            return $event;
        });

        return redirect()->route('penerimaan.index')
            ->with('success', 'Link penerimaan "' . $penerimaan->judul . '" berhasil dibuat.');
    }

    public function edit($id)
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')->findOrFail($id);
        $this->ensureEventInActiveRoom($penerimaan);

        if ($penerimaan->syncStaleJenjangKeys()) {
            $penerimaan->refresh()->load('gelombangs');
        }

        $authUser = auth()->user();
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        $formalOptions = PenerimaanSantri::formalJenjangOptions();
        if ($scopedFormal && $authUser && !$authUser->isYayasan()) {
            $formalOptions = [$scopedFormal->jenjang_key => $scopedFormal->nama];
        } else {
            // Pertahankan jenjang lama pada opsi bila sebelumnya sudah tersimpan di event
            foreach ($penerimaan->allowedJenjangs() as $existingKey) {
                if (!array_key_exists($existingKey, $formalOptions)) {
                    $formalOptions[$existingKey] = $existingKey;
                }
            }
        }

        return view('penerimaan.form', [
            'penerimaan'   => $penerimaan,
            'isEdit'       => true,
            'dokumenMeta'  => PenerimaanSantri::DOKUMEN_META,
            'jenjangItems' => $formalOptions,
            'gelombangs'   => $penerimaan->gelombangs,
        ]);
    }

    public function update(Request $request, $id)
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')->findOrFail($id);
        $this->ensureEventInActiveRoom($penerimaan);

        $authUser = auth()->user();
        $scopedFormal = Lembaga::activeScopedFormal($authUser);
        if ($scopedFormal && $authUser && !$authUser->isYayasan()) {
            $allowedKeys = [$scopedFormal->jenjang_key];
        } else {
            $formalKeys = array_keys(PenerimaanSantri::formalJenjangOptions());
            $allowedKeys = array_values(array_unique(array_merge($formalKeys, $penerimaan->allowedJenjangs())));
        }

        $validated = $this->validateEvent($request, $penerimaan->id, $allowedKeys);
        [$jenjangs, $jenjangSummary, $waPerJenjang, $fallbackWa, $lokasiPerJenjang, $fallbackLokasi] = $this->extractPerLembagaData($validated, $allowedKeys);

        DB::transaction(function () use ($penerimaan, $validated, $request, $jenjangs, $jenjangSummary, $waPerJenjang, $fallbackWa, $lokasiPerJenjang, $fallbackLokasi) {
            $penerimaan->update([
                'judul'                      => $validated['judul'],
                'slug'                       => $validated['slug'],
                'tahun_ajaran'               => $validated['tahun_ajaran'],
                'jenjang'                    => $jenjangSummary,
                'jenjangs'                   => $jenjangs,
                'kontak_wa'                  => $fallbackWa,
                'kontak_wa_per_jenjang'      => $waPerJenjang,
                'jadwal_seleksi'             => $validated['jadwal_seleksi'] ?? null,
                'lokasi_seleksi'             => $fallbackLokasi,
                'lokasi_seleksi_per_jenjang' => $lokasiPerJenjang,
                'syarat_dokumen'             => $this->normalizeDokumen($validated['syarat_dokumen'] ?? []),
                'catatan_syarat'             => $validated['catatan_syarat'] ?? null,
                'is_active'                  => $request->boolean('is_active'),
            ]);

            $this->syncGelombangs($penerimaan, $validated['gelombangs'] ?? [], $jenjangs);
        });

        return redirect()->route('penerimaan.index')
            ->with('success', 'Link penerimaan "' . $penerimaan->judul . '" berhasil diperbarui.');
    }

    public function toggleStatus($id)
    {
        $penerimaan = PenerimaanSantri::findOrFail($id);
        $this->ensureEventInActiveRoom($penerimaan);
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
        $this->ensureEventInActiveRoom($penerimaan);
        $judul = $penerimaan->judul;
        $penerimaan->delete();

        return redirect()->route('penerimaan.index')
            ->with('success', 'Link penerimaan "' . $judul . '" berhasil dihapus.');
    }

    protected function applyRoomScope($query, ?string $activeRoom): void
    {
        if (Lembaga::isGlobalRoom($activeRoom)) {
            return;
        }

        $aliases = array_map('strtolower', Lembaga::jenjangAliasesForRoom($activeRoom));
        if (empty($aliases)) {
            return;
        }

        $query->where(function ($q) use ($aliases) {
            $q->whereRaw('LOWER(TRIM(jenjang)) = ?', ['semua']);
            foreach ($aliases as $alias) {
                $q->orWhereRaw('LOWER(TRIM(jenjang)) = ?', [$alias])
                    ->orWhereRaw('LOWER(jenjang) LIKE ?', [$alias . ',%'])
                    ->orWhereRaw('LOWER(jenjang) LIKE ?', ['%, ' . $alias])
                    ->orWhereRaw('LOWER(jenjang) LIKE ?', ['%,' . $alias])
                    ->orWhereRaw('LOWER(jenjang) LIKE ?', ['%, ' . $alias . ',%'])
                    ->orWhereRaw('LOWER(jenjang) LIKE ?', ['%,' . $alias . ',%'])
                    ->orWhereRaw('LOWER(jenjangs) LIKE ?', ['%"' . $alias . '"%']);
            }
        });
    }

    protected function validateEvent(Request $request, ?int $ignoreId, array $allowedJenjangKeys): array
    {
        $rawSlug = trim((string) $request->input('slug', ''));
        $normalizedSlug = Str::slug($rawSlug !== '' ? $rawSlug : (string) $request->input('judul', ''));

        $jenjangsInput = $request->input('jenjangs');
        if (!is_array($jenjangsInput) && $request->filled('jenjang')) {
            $legacy = trim((string) $request->input('jenjang'));
            $jenjangsInput = strcasecmp($legacy, 'Semua') === 0
                ? $allowedJenjangKeys
                : array_values(array_filter(array_map('trim', explode(',', $legacy))));
        }

        $rawGelombangs = $request->input('gelombangs', []);
        $filteredGelombangs = [];
        if (is_array($rawGelombangs)) {
            foreach ($rawGelombangs as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $kuotaMapRaw = is_array($row['kuota_per_jenjang'] ?? null) ? $row['kuota_per_jenjang'] : [];
                $kuotaMapClean = [];
                foreach ($kuotaMapRaw as $jKey => $jVal) {
                    if (filled($jVal)) {
                        $kuotaMapClean[$jKey] = (int) $jVal;
                    }
                }

                $hasAny = filled($row['nama_gelombang'] ?? null)
                    || filled($row['tanggal_mulai'] ?? null)
                    || filled($row['tanggal_selesai'] ?? null)
                    || filled($row['kuota'] ?? null)
                    || !empty($kuotaMapClean);

                if ($hasAny) {
                    $filteredGelombangs[] = [
                        'id'                => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                        'nama_gelombang'    => trim((string) ($row['nama_gelombang'] ?? '')),
                        'tanggal_mulai'     => $row['tanggal_mulai'] ?? null,
                        'tanggal_selesai'   => $row['tanggal_selesai'] ?? null,
                        'kuota'             => filled($row['kuota'] ?? null) ? (int) $row['kuota'] : null,
                        'kuota_per_jenjang' => $kuotaMapClean,
                    ];
                }
            }
        }

        $request->merge([
            'slug'       => $normalizedSlug,
            'jenjangs'   => $jenjangsInput,
            'gelombangs' => $filteredGelombangs,
        ]);

        return $request->validate([
            'judul'                              => 'required|string|max:255',
            'slug'                               => [
                'required',
                'string',
                'max:160',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('penerimaan_santris', 'slug')->ignore($ignoreId),
            ],
            'tahun_ajaran'                       => 'required|string|max:30',
            'jenjangs'                           => 'required|array|min:1',
            'jenjangs.*'                         => ['required', 'string', Rule::in($allowedJenjangKeys)],
            'kontak_wa'                          => 'nullable|string|max:30',
            'kontak_wa_per_jenjang'              => 'nullable|array',
            'kontak_wa_per_jenjang.*'            => 'nullable|string|max:30',
            'jadwal_seleksi'                     => 'nullable|string|max:255',
            'lokasi_seleksi'                     => 'nullable|string|max:255',
            'lokasi_seleksi_per_jenjang'         => 'nullable|array',
            'lokasi_seleksi_per_jenjang.*'       => 'nullable|string|max:255',
            'syarat_dokumen'                     => 'nullable|array',
            'syarat_dokumen.*'                   => 'in:wajib,opsional,tidak',
            'catatan_syarat'                     => 'nullable|string|max:5000',
            'gelombangs'                         => 'nullable|array',
            'gelombangs.*.id'                    => 'nullable|integer',
            'gelombangs.*.nama_gelombang'        => 'required|string|max:100',
            'gelombangs.*.tanggal_mulai'         => 'required|date',
            'gelombangs.*.tanggal_selesai'       => 'required|date|after_or_equal:gelombangs.*.tanggal_mulai',
            'gelombangs.*.kuota'                 => 'nullable|integer|min:1',
            'gelombangs.*.kuota_per_jenjang'     => 'nullable|array',
            'gelombangs.*.kuota_per_jenjang.*'   => 'nullable|integer|min:1',
        ], [
            'judul.required'                              => 'Judul pendaftaran wajib diisi.',
            'slug.required'                               => 'Slug wajib diisi.',
            'slug.unique'                                 => 'Slug sudah digunakan oleh penerimaan lain.',
            'slug.regex'                                  => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung (-).',
            'tahun_ajaran.required'                       => 'Tahun pendaftaran wajib diisi.',
            'jenjangs.required'                           => 'Pilih minimal satu jenjang.',
            'jenjangs.min'                                => 'Pilih minimal satu jenjang.',
            'gelombangs.*.nama_gelombang.required'        => 'Nama gelombang wajib diisi.',
            'gelombangs.*.tanggal_mulai.required'         => 'Tanggal mulai gelombang wajib diisi.',
            'gelombangs.*.tanggal_selesai.required'       => 'Tanggal selesai gelombang wajib diisi.',
            'gelombangs.*.tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh lebih awal dari tanggal mulai.',
        ]);
    }

    protected function extractPerLembagaData(array $validated, array $validOrder): array
    {
        $jenjangs = array_values(array_intersect($validOrder, $validated['jenjangs'] ?? []));
        $summary = implode(', ', $jenjangs);

        $waInput = is_array($validated['kontak_wa_per_jenjang'] ?? null)
            ? $validated['kontak_wa_per_jenjang']
            : [];
        $legacyWa = trim((string) ($validated['kontak_wa'] ?? ''));

        $waPerJenjang = [];
        foreach ($jenjangs as $j) {
            $num = trim((string) ($waInput[$j] ?? ''));
            if ($num === '' && $legacyWa !== '' && empty($waInput)) {
                $num = $legacyWa;
            }
            if ($num !== '') {
                $waPerJenjang[$j] = $num;
            }
        }
        $fallbackWa = !empty($waPerJenjang)
            ? reset($waPerJenjang)
            : ($legacyWa !== '' ? $legacyWa : null);

        $lokasiInput = is_array($validated['lokasi_seleksi_per_jenjang'] ?? null)
            ? $validated['lokasi_seleksi_per_jenjang']
            : [];
        $legacyLokasi = trim((string) ($validated['lokasi_seleksi'] ?? ''));

        $lokasiPerJenjang = [];
        foreach ($jenjangs as $j) {
            $loc = trim((string) ($lokasiInput[$j] ?? ''));
            if ($loc === '' && $legacyLokasi !== '' && empty($lokasiInput)) {
                $loc = $legacyLokasi;
            }
            if ($loc !== '') {
                $lokasiPerJenjang[$j] = $loc;
            }
        }
        $fallbackLokasi = !empty($lokasiPerJenjang)
            ? reset($lokasiPerJenjang)
            : ($legacyLokasi !== '' ? $legacyLokasi : null);

        return [$jenjangs, $summary, $waPerJenjang, $fallbackWa, $lokasiPerJenjang, $fallbackLokasi];
    }

    /**
     * Halaman Form Builder (ala Google Form) khusus untuk 1 Link Penerimaan.
     */
    public function editFormBuilder($id)
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')->findOrFail($id);
        $this->ensureEventInActiveRoom($penerimaan);

        $dokumenConfig = $penerimaan->dokumenConfig();
        $schema = $penerimaan->resolvedFormSchema();
        $defaultSchema = SpmbFormSchema::defaultSchema($dokumenConfig);
        $compactSchema = SpmbFormSchema::compactSchema($dokumenConfig);
        $builtinCatalog = SpmbFormSchema::builtinCatalog();
        $fieldTypes = SpmbFormSchema::FIELD_TYPES;

        return view('penerimaan.formulir', compact(
            'penerimaan',
            'schema',
            'defaultSchema',
            'compactSchema',
            'builtinCatalog',
            'fieldTypes'
        ));
    }

    /**
     * Simpan skema kustom formulir pendaftaran pada Link Penerimaan.
     */
    public function updateFormBuilder(Request $request, $id)
    {
        $penerimaan = PenerimaanSantri::findOrFail($id);
        $this->ensureEventInActiveRoom($penerimaan);

        $rawSchema = $request->input('form_schema');
        if (is_string($request->input('form_schema_json'))) {
            $decoded = json_decode($request->input('form_schema_json'), true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return back()->with('error', 'Format skema formulir tidak valid. Silakan coba simpan kembali.');
            }
            $rawSchema = $decoded;
        }

        if (!is_array($rawSchema) || empty($rawSchema['sections'])) {
            return back()->with('error', 'Formulir minimal harus memiliki 1 bagian dengan pertanyaan aktif.');
        }

        $cleanSchema = SpmbFormSchema::sanitizeSubmittedSchema($rawSchema);
        $syncedDokumen = SpmbFormSchema::extractDokumenConfig($cleanSchema);

        $penerimaan->update([
            'form_schema'    => $cleanSchema,
            'syarat_dokumen' => $syncedDokumen,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'ok'      => true,
                'message' => 'Desain formulir pendaftaran berhasil disimpan.',
                'schema'  => $cleanSchema,
            ]);
        }

        return redirect()
            ->route('penerimaan.formulir.edit', $penerimaan->id)
            ->with('success', 'Desain formulir pendaftaran berhasil disimpan.');
    }

    /**
     * Kembalikan desain formulir ke tampilan standar bawaan.
     */
    public function resetFormBuilder($id)
    {
        $penerimaan = PenerimaanSantri::findOrFail($id);
        $this->ensureEventInActiveRoom($penerimaan);

        $penerimaan->update([
            'form_schema' => null,
        ]);

        return redirect()
            ->route('penerimaan.formulir.edit', $penerimaan->id)
            ->with('success', 'Formulir dikembalikan ke desain standar bawaan.');
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

    protected function syncGelombangs(PenerimaanSantri $event, array $rows, array $selectedJenjangs): void
    {
        $existingIds = $event->gelombangs()->pluck('id')->all();
        $keptIds = [];

        foreach ($rows as $index => $row) {
            $kuotaPerJenjang = [];
            $inputKuotaMap = is_array($row['kuota_per_jenjang'] ?? null) ? $row['kuota_per_jenjang'] : [];

            foreach ($selectedJenjangs as $j) {
                if (isset($inputKuotaMap[$j]) && (int) $inputKuotaMap[$j] > 0) {
                    $kuotaPerJenjang[$j] = (int) $inputKuotaMap[$j];
                }
            }

            // Total kuota disimpan di kolom `kuota` hanya bila seluruh jenjang memiliki batas atau menggunakan fallback tunggal
            $totalKuota = null;
            if (!empty($kuotaPerJenjang)) {
                $totalKuota = array_sum($kuotaPerJenjang);
            } elseif (!empty($row['kuota'])) {
                $totalKuota = (int) $row['kuota'];
            }

            $payload = [
                'nama_gelombang'    => $row['nama_gelombang'],
                'tanggal_mulai'     => $row['tanggal_mulai'],
                'tanggal_selesai'   => $row['tanggal_selesai'],
                'kuota'             => $totalKuota,
                'kuota_per_jenjang' => !empty($kuotaPerJenjang) ? $kuotaPerJenjang : null,
                'urutan'            => $index + 1,
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
