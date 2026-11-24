<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Lembaga extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama',
        'singkatan',
        'prefix_registrasi',
        'kategori',
        'alamat',
        'logo',
        'telepon',
        'email',
        'icon',
        'is_active',
        'urutan',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'urutan' => 'integer',
    ];

    /**
     * User yang terafiliasi dengan lembaga ini.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_lembagas', 'lembaga_id', 'user_id');
    }

    /**
     * URL logo khusus lembaga atau fallback ke logo bawaan.
     */
    public function getLogoUrlAttribute(): string
    {
        if (!empty($this->logo) && Storage::disk('public')->exists($this->logo)) {
            return asset('storage/' . $this->logo);
        }

        if ($this->kode === 'smp' && file_exists(public_path('images/logo-smp.png'))) {
            return asset('images/logo-smp.png');
        }

        if ($this->kode === 'sma' && file_exists(public_path('images/logo-sma.png'))) {
            return asset('images/logo-sma.png');
        }

        if ($this->kode === 'pondok' && file_exists(public_path('images/logo-pondok.png'))) {
            return asset('images/logo-pondok.png');
        }

        return asset('images/logo.png');
    }

    /**
     * Cek apakah kode room merupakan ruang global (Yayasan / Pondok).
     */
    public static function isGlobalRoom(?string $kode): bool
    {
        $clean = strtolower(trim((string) $kode));

        return $clean === '' || in_array($clean, ['yayasan', 'pondok'], true);
    }

    /**
     * Cek apakah lembaga ini termasuk lembaga pendidikan formal (SMP, SMA, SD IT, RA, dll).
     */
    public function isFormalSchool(): bool
    {
        $kode = strtolower(trim((string) $this->kode));
        if (in_array($kode, ['yayasan', 'pondok'], true)) {
            return false;
        }

        return in_array(strtolower(trim((string) $this->kategori)), ['formal', 'sekolah'], true)
            || in_array($kode, ['smp', 'sma', 'smk', 'sdit', 'sd', 'ra', 'tk', 'mi', 'mts', 'ma'], true);
    }

    /**
     * Daftar lembaga formal aktif yang terdaftar (SMP, SMA, SD IT, RA, dll).
     */
    public static function formalOptions()
    {
        return static::where('is_active', true)
            ->whereRaw("LOWER(kode) NOT IN ('yayasan', 'pondok')")
            ->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(kategori, '')) IN ('formal', 'sekolah')")
                  ->orWhereRaw("LOWER(kode) IN ('smp', 'sma', 'smk', 'sdit', 'sd', 'ra', 'tk', 'mi', 'mts', 'ma')");
            })
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
    }

    /**
     * Temukan lembaga aktif berdasarkan kode atau singkatan secara case-insensitive.
     */
    public static function resolveByKode(?string $kode): ?self
    {
        $clean = strtolower(trim((string) $kode));
        if ($clean === '') {
            return null;
        }

        $compact = preg_replace('/[\s_-]+/', '', $clean);

        return static::where('is_active', true)->orderBy('urutan')->get()->first(function (self $l) use ($clean, $compact) {
            $lKode = strtolower(trim((string) $l->kode));
            $lSingkat = strtolower(trim((string) $l->singkatan));
            $lKodeCompact = preg_replace('/[\s_-]+/', '', $lKode);
            $lSingkatCompact = preg_replace('/[\s_-]+/', '', $lSingkat);

            return $lKode === $clean
                || $lSingkat === $clean
                || ($compact !== '' && ($lKodeCompact === $compact || $lSingkatCompact === $compact));
        });
    }

    /**
     * Ambil seluruh alias penulisan jenjang untuk suatu kode room (misal 'sdit' => ['SD IT', 'SDIT', 'sdit', 'sd it']).
     */
    public static function jenjangAliasesForRoom(?string $kodeLembaga): array
    {
        $clean = trim((string) $kodeLembaga);
        if ($clean === '' || static::isGlobalRoom($clean)) {
            return [];
        }

        $lembaga = static::resolveByKode($clean);
        $rawList = [$clean, strtoupper($clean), strtolower($clean)];

        if ($lembaga) {
            foreach ([$lembaga->kode, $lembaga->singkatan, $lembaga->jenjang_key, $lembaga->nama] as $val) {
                $v = trim((string) $val);
                if ($v !== '') {
                    $rawList[] = $v;
                    $rawList[] = strtoupper($v);
                    $rawList[] = strtolower($v);
                    $noSpace = preg_replace('/\s+/', '', $v);
                    if ($noSpace !== '') {
                        $rawList[] = $noSpace;
                        $rawList[] = strtoupper($noSpace);
                        $rawList[] = strtolower($noSpace);
                    }
                }
            }
        }

        return array_values(array_unique(array_filter($rawList, fn ($v) => trim((string) $v) !== '')));
    }

    /**
     * Jenjang baku untuk kode room aktif (misal 'sdit' => 'SD IT', 'ra' => 'RA', 'smp' => 'SMP').
     */
    public static function defaultJenjangForRoom(?string $kodeLembaga): string
    {
        if (static::isGlobalRoom($kodeLembaga)) {
            return 'Reguler';
        }

        $lembaga = static::resolveByKode($kodeLembaga);
        if ($lembaga) {
            return $lembaga->jenjang_key;
        }

        return strtoupper(trim((string) $kodeLembaga)) ?: 'Reguler';
    }

    /**
     * Daftar seluruh opsi jenjang aktif di sistem (Reguler + seluruh lembaga formal terdaftar).
     */
    public static function allJenjangOptions(bool $includeReguler = true): array
    {
        $list = $includeReguler ? ['Reguler'] : [];
        foreach (static::formalOptions() as $lemb) {
            $key = $lemb->jenjang_key;
            if ($key !== '' && !in_array($key, $list, true)) {
                $list[] = $key;
            }
        }

        if (count($list) <= ($includeReguler ? 1 : 0)) {
            $list = array_values(array_unique(array_merge($list, ['SMP', 'SMA'])));
        }

        return $list;
    }

    /**
     * Dapatkan model Lembaga yang sedang aktif dan divalidasi terhadap hak akses user.
     * Mengembalikan null jika room aktif adalah global ('yayasan' atau 'pondok').
     */
    public static function activeScopedFormal(?User $user = null, ?string $roomKode = null): ?self
    {
        $authUser = $user ?? auth()->user();
        $activeKode = $roomKode ?? session('active_lembaga');

        if ($authUser && !$authUser->isYayasan()) {
            if (!$activeKode || !$authUser->hasAccessToLembaga($activeKode)) {
                $current = $authUser->currentLembaga();
                $activeKode = $current?->kode;
            }
        }

        if (static::isGlobalRoom($activeKode)) {
            return null;
        }

        return static::resolveByKode($activeKode);
    }

    /**
     * Awalan (prefix) nomor registrasi SPMB untuk lembaga ini.
     * Menggunakan prefix_registrasi kustom jika diisi, atau fallback dari singkatan/kode.
     */
    public function registrationPrefix(): string
    {
        $custom = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) ($this->prefix_registrasi ?? '')));
        if ($custom !== '') {
            return substr($custom, 0, 20);
        }

        $fallback = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $this->jenjang_key));

        return substr($fallback, 0, 12) ?: 'SANTRI';
    }

    /**
     * Kunci jenjang standar untuk lembaga ini (misal: SMP, SMA, SMK).
     */
    public function getJenjangKeyAttribute(): string
    {
        $key = trim((string) ($this->singkatan ?: strtoupper((string) $this->kode)));

        return $key !== '' ? $key : strtoupper((string) $this->kode);
    }

    /**
     * Temukan model Lembaga sesuai jenjang pendaftaran (dinamis dari tabel lembagas).
     */
    public static function resolveForJenjang(?string $jenjang): ?self
    {
        $clean = trim((string) $jenjang);
        $lower = strtolower($clean);

        if ($lower === 'reguler' || $lower === 'pondok') {
            return static::where('kode', 'pondok')->first()
                ?? static::where('kode', 'yayasan')->first();
        }

        if ($clean !== '') {
            $matched = static::whereRaw('LOWER(kode) = ?', [$lower])
                ->orWhereRaw('LOWER(singkatan) = ?', [$lower])
                ->orWhereRaw('LOWER(nama) = ?', [$lower])
                ->first();

            if ($matched) {
                return $matched;
            }
        }

        return static::where('kode', 'yayasan')->first();
    }

    /**
     * Data KOP surat siap pakai sesuai jenjang / lembaga masing-masing.
     */
    public static function kopForJenjang(?string $jenjang): array
    {
        $lembaga = static::resolveForJenjang($jenjang);
        $yayasan = ($lembaga && $lembaga->kode === 'yayasan')
            ? $lembaga
            : static::where('kode', 'yayasan')->first();

        $defaultAlamat = 'Jln. Nogososro No. 26 Josenan Taman Kota Madiun Jawa Timur Kode Pos : 63134';
        $defaultTelepon = '+6285785028124';
        $defaultEmail = 'ponpesalmuttaqin001@gmail.com';
        $defaultYayasanHeader = 'YAYASAN PONDOK PESANTREN MUTTAQIN JOSENAN MADIUN';

        $isYayasanSelf = !$lembaga || strtolower((string) $lembaga->kode) === 'yayasan';
        $yayasanHeader = $isYayasanSelf ? null : $defaultYayasanHeader;

        $rawNama = $lembaga
            ? strtoupper(trim((string) $lembaga->nama))
            : $defaultYayasanHeader;

        $namaLines = static::formatKopNamaLines($rawNama);

        $alamat = ($lembaga && filled($lembaga->alamat))
            ? $lembaga->alamat
            : (($yayasan && filled($yayasan->alamat)) ? $yayasan->alamat : $defaultAlamat);

        $telepon = ($lembaga && filled($lembaga->telepon))
            ? $lembaga->telepon
            : (($yayasan && filled($yayasan->telepon)) ? $yayasan->telepon : $defaultTelepon);

        $email = ($lembaga && filled($lembaga->email))
            ? $lembaga->email
            : (($yayasan && filled($yayasan->email)) ? $yayasan->email : $defaultEmail);

        $logoUrl = $lembaga ? $lembaga->logo_url : asset('images/logo.png');

        return [
            'lembaga'      => $lembaga,
            'yayasan_nama' => $yayasanHeader,
            'nama'         => $rawNama,
            'nama_lines'   => $namaLines,
            'alamat'       => $alamat,
            'telepon'      => $telepon,
            'email'        => $email,
            'logo_url'     => $logoUrl,
        ];
    }

    /**
     * Format nama lembaga pada KOP surat menjadi 1-2 baris proporsional.
     */
    protected static function formatKopNamaLines(string $rawNama): array
    {
        $normalized = preg_replace('/\s+/', ' ', strtoupper(trim($rawNama)));

        $expansions = [
            'SMP AL MUTTAQIN'                => ['SEKOLAH MENENGAH PERTAMA', 'AL MUTTAQIN'],
            'SMA AL MUTTAQIN'                => ['SEKOLAH MENENGAH ATAS', 'AL MUTTAQIN'],
            'SMK AL MUTTAQIN'                => ['SEKOLAH MENENGAH KEJURUAN', 'AL MUTTAQIN'],
            'SD IT AL MUTTAQIN'              => ['SEKOLAH DASAR ISLAM TERPADU', 'AL MUTTAQIN'],
            'SDIT AL MUTTAQIN'               => ['SEKOLAH DASAR ISLAM TERPADU', 'AL MUTTAQIN'],
            'RA AL MUTTAQIN'                 => ['RAUDHATUL ATHFAL', 'AL MUTTAQIN'],
            'PONDOK PESANTREN AL MUTTAQIN'   => ['PONDOK PESANTREN', 'AL MUTTAQIN'],
        ];

        if (isset($expansions[$normalized])) {
            return $expansions[$normalized];
        }

        // Bila admin menulis nama panjang berakhiran "AL MUTTAQIN", pecah rapi sebelum "AL MUTTAQIN"
        if (str_ends_with($normalized, ' AL MUTTAQIN') && strlen($normalized) > 22) {
            $prefix = trim(substr($normalized, 0, -strlen(' AL MUTTAQIN')));
            if ($prefix !== '') {
                return [$prefix, 'AL MUTTAQIN'];
            }
        }

        return [$normalized];
    }

    /**
     * Pengaturan absensi lembaga.
     */
    public function pengaturanAbsensi()
    {
        return $this->hasOne(PengaturanAbsensi::class);
    }

    /**
     * Relasi mata pelajaran yang diajarkan di lembaga ini.
     */
    public function mataPelajarans()
    {
        return $this->hasMany(MataPelajaran::class);
    }

    /**
     * Dapatkan atau buat pengaturan absensi default untuk lembaga ini.
     */
    public function getOrCreatePengaturanAbsensi(): PengaturanAbsensi
    {
        return $this->pengaturanAbsensi ?? $this->pengaturanAbsensi()->create([
            'jam_masuk'                  => '07:00:00',
            'toleransi_terlambat_menit'  => 15,
            'jam_pulang'                 => '15:30:00',
            'latitude'                   => -7.327434,
            'longitude'                  => 108.220761,
            'radius_meter'               => 100,
            'is_active'                  => true,
            'qr_secret_key'              => bin2hex(random_bytes(16)),
        ]);
    }

    /**
     * Teks judul lembaga untuk sidebar & switch lembaga.
     */
    public function getBrandTitleAttribute(): string
    {
        if (filled($this->nama)) {
            return strtoupper(trim((string) $this->nama));
        }

        return match (strtolower((string) $this->kode)) {
            'smp'     => 'SMP AL MUTTAQIN',
            'sma'     => 'SMA AL MUTTAQIN',
            'yayasan' => 'YAYASAN PONDOK PESANTREN MUTTAQIN',
            default   => strtoupper(trim((string) ($this->singkatan ?: $this->kode ?: 'PONDOK PESANTREN AL MUTTAQIN'))),
        };
    }

    /**
     * Propagasi perubahan singkatan / kode lembaga ke seluruh modul yang menyimpan label jenjang
     * (Link Penerimaan SPMB, Gelombang, Pendaftaran, Siswa, Kelas, Ekskul, Jam Pelajaran).
     */
    public static function propagateRename(self $lembaga, array $oldAliases, string $newJenjangKey): void
    {
        $newKey = trim($newJenjangKey);
        if ($newKey === '') {
            return;
        }

        $oldLower = array_values(array_unique(array_filter(array_map(
            fn ($v) => strtolower(trim((string) $v)),
            $oldAliases
        ))));
        if (empty($oldLower)) {
            return;
        }

        $matchesOld = function ($value) use ($oldLower): bool {
            return in_array(strtolower(trim((string) $value)), $oldLower, true);
        };

        $replaceAssocKeys = function (?array $map) use ($matchesOld, $newKey): ?array {
            if (!is_array($map)) {
                return $map;
            }
            $updated = [];
            foreach ($map as $k => $v) {
                $targetKey = $matchesOld($k) ? $newKey : $k;
                if (!array_key_exists($targetKey, $updated) || filled($v)) {
                    $updated[$targetKey] = $v;
                }
            }
            return $updated;
        };

        // 1. Update PenerimaanSantri & PenerimaanGelombang
        if (\Illuminate\Support\Facades\Schema::hasTable('penerimaan_santris')) {
            $events = PenerimaanSantri::with('gelombangs')->get();
            foreach ($events as $event) {
                $dirty = false;

                $rawList = is_array($event->jenjangs) && !empty($event->jenjangs)
                    ? $event->jenjangs
                    : array_values(array_filter(array_map('trim', explode(',', (string) $event->jenjang))));

                $newList = [];
                foreach ($rawList as $item) {
                    if ($matchesOld($item)) {
                        $newList[] = $newKey;
                        if ($item !== $newKey) {
                            $dirty = true;
                        }
                    } else {
                        $newList[] = $item;
                    }
                }
                $newList = array_values(array_unique($newList));

                $newWa = $replaceAssocKeys($event->kontak_wa_per_jenjang);
                $newLok = $replaceAssocKeys($event->lokasi_seleksi_per_jenjang);

                if ($newWa !== $event->kontak_wa_per_jenjang || $newLok !== $event->lokasi_seleksi_per_jenjang) {
                    $dirty = true;
                }

                if ($dirty) {
                    $event->update([
                        'jenjangs'                   => $newList,
                        'jenjang'                    => strcasecmp(trim((string) $event->jenjang), 'Semua') === 0 ? 'Semua' : implode(', ', $newList),
                        'kontak_wa_per_jenjang'      => $newWa,
                        'lokasi_seleksi_per_jenjang' => $newLok,
                    ]);
                }

                foreach ($event->gelombangs as $gel) {
                    $newKuota = $replaceAssocKeys($gel->kuota_per_jenjang);
                    if ($newKuota !== $gel->kuota_per_jenjang) {
                        $gel->update(['kuota_per_jenjang' => $newKuota]);
                    }
                }
            }
        }

        // 2. Update tabel pendaftarans, siswas, time_slots (berdasarkan alias lama)
        foreach (['pendaftarans', 'siswas', 'time_slots'] as $tableName) {
            if (\Illuminate\Support\Facades\Schema::hasTable($tableName) && \Illuminate\Support\Facades\Schema::hasColumn($tableName, 'jenjang')) {
                \Illuminate\Support\Facades\DB::table($tableName)
                    ->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(jenjang))'), $oldLower)
                    ->update(['jenjang' => $newKey]);
            }
        }

        // 3. Update classrooms & ekstrakurikulers berdasarkan lembaga_id
        if (\Illuminate\Support\Facades\Schema::hasTable('classrooms')) {
            Classroom::where('lembaga_id', $lembaga->id)->update(['jenjang' => $newKey]);
        }
        if (\Illuminate\Support\Facades\Schema::hasTable('ekstrakurikulers')) {
            Ekstrakurikuler::where('lembaga_id', $lembaga->id)
                ->whereRaw("UPPER(COALESCE(jenjang, '')) != 'ALL'")
                ->update(['jenjang' => $newKey]);
        }
    }

    /**
     * Nama singkat lembaga untuk tampilan filter & ringkas (Yayasan, Pondok, SMA, SMP).
     */
    public function getNamaSingkatAttribute(): string
    {
        return match (strtolower($this->kode)) {
            'yayasan' => 'Yayasan',
            'pondok'  => 'Pondok',
            'sma'     => 'SMA',
            'smp'     => 'SMP',
            default   => $this->singkatan ?: $this->nama,
        };
    }
}

