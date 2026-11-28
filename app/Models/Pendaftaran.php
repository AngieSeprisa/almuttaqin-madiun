<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pendaftaran extends Model
{
    use HasFactory;

    /**
     * Pemetaan kolom foto berkas => [kolom checklist, label].
     */
    public const FOTO_BERKAS = [
        'foto_akta' => ['berkas_akta', 'Akta Kelahiran'],
        'foto_kk' => ['berkas_kk', 'Kartu Keluarga (KK)'],
        'foto_ktp_ortu' => ['berkas_ktp_ortu', 'KTP Orang Tua / Wali'],
        'foto_surat_pernyataan' => ['berkas_surat_pernyataan', 'Surat Pernyataan'],
    ];

    public const STATUSES = [
        'Pending',
        'Lulus Seleksi',
        'Diterima',
        'Ditolak',
    ];

    protected $fillable = [
        'penerimaan_santri_id',
        'penerimaan_gelombang_id',
        'jenjang',
        'is_lanjut_jenjang',
        'jenjang_sebelumnya',
        'siswa_asal_id',
        'no_induk',
        'public_token',
        'nik',
        'nisn',
        'nama_lengkap',
        'nama_panggilan',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'anak_ke',
        'jumlah_saudara',
        'tinggal_bersama',
        'mulai_pengajian',
        'alamat_jalan',
        'rt_rw',
        'desa_kelurahan',
        'kecamatan',
        'kab_kota',
        'kode_pos',
        'provinsi',
        'warga_negara',
        'kelompok',
        'desa',
        'daerah',
        'pendidikan_terakhir',
        'nama_sekolah_asal',
        'jurusan',
        'tahun_lulus',
        'status_mondok',
        'pj_biaya_nama',
        'pj_biaya_hp',
        'penerima_kip',
        'nomor_kip',
        'penyakit_diderita',
        'alergi',
        'gol_darah',
        'tinggi_badan',
        'berat_badan',
        'keahlian_prestasi',
        'ayah_nama',
        'ayah_status',
        'ayah_ttl',
        'ayah_pendidikan',
        'ayah_pekerjaan',
        'ayah_penghasilan',
        'ayah_agama',
        'ayah_alamat',
        'ayah_wn',
        'ayah_hp',
        'ayah_daerah',
        'ayah_desa',
        'ayah_kelompok',
        'ibu_nama',
        'ibu_status',
        'ibu_ttl',
        'ibu_pendidikan',
        'ibu_pekerjaan',
        'ibu_penghasilan',
        'ibu_agama',
        'ibu_alamat',
        'ibu_wn',
        'ibu_hp',
        'ibu_daerah',
        'ibu_desa',
        'ibu_kelompok',
        'berkas_akta',
        'berkas_kk',
        'berkas_ktp_ortu',
        'berkas_surat_pernyataan',
        'foto_akta',
        'foto_kk',
        'foto_ktp_ortu',
        'foto_surat_pernyataan',
        'custom_responses',
        'status_pendaftaran',
    ];

    /**
     * URL publik foto berkas (null bila belum diunggah).
     */
    public function fotoUrl(string $field): ?string
    {
        return $this->{$field} ? asset('storage/' . $this->{$field}) : null;
    }

    protected $casts = [
        'tanggal_lahir' => 'date',
        'anak_ke' => 'integer',
        'jumlah_saudara' => 'integer',
        'tinggi_badan' => 'integer',
        'berat_badan' => 'integer',
        'berkas_akta' => 'boolean',
        'berkas_kk' => 'boolean',
        'berkas_ktp_ortu' => 'boolean',
        'berkas_surat_pernyataan' => 'boolean',
        'penerima_kip' => 'boolean',
        'is_lanjut_jenjang' => 'boolean',
        'custom_responses' => 'array',
    ];

    /**
     * Scope filter pendaftaran berdasarkan ruang kerja/lembaga aktif.
     */
    public function scopeForLembaga($query, ?string $kodeLembaga)
    {
        if (Lembaga::isGlobalRoom($kodeLembaga)) {
            return $query;
        }

        $aliases = array_map('strtolower', Lembaga::jenjangAliasesForRoom($kodeLembaga));
        if (empty($aliases)) {
            return $query;
        }

        return $query->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(TRIM(jenjang))'), $aliases);
    }

    /**
     * Konfigurasi syarat dokumen yang aktif untuk pendaftaran ini.
     * Jika berasal dari Link Penerimaan (SPMB), mengikuti pengaturan event tersebut;
     * jika input manual admin tanpa event, seluruh berkas standar aktif (opsional).
     */
    public function activeDokumenConfig(): array
    {
        $penerimaan = $this->relationLoaded('penerimaan')
            ? $this->getRelation('penerimaan')
            : ($this->penerimaan_santri_id ? $this->penerimaan()->first() : null);

        if ($penerimaan) {
            return $penerimaan->dokumenConfig();
        }

        return PenerimaanSantri::DOKUMEN_DEFAULTS;
    }

    /**
     * Daftar item persyaratan yang perlu ditampilkan pada formulir cetak A4,
     * hanya menyertakan dokumen yang diaktifkan (Wajib / Opsional) dan mengecualikan yang Tidak Diminta.
     */
    public function activePersyaratanForPrint(): array
    {
        $dokConfig = $this->activeDokumenConfig();
        $penerimaan = $this->relationLoaded('penerimaan')
            ? $this->getRelation('penerimaan')
            : ($this->penerimaan_santri_id ? $this->penerimaan()->first() : null);

        // Cek apakah admin memberi label kustom pada field dokumen bawaan di Form Builder
        $customLabels = [];
        if ($penerimaan && $penerimaan->hasCustomFormSchema()) {
            $catalog = \App\Support\SpmbFormSchema::builtinCatalog();
            foreach (($penerimaan->form_schema['sections'] ?? []) as $sec) {
                foreach (($sec['fields'] ?? []) as $fld) {
                    $fKey = $fld['key'] ?? '';
                    if (($fld['source'] ?? '') === 'builtin' && isset(\App\Support\SpmbFormSchema::DOKUMEN_FIELD_TO_FLAG[$fKey])) {
                        $flagKey = \App\Support\SpmbFormSchema::DOKUMEN_FIELD_TO_FLAG[$fKey];
                        $submittedLabel = trim((string) ($fld['label'] ?? ''));
                        $defaultLabel = $catalog[$fKey]['label'] ?? '';
                        if ($submittedLabel !== '' && $submittedLabel !== $defaultLabel) {
                            $customLabels[$flagKey] = $submittedLabel;
                        }
                    }
                }
            }
        }

        $printDefs = [
            'berkas_akta' => [
                'foto'       => 'foto_akta',
                'label'      => 'Foto Copy Akta Kelahiran',
                'html_label' => 'Foto Copy Akta Kelahiran',
            ],
            'berkas_kk' => [
                'foto'       => 'foto_kk',
                'label'      => 'Foto Copy Kartu Keluarga',
                'html_label' => 'Foto Copy Kartu Keluarga',
            ],
            'berkas_ktp_ortu' => [
                'foto'       => 'foto_ktp_ortu',
                'label'      => 'Foto Copy KTP Ayah & Ibu',
                'html_label' => 'Foto Copy KTP Ayah &amp; Ibu',
            ],
            'berkas_surat_pernyataan' => [
                'foto'       => 'foto_surat_pernyataan',
                'label'      => 'Surat Sanggup Mentaati Peraturan Pondok',
                'html_label' => 'Surat Sanggup Mentaati<br>Peraturan Pondok',
            ],
        ];

        $items = [];
        foreach ($printDefs as $flagKey => $meta) {
            $mode = $dokConfig[$flagKey] ?? 'opsional';
            if ($mode === 'tidak') {
                continue;
            }

            $label = $customLabels[$flagKey] ?? $meta['label'];
            $htmlLabel = isset($customLabels[$flagKey]) ? e($customLabels[$flagKey]) : $meta['html_label'];

            $items[] = [
                'flag'       => $flagKey,
                'foto'       => $meta['foto'],
                'label'      => $label,
                'html_label' => $htmlLabel,
                'mode'       => $mode,
                'checked'    => (bool) $this->{$flagKey},
            ];
        }

        return $items;
    }

    /**
     * Relasi ke data siswa
     */
    public function siswa()
    {
        return $this->hasOne(Siswa::class);
    }

    /**
     * Relasi ke data siswa internal asal (khusus pendaftar lanjut jenjang, mis. SMP -> SMA)
     */
    public function siswaAsal()
    {
        return $this->belongsTo(Siswa::class, 'siswa_asal_id');
    }

    /**
     * Deteksi apakah calon pendaftar merupakan siswa/alumni internal lembaga Al Muttaqin
     * dari jenjang sebelumnya (misal RA -> SD IT, SD IT -> SMP, SMP -> SMA).
     */
    public static function detectInternalStudent(
        ?string $nik,
        ?string $nisn,
        ?string $namaLengkap,
        ?string $tanggalLahir,
        ?string $targetJenjang
    ): ?array {
        $targetNorm = strtolower(trim((string) $targetJenjang));
        $cleanNik = preg_replace('/\D+/', '', trim((string) $nik));
        $cleanNisn = preg_replace('/\D+/', '', trim((string) $nisn));
        $cleanNama = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $namaLengkap)));
        $cleanTgl = '';
        if (!empty($tanggalLahir)) {
            try {
                $cleanTgl = \Illuminate\Support\Carbon::parse($tanggalLahir)->toDateString();
            } catch (\Throwable $e) {
                $cleanTgl = '';
            }
        }

        $isDifferentJenjang = function (?string $jenjang) use ($targetNorm): bool {
            $j = strtolower(trim((string) $jenjang));
            return $j !== '' && ($targetNorm === '' || $j !== $targetNorm);
        };

        // 1. Cari lewat NIK pada pendaftaran sebelumnya di jenjang berbeda
        if ($cleanNik !== '' && strlen($cleanNik) >= 10) {
            $prevPend = static::where('nik', $cleanNik)
                ->where(function ($q) {
                    $q->whereNull('status_pendaftaran')
                      ->orWhere('status_pendaftaran', '!=', 'Ditolak');
                })
                ->orderByDesc('id')
                ->get()
                ->first(fn ($p) => $isDifferentJenjang($p->jenjang));

            if ($prevPend) {
                $siswa = Siswa::where('pendaftaran_id', $prevPend->id)->first();
                return [
                    'is_lanjut_jenjang'  => true,
                    'jenjang_sebelumnya' => $siswa?->jenjang ?: $prevPend->jenjang,
                    'siswa_asal_id'      => $siswa?->id,
                ];
            }
        }

        // 2. Cari lewat NISN pada pendaftaran sebelumnya di jenjang berbeda
        if ($cleanNisn !== '' && strlen($cleanNisn) >= 5 && trim($cleanNisn, '0') !== '') {
            $prevPend = static::where('nisn', $cleanNisn)
                ->where(function ($q) {
                    $q->whereNull('status_pendaftaran')
                      ->orWhere('status_pendaftaran', '!=', 'Ditolak');
                })
                ->orderByDesc('id')
                ->get()
                ->first(fn ($p) => $isDifferentJenjang($p->jenjang));

            if ($prevPend) {
                $siswa = Siswa::where('pendaftaran_id', $prevPend->id)->first();
                return [
                    'is_lanjut_jenjang'  => true,
                    'jenjang_sebelumnya' => $siswa?->jenjang ?: $prevPend->jenjang,
                    'siswa_asal_id'      => $siswa?->id,
                ];
            }
        }

        // 3. Cari lewat Nama Lengkap + Tanggal Lahir pada pendaftaran sebelumnya di jenjang berbeda
        if ($cleanNama !== '' && $cleanTgl !== '') {
            $prevPend = static::whereDate('tanggal_lahir', $cleanTgl)
                ->where(function ($q) {
                    $q->whereNull('status_pendaftaran')
                      ->orWhere('status_pendaftaran', '!=', 'Ditolak');
                })
                ->orderByDesc('id')
                ->get()
                ->first(function ($p) use ($cleanNama, $isDifferentJenjang) {
                    $pNama = strtolower(trim((string) preg_replace('/\s+/', ' ', (string) $p->nama_lengkap)));
                    return $pNama === $cleanNama && $isDifferentJenjang($p->jenjang);
                });

            if ($prevPend) {
                $siswa = Siswa::where('pendaftaran_id', $prevPend->id)->first();
                return [
                    'is_lanjut_jenjang'  => true,
                    'jenjang_sebelumnya' => $siswa?->jenjang ?: $prevPend->jenjang,
                    'siswa_asal_id'      => $siswa?->id,
                ];
            }
        }

        // 4. Cari langsung pada tabel siswas (untuk siswa lama yang dulu diimpor langsung tanpa lewat form SPMB)
        if ($cleanNama !== '') {
            $matchedSiswa = Siswa::with('pendaftaran')
                ->whereRaw('LOWER(TRIM(nama_lengkap)) = ?', [$cleanNama])
                ->orderByDesc('id')
                ->get()
                ->first(function ($sw) use ($isDifferentJenjang, $cleanTgl, $cleanNik, $cleanNisn) {
                    if (!$isDifferentJenjang($sw->jenjang)) {
                        return false;
                    }
                    // Jika siswa punya pendaftaran dengan NIK/TTL berbeda, jangan salah cocokkan nama pasaran
                    if ($sw->pendaftaran) {
                        $swNik = preg_replace('/\D+/', '', (string) ($sw->pendaftaran->nik ?? ''));
                        if ($cleanNik !== '' && $swNik !== '' && $cleanNik !== $swNik) {
                            return false;
                        }
                        $swNisn = preg_replace('/\D+/', '', (string) ($sw->pendaftaran->nisn ?? ''));
                        if ($cleanNisn !== '' && $swNisn !== '' && $cleanNisn !== $swNisn) {
                            return false;
                        }
                        $swTgl = $sw->pendaftaran->tanggal_lahir?->toDateString() ?? '';
                        if ($cleanTgl !== '' && $swTgl !== '' && $cleanTgl !== $swTgl) {
                            return false;
                        }
                    }
                    return true;
                });

            if ($matchedSiswa) {
                return [
                    'is_lanjut_jenjang'  => true,
                    'jenjang_sebelumnya' => $matchedSiswa->jenjang,
                    'siswa_asal_id'      => $matchedSiswa->id,
                ];
            }
        }

        return null;
    }

    /**
     * Relasi ke event penerimaan santri
     */
    public function penerimaan()
    {
        return $this->belongsTo(PenerimaanSantri::class, 'penerimaan_santri_id');
    }

    /**
     * Relasi ke gelombang penerimaan
     */
    public function gelombang()
    {
        return $this->belongsTo(PenerimaanGelombang::class, 'penerimaan_gelombang_id');
    }
}
