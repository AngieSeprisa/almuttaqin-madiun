<?php

namespace App\Http\Controllers;

use App\Http\Requests\PendaftaranRequest;
use App\Models\Lembaga;
use App\Models\Pendaftaran;
use App\Models\PenerimaanSantri;
use App\Services\ImageCompressor;
use App\Support\SpmbFormSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class SpmbPublicController extends Controller
{
    /**
     * Endpoint JSON publik (read-only + CORS) untuk sinkronisasi kuota & info SPMB ke Landing Page.
     */
    public function info(string $slug): JsonResponse
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')
            ->where('slug', $slug)
            ->firstOrFail();

        $today = now()->toDateString();
        $isOpen = $penerimaan->isOpen();
        $statusState = $penerimaan->statusState();
        $activeGelombang = $penerimaan->activeGelombang();
        $allowedJenjangs = $penerimaan->allowedJenjangs();

        // Tentukan gelombang acuan (gelombang aktif, atau gelombang yang tanggalnya sedang berjalan namun penuh)
        $refGelombang = $activeGelombang;
        if (!$refGelombang && $penerimaan->gelombangs->isNotEmpty()) {
            $refGelombang = $penerimaan->gelombangs->first(function ($g) use ($today) {
                $s = $g->tanggal_mulai?->toDateString();
                $e = $g->tanggal_selesai?->toDateString();
                return $s && $e && $today >= $s && $today <= $e;
            });
        }

        $countsByJenjang = $refGelombang
            ? $refGelombang->countsByJenjang()
            : $penerimaan->pendaftarans()
                ->selectRaw('jenjang, COUNT(*) as total')
                ->groupBy('jenjang')
                ->pluck('total', 'jenjang')
                ->all();

        $lembagaItems = [];
        $totalKuota = 0;
        $hasAllQuotas = !empty($allowedJenjangs);
        $totalTerisi = 0;

        foreach ($allowedJenjangs as $jKey) {
            $lemModel = Lembaga::resolveForJenjang($jKey);
            $kuota = $refGelombang ? $refGelombang->kuotaForJenjang($jKey) : null;
            $terisi = (int) ($countsByJenjang[$jKey] ?? 0);
            $sisa = $kuota !== null ? max(0, $kuota - $terisi) : null;
            $persen = ($kuota !== null && $kuota > 0)
                ? (int) min(100, round(($terisi / $kuota) * 100))
                : null;
            $penuh = $kuota !== null ? ($terisi >= $kuota) : false;

            $totalTerisi += $terisi;
            if ($kuota === null) {
                $hasAllQuotas = false;
            } else {
                $totalKuota += $kuota;
            }

            $lembagaItems[] = [
                'kode'           => $jKey,
                'nama'           => $lemModel ? $lemModel->nama : $jKey,
                'logo_url'       => $lemModel ? $lemModel->logo_url : asset('images/logo.png'),
                'kuota'          => $kuota,
                'terisi'         => $terisi,
                'sisa'           => $sisa,
                'persen_terisi'  => $persen,
                'penuh'          => $penuh,
                'lokasi_seleksi' => $penerimaan->lokasiForJenjang($jKey),
                'wa_url'         => $penerimaan->waUrl(null, $jKey),
            ];
        }

        $payload = [
            'slug'            => $penerimaan->slug,
            'judul'           => $penerimaan->judul,
            'tahun_ajaran'    => $penerimaan->tahun_ajaran,
            'is_open'         => $isOpen,
            'status'          => $statusState,
            'jadwal_seleksi'  => $penerimaan->jadwal_seleksi,
            'urls'            => [
                'form'       => route('spmb.show', $penerimaan->slug),
                'cek_status' => route('spmb.cek-status', $penerimaan->slug),
            ],
            'gelombang_aktif' => $activeGelombang ? [
                'id'              => $activeGelombang->id,
                'nama'            => $activeGelombang->nama_gelombang,
                'tanggal_mulai'   => $activeGelombang->tanggal_mulai?->toDateString(),
                'tanggal_selesai' => $activeGelombang->tanggal_selesai?->toDateString(),
            ] : null,
            'gelombangs'      => $penerimaan->gelombangs->map(fn ($g) => [
                'id'                => $g->id,
                'nama'              => $g->nama_gelombang,
                'tanggal_mulai'     => $g->tanggal_mulai?->toDateString(),
                'tanggal_selesai'   => $g->tanggal_selesai?->toDateString(),
                'kuota_per_jenjang' => $g->kuota_per_jenjang ?? [],
            ])->values()->all(),
            'ringkasan'       => [
                'total_pendaftar' => $totalTerisi,
                'total_kuota'     => $hasAllQuotas ? $totalKuota : null,
                'total_sisa'      => $hasAllQuotas ? max(0, $totalKuota - $totalTerisi) : null,
            ],
            'lembaga'         => $lembagaItems,
            'syarat'          => $penerimaan->syaratList(),
            'updated_at'      => now()->toIso8601String(),
        ];

        return response()->json($payload, 200, [
            'Access-Control-Allow-Origin'  => '*',
            'Access-Control-Allow-Methods' => 'GET, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Accept',
            'Cache-Control'                => 'public, max-age=30',
        ]);
    }

    public function show(string $slug)
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')
            ->where('slug', $slug)
            ->firstOrFail();

        if ($penerimaan->syncStaleJenjangKeys()) {
            $penerimaan->refresh()->load('gelombangs');
        }

        $isOpen = $penerimaan->isOpen();
        $activeGelombang = $penerimaan->activeGelombang();
        $statusState = $penerimaan->statusState();
        $allowedJenjangs = $penerimaan->allowedJenjangs();
        $availableJenjangs = $penerimaan->availableJenjangs();
        $jenjangDisplayLabels = $penerimaan->jenjangDisplayLabels();
        $dokumenConfig = $penerimaan->dokumenConfig();
        $lokasiMap = $penerimaan->lokasiMap();
        $formSchema = $penerimaan->resolvedFormSchema();
        $visibleSections = SpmbFormSchema::visibleSections($formSchema);
        $submissionToken = (string) Str::uuid();

        return view('spmb.form', compact(
            'penerimaan',
            'isOpen',
            'activeGelombang',
            'statusState',
            'allowedJenjangs',
            'availableJenjangs',
            'jenjangDisplayLabels',
            'dokumenConfig',
            'lokasiMap',
            'formSchema',
            'visibleSections',
            'submissionToken'
        ));
    }

    public function store(Request $request, string $slug, ImageCompressor $compressor)
    {
        $penerimaan = PenerimaanSantri::with('gelombangs')
            ->where('slug', $slug)
            ->firstOrFail();

        // Honeypot anti-spam
        if ($request->filled('website_url')) {
            return redirect()->route('spmb.show', $slug);
        }

        if (!$penerimaan->isOpen()) {
            return redirect()->route('spmb.show', $slug)
                ->with('error', 'Pendaftaran sedang ditutup.');
        }

        // Idempotency check: cegah pendaftaran ganda akibat double-click / retry browser dengan token form yang sama
        $submissionToken = trim((string) $request->input('submission_token', ''));
        $doneTokenCacheKey = $submissionToken !== ''
            ? ('spmb_done_token:' . $penerimaan->id . ':' . sha1($submissionToken))
            : null;

        if ($doneTokenCacheKey) {
            $existingId = Cache::get($doneTokenCacheKey);
            if ($existingId) {
                $alreadyCreated = Pendaftaran::where('penerimaan_santri_id', $penerimaan->id)->find($existingId);
                if ($alreadyCreated) {
                    return redirect()->route('spmb.bukti', [
                        'slug'  => $penerimaan->slug,
                        'token' => $alreadyCreated->public_token,
                    ])->with('success', 'Pendaftaran Anda sudah tercatat sebelumnya.');
                }
            }
        }

        $activeGelombang = $penerimaan->activeGelombang();
        $availableJenjangs = $penerimaan->availableJenjangs();
        $dokumenConfig = $penerimaan->dokumenConfig();
        $formSchema = $penerimaan->resolvedFormSchema();
        $activeFields = SpmbFormSchema::activeFieldsFlat($formSchema);

        $activeBuiltins = [];
        $activeCustoms = [];
        foreach ($activeFields as $fld) {
            if (($fld['source'] ?? '') === 'builtin') {
                $activeBuiltins[$fld['key']] = $fld;
            } else {
                $activeCustoms[$fld['id']] = $fld;
            }
        }

        $baseRequest = new PendaftaranRequest();
        $baseRules = $baseRequest->rules();
        $attributes = $baseRequest->attributes();
        $messages = $baseRequest->messages();

        $rules = [];

        // Field inti terkunci selalu wajib
        $rules['jenjang'] = ['required', Rule::in($availableJenjangs)];
        $messages['jenjang.in'] = 'Kuota untuk lembaga/jenjang yang dipilih sudah penuh atau tidak tersedia.';
        $rules['nama_lengkap'] = 'required|string|max:255';
        if (isset($activeBuiltins['jenjang']['label'])) {
            $attributes['jenjang'] = $activeBuiltins['jenjang']['label'];
        }
        if (isset($activeBuiltins['nama_lengkap']['label'])) {
            $attributes['nama_lengkap'] = $activeBuiltins['nama_lengkap']['label'];
        }

        // Validasi dinamis untuk seluruh field bawaan yang aktif di skema
        foreach ($activeBuiltins as $key => $fld) {
            if (in_array($key, ['jenjang', 'nama_lengkap'], true)) {
                continue;
            }

            $isReq = !empty($fld['required']);
            $attributes[$key] = $fld['label'] ?: ($attributes[$key] ?? $key);

            if ($key === 'nik') {
                $rules['nik'] = [
                    $isReq ? 'required' : 'nullable',
                    'string',
                    'max:16',
                ];
                continue;
            }

            if (isset(SpmbFormSchema::DOKUMEN_FIELD_TO_FLAG[$key])) {
                $flagKey = SpmbFormSchema::DOKUMEN_FIELD_TO_FLAG[$key];
                $rules[$flagKey] = 'nullable|boolean';
                $rules[$key] = ($isReq ? 'required' : 'nullable') . '|file|image|mimes:jpg,jpeg,png,webp|max:10240';
                continue;
            }

            $baseRule = $baseRules[$key] ?? 'nullable|string|max:255';
            if (is_string($baseRule)) {
                if ($isReq) {
                    $rules[$key] = preg_replace('/^nullable\|?/', 'required|', $baseRule);
                    if (!str_starts_with($rules[$key], 'required')) {
                        $rules[$key] = 'required|' . $rules[$key];
                    }
                    $rules[$key] = rtrim($rules[$key], '|');
                } else {
                    $rules[$key] = $baseRule;
                }
            } else {
                $rules[$key] = $baseRule;
            }
        }

        // Validasi dinamis untuk seluruh pertanyaan kustom ala Google Form
        foreach ($activeCustoms as $cfId => $fld) {
            $isReq = !empty($fld['required']);
            $type = $fld['type'] ?? 'text';
            $fieldPath = "custom_fields.{$cfId}";
            $attributes[$fieldPath] = $fld['label'];
            $options = is_array($fld['options'] ?? null) ? $fld['options'] : [];

            if ($type === 'textarea') {
                $rules[$fieldPath] = ($isReq ? 'required' : 'nullable') . '|string|max:3000';
            } elseif ($type === 'number') {
                $rules[$fieldPath] = ($isReq ? 'required' : 'nullable') . '|numeric';
            } elseif ($type === 'date') {
                $rules[$fieldPath] = ($isReq ? 'required' : 'nullable') . '|date';
            } elseif (in_array($type, ['radio', 'select'], true)) {
                $rules[$fieldPath] = array_values(array_filter([
                    $isReq ? 'required' : 'nullable',
                    'string',
                    !empty($options) ? Rule::in($options) : null,
                ]));
            } elseif ($type === 'checkbox') {
                $rules[$fieldPath] = $isReq ? 'required|array|min:1' : 'nullable|array';
                if (!empty($options)) {
                    $rules["{$fieldPath}.*"] = [Rule::in($options)];
                }
            } elseif ($type === 'file') {
                $rules[$fieldPath] = ($isReq ? 'required' : 'nullable') . '|file|mimes:jpg,jpeg,png,webp,pdf|max:10240';
                $messages["{$fieldPath}.mimes"] = ':attribute harus berformat JPG, PNG, WEBP, atau PDF.';
                $messages["{$fieldPath}.max"] = ':attribute terlalu besar (maksimal 10 MB).';
            } else {
                $rules[$fieldPath] = ($isReq ? 'required' : 'nullable') . '|string|max:500';
            }
        }

        $input = $request->all();
        if (isset($input['nama_lengkap']) && is_string($input['nama_lengkap'])) {
            $input['nama_lengkap'] = preg_replace('/\s+/', ' ', trim($input['nama_lengkap']));
        }
        foreach (['nik', 'nisn'] as $idField) {
            if (array_key_exists($idField, $input) && is_string($input[$idField])) {
                $cleanId = preg_replace('/\s+/', '', trim($input[$idField]));
                $input[$idField] = ($cleanId === '' || $cleanId === '-') ? null : $cleanId;
            }
        }
        if (isset($activeBuiltins['penerima_kip'])) {
            $input['penerima_kip'] = $request->boolean('penerima_kip');
        }
        foreach (SpmbFormSchema::DOKUMEN_FIELD_TO_FLAG as $fotoKey => $flagKey) {
            if (isset($activeBuiltins[$fotoKey])) {
                $input[$flagKey] = $request->boolean($flagKey);
            }
        }

        if (count($availableJenjangs) === 1 && empty($input['jenjang'])) {
            $input['jenjang'] = $availableJenjangs[0];
        }

        $validator = Validator::make($input, $rules, $messages, $attributes);
        if ($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }

        $validated = $validator->validated();
        $data = $validated;
        unset($data['custom_fields']);

        // Kunci atomik per event agar klik ganda / request paralel tidak menembus cek duplikat
        $lock = null;
        try {
            $lock = Cache::lock('spmb_store_event:' . $penerimaan->id, 10);
            $lock->block(5);
        } catch (\Throwable $e) {
            $lock = null;
        }

        try {
            // Cek ulang idempotency token setelah mendapatkan lock
            if ($doneTokenCacheKey) {
                $existingId = Cache::get($doneTokenCacheKey);
                if ($existingId) {
                    $alreadyCreated = Pendaftaran::where('penerimaan_santri_id', $penerimaan->id)->find($existingId);
                    if ($alreadyCreated) {
                        return redirect()->route('spmb.bukti', [
                            'slug'  => $penerimaan->slug,
                            'token' => $alreadyCreated->public_token,
                        ])->with('success', 'Pendaftaran Anda sudah tercatat sebelumnya.');
                    }
                }
            }

            // Cek duplikat multi-kriteria (NIK, NISN, Nama + Tanggal Lahir, Nama + No. WA, atau kemiripan nama + TTL + WA)
            $duplicate = $this->findDuplicatePendaftaran($penerimaan, $data);
            if ($duplicate !== null) {
                return $this->duplicateRedirectResponse($penerimaan, $duplicate);
            }

            $stored = [];
            $storedCustomFiles = [];
            try {
                // Simpan foto berkas bawaan
                foreach (Pendaftaran::FOTO_BERKAS as $field => [$flag]) {
                    unset($data[$field]);
                    if (!isset($activeBuiltins[$field])) {
                        unset($data[$flag]);
                        continue;
                    }

                    if ($request->hasFile($field)) {
                        $stored[$field] = $compressor->store($request->file($field), 'berkas');
                        $data[$field] = $stored[$field];
                        $data[$flag] = true;
                    }
                }

                // Susun & simpan jawaban pertanyaan kustom
                $customResponses = [];
                $submittedCustom = is_array($validated['custom_fields'] ?? null) ? $validated['custom_fields'] : [];

                foreach ($activeCustoms as $cfId => $fld) {
                    $type = $fld['type'] ?? 'text';
                    $entry = [
                        'id'      => $cfId,
                        'section' => $fld['_section_title'] ?? 'Tambahan',
                        'label'   => $fld['label'],
                        'type'    => $type,
                    ];

                    if ($type === 'file') {
                        if ($request->hasFile("custom_fields.{$cfId}")) {
                            $uploaded = $request->file("custom_fields.{$cfId}");
                            $mime = strtolower((string) $uploaded->getMimeType());
                            if (str_starts_with($mime, 'image/')) {
                                $savedPath = $compressor->store($uploaded, 'berkas/custom');
                            } else {
                                $savedPath = $uploaded->store('berkas/custom', 'public');
                            }
                            $storedCustomFiles[] = $savedPath;
                            $entry['value'] = $savedPath;
                            $entry['original_name'] = $uploaded->getClientOriginalName();
                            $customResponses[] = $entry;
                        }
                    } elseif ($type === 'checkbox') {
                        $vals = $submittedCustom[$cfId] ?? [];
                        if (is_array($vals) && !empty($vals)) {
                            $entry['value'] = array_values($vals);
                            $customResponses[] = $entry;
                        }
                    } else {
                        $val = $submittedCustom[$cfId] ?? null;
                        if ($val !== null && trim((string) $val) !== '') {
                            $entry['value'] = trim((string) $val);
                            $customResponses[] = $entry;
                        }
                    }
                }

                $data['custom_responses'] = !empty($customResponses) ? $customResponses : null;
            } catch (RuntimeException $e) {
                foreach ($stored as $path) {
                    $compressor->delete($path);
                }
                foreach ($storedCustomFiles as $path) {
                    Storage::disk('public')->delete($path);
                }

                return back()->withInput()->withErrors(['foto_berkas' => $e->getMessage()]);
            }

            if (empty($data['nik'])) {
                $data['nik'] = null;
            }
            if (empty($data['nisn'])) {
                $data['nisn'] = null;
            }

            foreach (['pj_biaya_hp', 'ayah_hp', 'ibu_hp'] as $hpField) {
                if (!empty($data[$hpField])) {
                    $data[$hpField] = preg_replace('/[^0-9+]/', '', (string) $data[$hpField]);
                }
            }

            $data['penerimaan_santri_id'] = $penerimaan->id;
            $data['penerimaan_gelombang_id'] = $activeGelombang?->id;
            $data['status_pendaftaran'] = 'Pending';
            $data['no_induk'] = $this->generateRegistrationNumber($penerimaan, $data['jenjang']);
            $data['public_token'] = Str::random(40);

            // Deteksi otomatis apakah pendaftar adalah siswa/alumni internal yang melanjutkan ke jenjang berikutnya (mis. SMP -> SMA)
            $internalMatch = Pendaftaran::detectInternalStudent(
                $data['nik'] ?? null,
                $data['nisn'] ?? null,
                $data['nama_lengkap'] ?? null,
                $data['tanggal_lahir'] ?? null,
                $data['jenjang'] ?? null
            );
            if ($internalMatch) {
                $data['is_lanjut_jenjang'] = true;
                $data['jenjang_sebelumnya'] = $internalMatch['jenjang_sebelumnya'];
                $data['siswa_asal_id'] = $internalMatch['siswa_asal_id'];
            }

            try {
                $pendaftaran = Pendaftaran::create($data);
            } catch (\Throwable $e) {
                foreach ($stored as $path) {
                    $compressor->delete($path);
                }
                foreach ($storedCustomFiles as $path) {
                    Storage::disk('public')->delete($path);
                }
                throw $e;
            }

            if ($doneTokenCacheKey) {
                Cache::put($doneTokenCacheKey, $pendaftaran->id, now()->addMinutes(30));
            }
        } finally {
            if ($lock) {
                try {
                    $lock->release();
                } catch (\Throwable $e) {
                    // abaikan kegagalan release lock
                }
            }
        }

        return redirect()->route('spmb.bukti', [
            'slug'  => $penerimaan->slug,
            'token' => $pendaftaran->public_token,
        ])->with('success', 'Pendaftaran berhasil dikirim.');
    }

    /**
     * Deteksi apakah calon santri sudah terdaftar sebelumnya untuk mencegah pendaftaran ganda/berulang.
     */
    protected function findDuplicatePendaftaran(PenerimaanSantri $penerimaan, array $data): ?array
    {
        $nik = trim((string) ($data['nik'] ?? ''));
        $nisn = trim((string) ($data['nisn'] ?? ''));
        $nisnDigits = preg_replace('/\D+/', '', $nisn);
        $namaNorm = $this->normalizeComparableName((string) ($data['nama_lengkap'] ?? ''));
        $hpNorm = $this->normalizeComparablePhone((string) ($data['pj_biaya_hp'] ?? ''));

        $tglLahir = '';
        if (!empty($data['tanggal_lahir'])) {
            try {
                $tglLahir = \Illuminate\Support\Carbon::parse($data['tanggal_lahir'])->toDateString();
            } catch (\Throwable $e) {
                $tglLahir = '';
            }
        }

        // Ambil pendaftar aktif (selain Ditolak) pada event penerimaan yang sama.
        // Pendaftaran lama di event lain atau siswa yang sudah Diterima di jenjang berbeda (mis. SMP -> SMA) tetap diizinkan mendaftar.
        $targetJenjangNorm = strtolower(trim((string) ($data['jenjang'] ?? '')));
        $eventCandidates = Pendaftaran::where('penerimaan_santri_id', $penerimaan->id)
            ->where(function ($q) {
                $q->whereNull('status_pendaftaran')
                  ->orWhere('status_pendaftaran', '!=', 'Ditolak');
            })
            ->get(['id', 'no_induk', 'public_token', 'jenjang', 'nama_lengkap', 'nik', 'nisn', 'tanggal_lahir', 'pj_biaya_hp', 'status_pendaftaran'])
            ->filter(function ($cand) use ($targetJenjangNorm) {
                $candJenjang = strtolower(trim((string) ($cand->jenjang ?? '')));
                if ($targetJenjangNorm !== '' && $candJenjang !== '' && $candJenjang !== $targetJenjangNorm && $cand->status_pendaftaran === 'Diterima') {
                    return false;
                }
                return true;
            })
            ->values();

        if ($eventCandidates->isEmpty()) {
            return null;
        }

        // 1. Cek NIK sama pada event penerimaan yang sama
        if ($nik !== '') {
            $nikDigits = preg_replace('/\D+/', '', $nik);
            foreach ($eventCandidates as $cand) {
                $candNik = preg_replace('/\D+/', '', (string) ($cand->nik ?? ''));
                if ($candNik !== '' && $candNik === $nikDigits) {
                    return [
                        'record' => $cand,
                        'field'  => 'nik',
                        'reason' => 'NIK',
                    ];
                }
            }
        }

        // 2. Cek NISN sama pada event yang sama (minimal 5 digit dan bukan angka nol semua)
        if ($nisnDigits !== '' && strlen($nisnDigits) >= 5 && trim($nisnDigits, '0') !== '') {
            foreach ($eventCandidates as $cand) {
                $candNisn = preg_replace('/\D+/', '', (string) ($cand->nisn ?? ''));
                if ($candNisn !== '' && $candNisn === $nisnDigits) {
                    return [
                        'record' => $cand,
                        'field'  => 'nisn',
                        'reason' => 'NISN',
                    ];
                }
            }
        }

        // 3. Cek Nama Lengkap + Tanggal Lahir sama pada event yang sama
        if ($namaNorm !== '' && $tglLahir !== '') {
            foreach ($eventCandidates as $cand) {
                $candNama = $this->normalizeComparableName((string) ($cand->nama_lengkap ?? ''));
                $candTgl = $cand->tanggal_lahir ? $cand->tanggal_lahir->toDateString() : '';
                if ($candNama === $namaNorm && $candTgl === $tglLahir) {
                    return [
                        'record' => $cand,
                        'field'  => 'nama_lengkap',
                        'reason' => 'Nama Lengkap dan Tanggal Lahir',
                    ];
                }
            }
        }

        // 4. Cek Nama Lengkap + No. WhatsApp sama pada event yang sama
        if ($namaNorm !== '' && strlen($hpNorm) >= 8) {
            foreach ($eventCandidates as $cand) {
                $candNama = $this->normalizeComparableName((string) ($cand->nama_lengkap ?? ''));
                $candHp = $this->normalizeComparablePhone((string) ($cand->pj_biaya_hp ?? ''));
                if ($candNama === $namaNorm && $candHp !== '' && $candHp === $hpNorm) {
                    return [
                        'record' => $cand,
                        'field'  => 'nama_lengkap',
                        'reason' => 'Nama Lengkap dan Nomor WhatsApp',
                    ];
                }
            }
        }

        // 5. Cek typo tipis pada Nama (>= 85% mirip) dengan Tanggal Lahir & No. WhatsApp sama pada event yang sama
        if (mb_strlen($namaNorm) >= 6 && $tglLahir !== '' && strlen($hpNorm) >= 8) {
            foreach ($eventCandidates as $cand) {
                $candTgl = $cand->tanggal_lahir ? $cand->tanggal_lahir->toDateString() : '';
                $candHp = $this->normalizeComparablePhone((string) ($cand->pj_biaya_hp ?? ''));
                if ($candTgl !== $tglLahir || $candHp === '' || $candHp !== $hpNorm) {
                    continue;
                }
                $candNama = $this->normalizeComparableName((string) ($cand->nama_lengkap ?? ''));
                if (mb_strlen($candNama) < 6) {
                    continue;
                }
                similar_text($namaNorm, $candNama, $pct);
                if ($pct >= 85.0) {
                    return [
                        'record' => $cand,
                        'field'  => 'nama_lengkap',
                        'reason' => 'Data Calon Santri',
                    ];
                }
            }
        }

        return null;
    }

    protected function normalizeComparableName(string $name): string
    {
        $clean = mb_strtolower(trim($name));
        $clean = preg_replace('/[^a-z0-9\s]/u', ' ', $clean);

        return trim((string) preg_replace('/\s+/', ' ', $clean));
    }

    protected function normalizeComparablePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return '';
        }
        if (str_starts_with($digits, '62')) {
            return '0' . substr($digits, 2);
        }

        return $digits;
    }

    protected function duplicateRedirectResponse(PenerimaanSantri $penerimaan, array $duplicate)
    {
        /** @var Pendaftaran $existing */
        $existing = $duplicate['record'];
        $noReg = $existing->no_induk ?: '-';
        $field = $duplicate['field'] ?? 'nama_lengkap';
        $reason = $duplicate['reason'] ?? 'Data';

        $waMessage = "Assalamu'alaikum Panitia {$penerimaan->judul},\n"
            . "Saya ingin menanyakan / memperbaiki data pendaftaran atas nama {$existing->nama_lengkap} "
            . "(No. Registrasi: {$noReg}).";

        return back()
            ->withInput()
            ->withErrors([
                $field => "{$reason} sudah terdaftar sebelumnya dengan No. Registrasi {$noReg} atas nama {$existing->nama_lengkap}. Tidak perlu mendaftar ulang; silakan buka Cek Status atau hubungi panitia jika perlu memperbaiki data.",
            ])
            ->with('duplicate_registration', [
                'no_induk'       => $noReg,
                'nama_lengkap'   => $existing->nama_lengkap,
                'jenjang'        => $existing->jenjang,
                'reason'         => $reason,
                'bukti_url'      => $existing->public_token
                    ? route('spmb.bukti', ['slug' => $penerimaan->slug, 'token' => $existing->public_token])
                    : null,
                'cek_status_url' => route('spmb.cek-status', ['slug' => $penerimaan->slug, 'kode' => $noReg]),
                'wa_url'         => $penerimaan->waUrl($waMessage, $existing->jenjang),
            ]);
    }

    public function bukti(string $slug, string $token)
    {
        $penerimaan = PenerimaanSantri::where('slug', $slug)->firstOrFail();
        $pendaftaran = Pendaftaran::with('gelombang')
            ->where('penerimaan_santri_id', $penerimaan->id)
            ->where('public_token', $token)
            ->firstOrFail();
        $pendaftaran->setRelation('penerimaan', $penerimaan);

        $waMessage = "Assalamu'alaikum Panitia {$penerimaan->judul} ({$pendaftaran->jenjang}),\n"
            . "Saya telah mendaftar secara online:\n"
            . "No. Registrasi: {$pendaftaran->no_induk}\n"
            . "Nama: {$pendaftaran->nama_lengkap}\n"
            . "Jenjang: {$pendaftaran->jenjang}";
        $waConfirmUrl = $penerimaan->waUrl($waMessage, $pendaftaran->jenjang);
        $lokasiSeleksi = $penerimaan->lokasiForJenjang($pendaftaran->jenjang);

        return view('spmb.bukti', compact('penerimaan', 'pendaftaran', 'waConfirmUrl', 'lokasiSeleksi'));
    }

    public function cetak(string $slug, string $token)
    {
        $penerimaan = PenerimaanSantri::where('slug', $slug)->firstOrFail();
        $pendaftaran = Pendaftaran::with('gelombang')
            ->where('penerimaan_santri_id', $penerimaan->id)
            ->where('public_token', $token)
            ->firstOrFail();
        $pendaftaran->setRelation('penerimaan', $penerimaan);

        $kop = Lembaga::kopForJenjang($pendaftaran->jenjang);
        $lokasiSeleksi = $penerimaan->lokasiForJenjang($pendaftaran->jenjang);

        return view('spmb.cetak', compact('penerimaan', 'pendaftaran', 'kop', 'lokasiSeleksi'));
    }

    public function cekStatus(Request $request, string $slug)
    {
        $penerimaan = PenerimaanSantri::where('slug', $slug)->firstOrFail();
        $kode = trim((string) ($request->query('kode') ?: $request->query('nama', '')));
        $tanggalLahir = trim((string) $request->query('tanggal_lahir', ''));
        $pendaftaran = null;
        $lokasiSeleksi = null;
        $searched = false;
        $errorMessage = null;
        $httpErrorCode = 404;

        if ($kode !== '') {
            $searched = true;

            // 1. Cek exact match berdasarkan Nomor Registrasi atau NIK
            $exactMatch = Pendaftaran::with('gelombang')
                ->where('penerimaan_santri_id', $penerimaan->id)
                ->where(function ($q) use ($kode) {
                    $q->where('no_induk', $kode)
                        ->orWhere('nik', $kode);
                })
                ->latest()
                ->first();

            if ($exactMatch) {
                $pendaftaran = $exactMatch;
            } else {
                // 2. Pencarian berdasarkan Nama Lengkap wajib verifikasi Tanggal Lahir
                if ($tanggalLahir === '') {
                    $errorMessage = 'Masukkan Tanggal Lahir untuk verifikasi pencarian dengan nama.';
                    $httpErrorCode = 422;
                } elseif (mb_strlen($kode) < 3) {
                    $errorMessage = 'Masukkan minimal 3 huruf nama.';
                    $httpErrorCode = 422;
                } else {
                    try {
                        $parsedDate = \Illuminate\Support\Carbon::parse($tanggalLahir)->toDateString();
                        $pendaftaran = Pendaftaran::with('gelombang')
                            ->where('penerimaan_santri_id', $penerimaan->id)
                            ->where('nama_lengkap', 'like', "%{$kode}%")
                            ->whereDate('tanggal_lahir', $parsedDate)
                            ->latest()
                            ->first();
                    } catch (\Throwable $e) {
                        $errorMessage = 'Format Tanggal Lahir tidak valid.';
                        $httpErrorCode = 422;
                    }
                }
            }

            if ($pendaftaran) {
                $lokasiSeleksi = $penerimaan->lokasiForJenjang($pendaftaran->jenjang);
            } elseif ($errorMessage === null) {
                $errorMessage = 'Data pendaftaran tidak ditemukan.';
            }
        }

        if ($request->wantsJson() || $request->query('format') === 'json') {
            $corsHeaders = [
                'Access-Control-Allow-Origin'  => '*',
                'Access-Control-Allow-Methods' => 'GET, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept',
                'Cache-Control'                => 'no-store',
            ];

            if ($kode === '') {
                return response()->json([
                    'found'   => false,
                    'message' => 'Masukkan Nama Lengkap beserta Tanggal Lahir, atau Nomor Registrasi.',
                ], 422, $corsHeaders);
            }

            if (!$pendaftaran) {
                return response()->json([
                    'found'   => false,
                    'message' => $errorMessage ?? 'Data pendaftaran tidak ditemukan.',
                ], $httpErrorCode, $corsHeaders);
            }

            return response()->json([
                'found' => true,
                'data'  => [
                    'no_registrasi'  => $pendaftaran->no_induk,
                    'nama_lengkap'   => $pendaftaran->nama_lengkap,
                    'jenjang'        => $pendaftaran->jenjang,
                    'gelombang'      => $pendaftaran->gelombang?->nama_gelombang,
                    'status'         => $pendaftaran->status_pendaftaran,
                    'status_code'    => Str::slug($pendaftaran->status_pendaftaran ?? 'pending'),
                    'tanggal_daftar' => $pendaftaran->created_at?->toDateString(),
                    'jadwal_seleksi' => $penerimaan->jadwal_seleksi,
                    'lokasi_seleksi' => $lokasiSeleksi,
                    'bukti_url'      => $pendaftaran->public_token
                        ? route('spmb.bukti', ['slug' => $penerimaan->slug, 'token' => $pendaftaran->public_token])
                        : null,
                    'cetak_url'      => $pendaftaran->public_token
                        ? route('spmb.cetak', ['slug' => $penerimaan->slug, 'token' => $pendaftaran->public_token])
                        : null,
                    'wa_panitia_url' => $penerimaan->waUrl(null, $pendaftaran->jenjang),
                ],
            ], 200, $corsHeaders);
        }

        return view('spmb.cek-status', compact('penerimaan', 'kode', 'tanggalLahir', 'pendaftaran', 'lokasiSeleksi', 'searched', 'errorMessage'));
    }

    /**
     * Format nomor registrasi: {LEMBAGA}-{NOMOR_URUT}-{TAHUN_AJARAN}
     * Contoh: SMP-01-202728, SMA-02-202728, SMK-01-202728
     */
    protected function generateRegistrationNumber(PenerimaanSantri $penerimaan, string $jenjang): string
    {
        if (strtoupper(trim($jenjang)) === 'REGULER') {
            $lembagaCode = 'PONDOK';
        } else {
            $lembaga = Lembaga::resolveByKode($jenjang) ?? Lembaga::resolveForJenjang($jenjang);
            if ($lembaga && filled($lembaga->prefix_registrasi)) {
                $lembagaCode = $lembaga->registrationPrefix();
            } else {
                $lembagaCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $jenjang)), 0, 12) ?: 'SANTRI';
            }
        }

        $tahunCode = $penerimaan->tahunAjaranCode();
        $prefix = $lembagaCode . '-';
        $suffix = '-' . $tahunCode;

        $existingCodes = Pendaftaran::where('no_induk', 'like', $prefix . '%' . $suffix)
            ->pluck('no_induk');

        $maxSeq = 0;
        $pattern = '/^' . preg_quote($lembagaCode, '/') . '-(\d+)-' . preg_quote($tahunCode, '/') . '$/';
        foreach ($existingCodes as $code) {
            if (preg_match($pattern, (string) $code, $m)) {
                $num = (int) $m[1];
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            }
        }

        $seq = $maxSeq + 1;
        do {
            $urut = str_pad((string) $seq, 2, '0', STR_PAD_LEFT);
            $candidate = "{$lembagaCode}-{$urut}-{$tahunCode}";
            $exists = Pendaftaran::where('no_induk', $candidate)->exists();
            $seq++;
        } while ($exists);

        return $candidate;
    }
}
