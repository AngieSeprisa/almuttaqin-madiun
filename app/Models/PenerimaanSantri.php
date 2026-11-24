<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PenerimaanSantri extends Model
{
    use HasFactory;

    public const DOKUMEN_DEFAULTS = [
        'berkas_akta'             => 'opsional',
        'berkas_kk'               => 'opsional',
        'berkas_ktp_ortu'         => 'opsional',
        'berkas_surat_pernyataan' => 'opsional',
    ];

    public const DOKUMEN_META = [
        'berkas_akta'             => ['foto' => 'foto_akta', 'label' => 'Akta Kelahiran'],
        'berkas_kk'               => ['foto' => 'foto_kk', 'label' => 'Kartu Keluarga (KK)'],
        'berkas_ktp_ortu'         => ['foto' => 'foto_ktp_ortu', 'label' => 'KTP Orang Tua / Wali'],
        'berkas_surat_pernyataan' => ['foto' => 'foto_surat_pernyataan', 'label' => 'Surat Pernyataan'],
    ];

    protected $fillable = [
        'judul',
        'slug',
        'tahun_ajaran',
        'jenjang',
        'jenjangs',
        'kontak_wa',
        'kontak_wa_per_jenjang',
        'jadwal_seleksi',
        'lokasi_seleksi',
        'lokasi_seleksi_per_jenjang',
        'syarat_dokumen',
        'form_schema',
        'catatan_syarat',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'jenjangs'                   => 'array',
        'kontak_wa_per_jenjang'      => 'array',
        'lokasi_seleksi_per_jenjang' => 'array',
        'syarat_dokumen'             => 'array',
        'form_schema'                => 'array',
        'is_active'                  => 'boolean',
    ];

    /**
     * Daftar lembaga formal terdaftar dalam format [key => label].
     * Contoh: ['SMP' => 'SMP Al Muttaqin', 'SMA' => 'SMA Al Muttaqin']
     */
    public static function formalJenjangOptions(): array
    {
        $lembagas = Lembaga::formalOptions();
        if ($lembagas->isEmpty()) {
            return [
                'SMP' => 'SMP',
                'SMA' => 'SMA',
            ];
        }

        $options = [];
        foreach ($lembagas as $lem) {
            $options[$lem->jenjang_key] = $lem->nama;
        }

        return $options;
    }

    public function gelombangs()
    {
        return $this->hasMany(PenerimaanGelombang::class, 'penerimaan_santri_id')
            ->orderBy('urutan')
            ->orderBy('tanggal_mulai');
    }

    public function pendaftarans()
    {
        return $this->hasMany(Pendaftaran::class, 'penerimaan_santri_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publicUrl(): string
    {
        return route('spmb.show', $this->slug);
    }

    /**
     * Mengembalikan gelombang yang sedang aktif pada tanggal tertentu dan masih memiliki kuota minimal pada 1 lembaga.
     */
    public function activeGelombang(?Carbon $date = null): ?PenerimaanGelombang
    {
        $today = ($date ?? now())->toDateString();
        $gelombangs = $this->relationLoaded('gelombangs')
            ? $this->gelombangs
            : $this->gelombangs()->get();

        $allowed = $this->allowedJenjangs();

        foreach ($gelombangs as $gel) {
            $start = $gel->tanggal_mulai?->toDateString();
            $end = $gel->tanggal_selesai?->toDateString();
            if ($start && $end && $today >= $start && $today <= $end) {
                if (!empty($gel->availableJenjangs($allowed))) {
                    return $gel;
                }
            }
        }

        return null;
    }

    /**
     * Daftar jenjang yang kuotanya masih tersedia saat ini.
     */
    public function availableJenjangs(?Carbon $date = null): array
    {
        $allowed = $this->allowedJenjangs();
        if (!$this->is_active) {
            return [];
        }

        $gelombangs = $this->relationLoaded('gelombangs')
            ? $this->gelombangs
            : $this->gelombangs()->get();

        if ($gelombangs->isEmpty()) {
            return $allowed;
        }

        $active = $this->activeGelombang($date);
        if (!$active) {
            return [];
        }

        return $active->availableJenjangs($allowed);
    }

    /**
     * Apakah form pendaftaran publik sedang dibuka.
     */
    public function isOpen(?Carbon $date = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $gelombangs = $this->relationLoaded('gelombangs')
            ? $this->gelombangs
            : $this->gelombangs()->get();

        if ($gelombangs->isEmpty()) {
            return true;
        }

        return $this->activeGelombang($date) !== null;
    }

    /**
     * Label status ringkas untuk tampilan admin & publik.
     */
    public function statusState(?Carbon $date = null): array
    {
        if (!$this->is_active) {
            return ['code' => 'tutup', 'label' => 'Ditutup'];
        }

        $gelombangs = $this->relationLoaded('gelombangs')
            ? $this->gelombangs
            : $this->gelombangs()->get();

        if ($gelombangs->isEmpty()) {
            return ['code' => 'buka', 'label' => 'Dibuka'];
        }

        $active = $this->activeGelombang($date);
        if ($active) {
            return ['code' => 'buka', 'label' => 'Dibuka (' . $active->nama_gelombang . ')'];
        }

        $today = ($date ?? now())->toDateString();

        foreach ($gelombangs as $gel) {
            $start = $gel->tanggal_mulai?->toDateString();
            $end = $gel->tanggal_selesai?->toDateString();
            if ($start && $end && $today >= $start && $today <= $end) {
                return ['code' => 'penuh', 'label' => 'Kuota Penuh'];
            }
        }

        $hasUpcoming = $gelombangs->contains(fn ($g) => $g->tanggal_mulai && $g->tanggal_mulai->toDateString() > $today);
        if ($hasUpcoming) {
            return ['code' => 'belum', 'label' => 'Belum Dibuka'];
        }

        return ['code' => 'selesai', 'label' => 'Selesai'];
    }

    /**
     * Pilihan jenjang yang diizinkan pada form publik event ini (dinamis dari lembaga formal).
     */
    public function allowedJenjangs(): array
    {
        $formalKeys = array_keys(self::formalJenjangOptions());

        if (is_array($this->jenjangs) && !empty($this->jenjangs)) {
            $cleaned = array_values(array_unique(array_filter(array_map('trim', $this->jenjangs))));
            if (!empty($cleaned)) {
                return $cleaned;
            }
        }

        $legacy = trim((string) $this->jenjang);
        if ($legacy === '' || strcasecmp($legacy, 'Semua') === 0) {
            return $formalKeys;
        }

        $parts = array_values(array_unique(array_filter(array_map('trim', explode(',', $legacy)))));

        return !empty($parts) ? $parts : $formalKeys;
    }

    /**
     * Label ringkas jenjang terpilih.
     */
    public function jenjangLabel(): string
    {
        return implode(', ', $this->allowedJenjangs());
    }

    /**
     * Map nomor WA panitia per jenjang.
     */
    public function waMap(): array
    {
        $saved = is_array($this->kontak_wa_per_jenjang) ? $this->kontak_wa_per_jenjang : [];
        $map = [];

        foreach ($this->allowedJenjangs() as $j) {
            $val = trim((string) ($saved[$j] ?? ''));
            if ($val === '' && filled($this->kontak_wa) && count($this->allowedJenjangs()) === 1) {
                $val = trim((string) $this->kontak_wa);
            }
            $map[$j] = $val;
        }

        return $map;
    }

    /**
     * Map lokasi seleksi per jenjang.
     */
    public function lokasiMap(): array
    {
        $saved = is_array($this->lokasi_seleksi_per_jenjang) ? $this->lokasi_seleksi_per_jenjang : [];
        $map = [];

        foreach ($this->allowedJenjangs() as $j) {
            $val = trim((string) ($saved[$j] ?? ''));
            if ($val === '' && filled($this->lokasi_seleksi)) {
                $val = trim((string) $this->lokasi_seleksi);
            }
            $map[$j] = $val;
        }

        return $map;
    }

    /**
     * Lokasi seleksi untuk jenjang tertentu (fallback ke lokasi_seleksi umum).
     */
    public function lokasiForJenjang(?string $jenjang = null): ?string
    {
        $saved = is_array($this->lokasi_seleksi_per_jenjang) ? $this->lokasi_seleksi_per_jenjang : [];

        if ($jenjang !== null && !empty($saved[$jenjang])) {
            return trim((string) $saved[$jenjang]);
        }

        if (filled($this->lokasi_seleksi)) {
            return trim((string) $this->lokasi_seleksi);
        }

        foreach ($saved as $loc) {
            if (filled($loc)) {
                return trim((string) $loc);
            }
        }

        return null;
    }

    /**
     * Nomor WA mentah untuk jenjang tertentu.
     */
    public function waNumberForJenjang(?string $jenjang = null): ?string
    {
        $saved = is_array($this->kontak_wa_per_jenjang) ? $this->kontak_wa_per_jenjang : [];

        if ($jenjang !== null && !empty($saved[$jenjang])) {
            return trim((string) $saved[$jenjang]);
        }

        if (filled($this->kontak_wa)) {
            return trim((string) $this->kontak_wa);
        }

        foreach ($saved as $num) {
            if (filled($num)) {
                return trim((string) $num);
            }
        }

        return null;
    }

    /**
     * URL WhatsApp panitia sesuai jenjang.
     */
    public function waUrl(?string $message = null, ?string $jenjang = null): ?string
    {
        $number = $this->waNumberForJenjang($jenjang);
        $digits = preg_replace('/\D+/', '', (string) $number);
        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (!str_starts_with($digits, '62')) {
            $digits = '62' . $digits;
        }

        $url = 'https://wa.me/' . $digits;
        if ($message !== null && $message !== '') {
            $url .= '?text=' . rawurlencode($message);
        }

        return $url;
    }

    /**
     * Daftar URL WA panitia untuk setiap jenjang yang memiliki nomor WA.
     */
    public function waLinksByJenjang(?string $message = null): array
    {
        $links = [];
        $saved = is_array($this->kontak_wa_per_jenjang) ? $this->kontak_wa_per_jenjang : [];

        foreach ($this->allowedJenjangs() as $j) {
            if (!empty($saved[$j])) {
                $url = $this->waUrl($message, $j);
                if ($url) {
                    $links[$j] = $url;
                }
            }
        }

        if (empty($links)) {
            $fallback = $this->waUrl($message);
            if ($fallback) {
                $links['Panitia'] = $fallback;
            }
        }

        return $links;
    }

    /**
     * Konfigurasi syarat dokumen ternormalisasi.
     */
    public function dokumenConfig(): array
    {
        $saved = is_array($this->syarat_dokumen) ? $this->syarat_dokumen : [];
        $config = [];

        foreach (self::DOKUMEN_DEFAULTS as $key => $default) {
            $val = $saved[$key] ?? $default;
            $config[$key] = in_array($val, ['wajib', 'opsional', 'tidak'], true) ? $val : $default;
        }

        return $config;
    }

    /**
     * Baris catatan syarat (tiap baris jadi 1 item).
     */
    public function syaratList(): array
    {
        if (!filled($this->catatan_syarat)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', (string) $this->catatan_syarat)),
            fn ($line) => $line !== ''
        ));
    }

    /**
     * Cek apakah event ini sudah memiliki skema formulir kustom.
     */
    public function hasCustomFormSchema(): bool
    {
        return is_array($this->form_schema) && !empty($this->form_schema['sections']);
    }

    /**
     * Kembalikan skema formulir final (kustom atau standar bawaan).
     */
    public function resolvedFormSchema(): array
    {
        return \App\Support\SpmbFormSchema::resolve($this->form_schema, $this->dokumenConfig());
    }

    /**
     * Cocokkan dan perbarui key jenjang lama pada event ini yang lembaganya sudah berganti nama/singkatan
     * di Master Lembaga (misal "RA", "ra gemarang" -> "RA AL MUTTAQIN KOTA MADIUN", "RA AL MUTTAQIN GEMARANG").
     */
    public function syncStaleJenjangKeys(): bool
    {
        $formalLembagas = Lembaga::formalOptions();
        if ($formalLembagas->isEmpty()) {
            return false;
        }

        $formalByKeyLower = [];
        foreach ($formalLembagas as $lem) {
            $formalByKeyLower[strtolower(trim($lem->jenjang_key))] = $lem;
        }

        $rawList = $this->allowedJenjangs();
        if (empty($rawList)) {
            return false;
        }

        $usedLembagaIds = [];
        $staleItems = [];
        $resolvedMap = []; // rawKey => newJenjangKey

        // Tahap 1: Identifikasi key yang sudah cocok langsung dengan lembaga aktif
        foreach ($rawList as $rawKey) {
            $exact = Lembaga::resolveByKode($rawKey);
            if ($exact && $exact->isFormalSchool()) {
                $usedLembagaIds[$exact->id] = true;
                $resolvedMap[$rawKey] = $exact->jenjang_key;
            } else {
                $staleItems[] = $rawKey;
            }
        }

        if (empty($staleItems)) {
            // Cek apakah ada perbedaan kapitalisasi/singkatan (misal "ra" -> "RA")
            $hasDiff = false;
            foreach ($resolvedMap as $old => $new) {
                if ($old !== $new) {
                    $hasDiff = true;
                    break;
                }
            }
            if (!$hasDiff) {
                return false;
            }
        } else {
            // Tahap 2: Urutkan stale item dari paling spesifik (banyak kata / panjang) agar "ra gemarang"
            // dicocokkan terlebih dahulu ke "RA AL MUTTAQIN GEMARANG" sebelum "RA" ke "RA AL MUTTAQIN KOTA".
            $tokenize = function (string $text): array {
                $parts = preg_split('/[^a-z0-9]+/i', strtolower(trim($text)));
                return array_values(array_unique(array_filter($parts, fn ($t) => $t !== '')));
            };

            usort($staleItems, function ($a, $b) use ($tokenize) {
                $ta = count($tokenize($a));
                $tb = count($tokenize($b));
                if ($ta !== $tb) {
                    return $tb <=> $ta;
                }
                return strlen($b) <=> strlen($a);
            });

            foreach ($staleItems as $staleKey) {
                $tokens = $tokenize($staleKey);
                if (empty($tokens)) {
                    $resolvedMap[$staleKey] = $staleKey;
                    continue;
                }

                $bestMatch = null;
                $bestScore = -1;

                foreach ($formalLembagas as $cand) {
                    if (isset($usedLembagaIds[$cand->id])) {
                        continue;
                    }

                    $candHaystack = $tokenize(implode(' ', [
                        (string) $cand->kode,
                        (string) $cand->singkatan,
                        (string) $cand->nama,
                    ]));

                    $allMatch = true;
                    foreach ($tokens as $tok) {
                        if (!in_array($tok, $candHaystack, true)) {
                            $allMatch = false;
                            break;
                        }
                    }

                    if ($allMatch) {
                        // Skor: prioritas lembaga yang memiliki kecocokan token lebih padat & urutan lebih awal
                        $score = (count($tokens) * 100) - count($candHaystack);
                        if ($score > $bestScore) {
                            $bestScore = $score;
                            $bestMatch = $cand;
                        }
                    }
                }

                if ($bestMatch) {
                    $usedLembagaIds[$bestMatch->id] = true;
                    $resolvedMap[$staleKey] = $bestMatch->jenjang_key;
                } else {
                    $resolvedMap[$staleKey] = $staleKey;
                }
            }
        }

        $changed = false;
        $newList = [];
        foreach ($rawList as $rawKey) {
            $mapped = $resolvedMap[$rawKey] ?? $rawKey;
            if ($mapped !== $rawKey) {
                $changed = true;
            }
            $newList[] = $mapped;
        }
        $newList = array_values(array_unique($newList));

        if (!$changed) {
            return false;
        }

        $remapAssoc = function (?array $map) use ($resolvedMap): ?array {
            if (!is_array($map)) {
                return $map;
            }
            $out = [];
            foreach ($map as $k => $v) {
                $nk = $resolvedMap[$k] ?? $k;
                if (!array_key_exists($nk, $out) || filled($v)) {
                    $out[$nk] = $v;
                }
            }
            return $out;
        };

        $this->update([
            'jenjangs'                   => $newList,
            'jenjang'                    => strcasecmp(trim((string) $this->jenjang), 'Semua') === 0 ? 'Semua' : implode(', ', $newList),
            'kontak_wa_per_jenjang'      => $remapAssoc($this->kontak_wa_per_jenjang),
            'lokasi_seleksi_per_jenjang' => $remapAssoc($this->lokasi_seleksi_per_jenjang),
        ]);

        $gelombangs = $this->relationLoaded('gelombangs') ? $this->gelombangs : $this->gelombangs()->get();
        foreach ($gelombangs as $gel) {
            $newKuota = $remapAssoc($gel->kuota_per_jenjang);
            if ($newKuota !== $gel->kuota_per_jenjang) {
                $gel->update(['kuota_per_jenjang' => $newKuota]);
            }
        }

        return true;
    }

    /**
     * Sinkronkan seluruh event SPMB yang memiliki key jenjang usang.
     */
    public static function syncAllStaleJenjangKeys(): int
    {
        $updated = 0;
        foreach (static::with('gelombangs')->get() as $event) {
            if ($event->syncStaleJenjangKeys()) {
                $updated++;
            }
        }
        return $updated;
    }

    /**
     * Label tampilan terbaru untuk tiap opsi jenjang pada dropdown form publik.
     */
    public function jenjangDisplayLabels(): array
    {
        $labels = [];
        foreach ($this->allowedJenjangs() as $jKey) {
            $lem = Lembaga::resolveByKode($jKey);
            $labels[$jKey] = $lem ? ($lem->singkatan ?: $lem->nama) : $jKey;
        }
        return $labels;
    }

    /**
     * Format tahun ajaran ringkas untuk nomor registrasi: "2027/2028" -> "202728".
     */
    public function tahunAjaranCode(): string
    {
        $raw = trim((string) $this->tahun_ajaran);

        if (preg_match('/(\d{4})\D+(\d{2,4})/', $raw, $m)) {
            return $m[1] . substr($m[2], -2);
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if (strlen($digits) === 8) {
            return substr($digits, 0, 4) . substr($digits, -2);
        }
        if (strlen($digits) >= 4) {
            return $digits;
        }

        $y = (int) date('Y');
        return $y . substr((string) ($y + 1), -2);
    }
}
