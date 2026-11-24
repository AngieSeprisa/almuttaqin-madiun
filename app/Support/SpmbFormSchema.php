<?php

namespace App\Support;

use App\Models\PenerimaanSantri;
use Illuminate\Support\Str;

class SpmbFormSchema
{
    public const FIELD_TYPES = [
        'text'     => 'Jawaban Singkat',
        'textarea' => 'Paragraf',
        'number'   => 'Angka',
        'date'     => 'Tanggal',
        'radio'    => 'Pilihan Ganda',
        'checkbox' => 'Kotak Centang',
        'select'   => 'Dropdown',
        'file'     => 'Upload Berkas / Foto',
    ];

    public const LOCKED_BUILTIN_KEYS = ['jenjang', 'nama_lengkap'];

    public const DOKUMEN_FIELD_TO_FLAG = [
        'foto_akta'             => 'berkas_akta',
        'foto_kk'               => 'berkas_kk',
        'foto_ktp_ortu'         => 'berkas_ktp_ortu',
        'foto_surat_pernyataan' => 'berkas_surat_pernyataan',
    ];

    /**
     * Katalog seluruh field bawaan sistem yang terpetakan ke kolom tabel pendaftarans.
     */
    public static function builtinCatalog(): array
    {
        return [
            // Bagian 1: Identitas
            'jenjang' => [
                'section' => 'sec_identitas', 'label' => 'Jenjang', 'type' => 'select',
                'placeholder' => '', 'required' => true, 'locked' => true, 'options' => [],
            ],
            'nama_lengkap' => [
                'section' => 'sec_identitas', 'label' => 'Nama Lengkap', 'type' => 'text',
                'placeholder' => 'Sesuai KK / Akta', 'required' => true, 'locked' => true, 'options' => [],
            ],
            'jenis_kelamin' => [
                'section' => 'sec_identitas', 'label' => 'Jenis Kelamin', 'type' => 'select',
                'placeholder' => '', 'required' => true, 'locked' => false, 'options' => ['Laki-laki', 'Perempuan'],
            ],
            'pj_biaya_hp' => [
                'section' => 'sec_identitas', 'label' => 'No. WhatsApp Aktif (Orang Tua / Wali)', 'type' => 'text',
                'placeholder' => '08xxxxxxxxxx', 'required' => true, 'locked' => false, 'options' => [],
            ],
            'nik' => [
                'section' => 'sec_identitas', 'label' => 'NIK (16 Digit)', 'type' => 'text',
                'placeholder' => 'Nomor Induk Kependudukan', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'nisn' => [
                'section' => 'sec_identitas', 'label' => 'NISN', 'type' => 'text',
                'placeholder' => 'Nomor Induk Siswa Nasional', 'required' => false, 'locked' => false, 'options' => [],
            ],

            // Bagian 2: Data Pribadi
            'nama_panggilan' => [
                'section' => 'sec_pribadi', 'label' => 'Nama Panggilan', 'type' => 'text',
                'placeholder' => 'Nama Panggilan', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'tempat_lahir' => [
                'section' => 'sec_pribadi', 'label' => 'Tempat Lahir', 'type' => 'text',
                'placeholder' => 'Kota / Kabupaten', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'tanggal_lahir' => [
                'section' => 'sec_pribadi', 'label' => 'Tanggal Lahir', 'type' => 'date',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'anak_ke' => [
                'section' => 'sec_pribadi', 'label' => 'Anak Ke-', 'type' => 'number',
                'placeholder' => '1', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'jumlah_saudara' => [
                'section' => 'sec_pribadi', 'label' => 'Jumlah Saudara', 'type' => 'number',
                'placeholder' => '0', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'tinggal_bersama' => [
                'section' => 'sec_pribadi', 'label' => 'Tinggal Bersama', 'type' => 'select',
                'placeholder' => '', 'required' => false, 'locked' => false,
                'options' => ['Orang Tua Kandung', 'Ayah', 'Ibu', 'Wali'],
            ],
            'mulai_pengajian' => [
                'section' => 'sec_pribadi', 'label' => 'Mulai Sambung Pengajian (Tahun)', 'type' => 'text',
                'placeholder' => '2024', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'status_mondok' => [
                'section' => 'sec_pribadi', 'label' => 'Status Mondok', 'type' => 'select',
                'placeholder' => '', 'required' => false, 'locked' => false,
                'options' => ['Person', 'Utusan/Kiriman'],
            ],
            'pendidikan_terakhir' => [
                'section' => 'sec_pribadi', 'label' => 'Pendidikan Terakhir', 'type' => 'select',
                'placeholder' => '', 'required' => false, 'locked' => false,
                'options' => ['TK/RA', 'SD/MI', 'SMP/MTs', 'SMA/MA/SMK', 'D3', 'S1/D4', 'Lainnya'],
            ],
            'nama_sekolah_asal' => [
                'section' => 'sec_pribadi', 'label' => 'Nama Sekolah Asal', 'type' => 'text',
                'placeholder' => 'Nama Sekolah Asal', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'tahun_lulus' => [
                'section' => 'sec_pribadi', 'label' => 'Tahun Lulus', 'type' => 'text',
                'placeholder' => '2027', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'penerima_kip' => [
                'section' => 'sec_pribadi', 'label' => 'Penerima KIP / PIP / PKH', 'type' => 'select',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => ['Tidak', 'Ya'],
            ],
            'nomor_kip' => [
                'section' => 'sec_pribadi', 'label' => 'Nomor KIP / KKS / PKH', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'penyakit_diderita' => [
                'section' => 'sec_pribadi', 'label' => 'Riwayat Penyakit', 'type' => 'text',
                'placeholder' => 'Jika ada', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'alergi' => [
                'section' => 'sec_pribadi', 'label' => 'Alergi', 'type' => 'text',
                'placeholder' => 'Jika ada', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'gol_darah' => [
                'section' => 'sec_pribadi', 'label' => 'Golongan Darah', 'type' => 'select',
                'placeholder' => '', 'required' => false, 'locked' => false,
                'options' => ['A', 'B', 'AB', 'O', 'Tidak Tahu'],
            ],
            'tinggi_badan' => [
                'section' => 'sec_pribadi', 'label' => 'Tinggi Badan (cm)', 'type' => 'number',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'berat_badan' => [
                'section' => 'sec_pribadi', 'label' => 'Berat Badan (kg)', 'type' => 'number',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'keahlian_prestasi' => [
                'section' => 'sec_pribadi', 'label' => 'Keahlian / Prestasi', 'type' => 'textarea',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [], 'wide' => true,
            ],

            // Bagian 3: Keluarga (Ayah, Ibu, Penanggung Jawab)
            'ayah_nama' => [
                'section' => 'sec_keluarga', 'label' => 'Nama Ayah', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ayah_status' => [
                'section' => 'sec_keluarga', 'label' => 'Status Ayah', 'type' => 'select',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => ['Hidup', 'Meninggal'],
            ],
            'ayah_ttl' => [
                'section' => 'sec_keluarga', 'label' => 'Tempat, Tanggal Lahir Ayah', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ayah_pendidikan' => [
                'section' => 'sec_keluarga', 'label' => 'Pendidikan Terakhir Ayah', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ayah_pekerjaan' => [
                'section' => 'sec_keluarga', 'label' => 'Pekerjaan Ayah', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ayah_penghasilan' => [
                'section' => 'sec_keluarga', 'label' => 'Penghasilan Ayah', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ayah_hp' => [
                'section' => 'sec_keluarga', 'label' => 'No. HP / WA Ayah', 'type' => 'text',
                'placeholder' => '08xxxxxxxxxx', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ayah_daerah' => [
                'section' => 'sec_keluarga', 'label' => 'Daerah Sambung Ayah', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ayah_desa' => [
                'section' => 'sec_keluarga', 'label' => 'Desa Sambung Ayah', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ayah_kelompok' => [
                'section' => 'sec_keluarga', 'label' => 'Kelompok Sambung Ayah', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ibu_nama' => [
                'section' => 'sec_keluarga', 'label' => 'Nama Ibu', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ibu_status' => [
                'section' => 'sec_keluarga', 'label' => 'Status Ibu', 'type' => 'select',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => ['Hidup', 'Meninggal'],
            ],
            'ibu_ttl' => [
                'section' => 'sec_keluarga', 'label' => 'Tempat, Tanggal Lahir Ibu', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ibu_pendidikan' => [
                'section' => 'sec_keluarga', 'label' => 'Pendidikan Terakhir Ibu', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ibu_pekerjaan' => [
                'section' => 'sec_keluarga', 'label' => 'Pekerjaan Ibu', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ibu_penghasilan' => [
                'section' => 'sec_keluarga', 'label' => 'Penghasilan Ibu', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ibu_hp' => [
                'section' => 'sec_keluarga', 'label' => 'No. HP / WA Ibu', 'type' => 'text',
                'placeholder' => '08xxxxxxxxxx', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ibu_daerah' => [
                'section' => 'sec_keluarga', 'label' => 'Daerah Sambung Ibu', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ibu_desa' => [
                'section' => 'sec_keluarga', 'label' => 'Desa Sambung Ibu', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'ibu_kelompok' => [
                'section' => 'sec_keluarga', 'label' => 'Kelompok Sambung Ibu', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'pj_biaya_nama' => [
                'section' => 'sec_keluarga', 'label' => 'Nama Penanggung Jawab Biaya', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],

            // Bagian 4: Alamat & Berkas
            'alamat_jalan' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Alamat Jalan / Dusun', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [], 'wide' => true,
            ],
            'rt_rw' => [
                'section' => 'sec_alamat_berkas', 'label' => 'RT / RW', 'type' => 'text',
                'placeholder' => '001/002', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'desa_kelurahan' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Desa / Kelurahan', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'kecamatan' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Kecamatan', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'kab_kota' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Kabupaten / Kota', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'kode_pos' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Kode Pos', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'provinsi' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Provinsi', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'warga_negara' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Kewarganegaraan', 'type' => 'text',
                'placeholder' => 'Indonesia', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'kelompok' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Kelompok Sambung Pengajian', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'desa' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Desa Sambung Pengajian', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'daerah' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Daerah Sambung Pengajian', 'type' => 'text',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'foto_akta' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Foto Akta Kelahiran', 'type' => 'file',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'foto_kk' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Foto Kartu Keluarga (KK)', 'type' => 'file',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'foto_ktp_ortu' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Foto KTP Orang Tua / Wali', 'type' => 'file',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
            'foto_surat_pernyataan' => [
                'section' => 'sec_alamat_berkas', 'label' => 'Foto Surat Pernyataan', 'type' => 'file',
                'placeholder' => '', 'required' => false, 'locked' => false, 'options' => [],
            ],
        ];
    }

    /**
     * Skema standar lengkap (4 bagian identik dengan formulir SPMB bawaan).
     */
    public static function defaultSchema(array $dokumenConfig = []): array
    {
        $dok = array_merge(PenerimaanSantri::DOKUMEN_DEFAULTS, $dokumenConfig);
        $catalog = self::builtinCatalog();

        $sectionDefs = [
            'sec_identitas'     => ['title' => 'Identitas', 'short_title' => 'Identitas', 'description' => ''],
            'sec_pribadi'       => ['title' => 'Data Pribadi', 'short_title' => 'Pribadi', 'description' => ''],
            'sec_keluarga'      => ['title' => 'Keluarga', 'short_title' => 'Keluarga', 'description' => ''],
            'sec_alamat_berkas' => ['title' => 'Alamat & Berkas', 'short_title' => 'Berkas', 'description' => ''],
        ];

        $sections = [];
        foreach ($sectionDefs as $secId => $meta) {
            $fields = [];
            foreach ($catalog as $key => $cfg) {
                if ($cfg['section'] !== $secId) {
                    continue;
                }

                $enabled = true;
                $required = (bool) ($cfg['required'] ?? false);

                if (isset(self::DOKUMEN_FIELD_TO_FLAG[$key])) {
                    $flagKey = self::DOKUMEN_FIELD_TO_FLAG[$key];
                    $mode = $dok[$flagKey] ?? 'opsional';
                    $enabled = $mode !== 'tidak';
                    $required = $mode === 'wajib';
                }

                $fields[] = [
                    'id'          => $key,
                    'source'      => 'builtin',
                    'key'         => $key,
                    'label'       => $cfg['label'],
                    'type'        => $cfg['type'],
                    'placeholder' => $cfg['placeholder'] ?? '',
                    'help'        => '',
                    'required'    => $required,
                    'enabled'     => $enabled,
                    'locked'      => (bool) ($cfg['locked'] ?? false),
                    'wide'        => (bool) ($cfg['wide'] ?? false),
                    'options'     => $cfg['options'] ?? [],
                ];
            }

            $sections[] = [
                'id'          => $secId,
                'title'       => $meta['title'],
                'short_title' => $meta['short_title'],
                'description' => $meta['description'],
                'enabled'     => true,
                'fields'      => $fields,
            ];
        }

        return [
            'version'     => 1,
            'layout'      => 'wizard',
            'description' => '',
            'sections'    => $sections,
        ];
    }

    /**
     * Preset ringkas (1 halaman ala Google Form, ideal untuk RA / SD IT).
     */
    public static function compactSchema(array $dokumenConfig = []): array
    {
        $schema = self::defaultSchema($dokumenConfig);
        $schema['layout'] = 'single';

        $keepKeys = [
            'jenjang', 'nama_lengkap', 'nama_panggilan', 'jenis_kelamin',
            'tempat_lahir', 'tanggal_lahir', 'nik', 'pj_biaya_hp',
            'anak_ke', 'jumlah_saudara', 'ayah_nama', 'ayah_pekerjaan', 'ayah_hp',
            'ibu_nama', 'ibu_pekerjaan', 'ibu_hp',
            'alamat_jalan', 'rt_rw', 'desa_kelurahan', 'kecamatan', 'kab_kota',
            'foto_akta', 'foto_kk', 'foto_ktp_ortu',
        ];

        $requiredKeys = ['jenjang', 'nama_lengkap', 'jenis_kelamin', 'tanggal_lahir', 'pj_biaya_hp', 'ayah_nama', 'ibu_nama'];

        $dok = array_merge(PenerimaanSantri::DOKUMEN_DEFAULTS, $dokumenConfig);

        foreach ($schema['sections'] as &$sec) {
            foreach ($sec['fields'] as &$field) {
                $key = $field['key'];
                if (in_array($key, self::LOCKED_BUILTIN_KEYS, true)) {
                    $field['enabled'] = true;
                    $field['required'] = true;
                } elseif (isset(self::DOKUMEN_FIELD_TO_FLAG[$key])) {
                    $flagKey = self::DOKUMEN_FIELD_TO_FLAG[$key];
                    $mode = $dok[$flagKey] ?? 'opsional';
                    $inPreset = in_array($key, $keepKeys, true);
                    $field['enabled'] = $mode !== 'tidak' && ($inPreset || $mode === 'wajib');
                    $field['required'] = $field['enabled'] && $mode === 'wajib';
                } else {
                    $field['enabled'] = in_array($key, $keepKeys, true);
                    $field['required'] = $field['enabled'] && in_array($key, $requiredKeys, true);
                }
            }
            unset($field);
        }
        unset($sec);

        return $schema;
    }

    /**
     * Terapkan konfigurasi syarat_dokumen (wajib/opsional/tidak) ke skema formulir kustom
     * agar perubahan dari halaman Edit Penerimaan selalu sinkron dengan Desain Formulir.
     */
    public static function applyDokumenConfig(array $schema, array $dokumenConfig): array
    {
        $clean = self::sanitizeSubmittedSchema($schema);
        $catalog = self::builtinCatalog();
        $found = [];

        foreach ($clean['sections'] as &$sec) {
            foreach ($sec['fields'] as &$fld) {
                if (($fld['source'] ?? '') !== 'builtin') {
                    continue;
                }
                $key = $fld['key'] ?? '';
                if (!isset(self::DOKUMEN_FIELD_TO_FLAG[$key])) {
                    continue;
                }
                $flagKey = self::DOKUMEN_FIELD_TO_FLAG[$key];
                $mode = $dokumenConfig[$flagKey] ?? 'opsional';
                $fld['enabled'] = $mode !== 'tidak';
                $fld['required'] = $mode === 'wajib';
                if ($fld['enabled'] && empty($sec['enabled'])) {
                    $sec['enabled'] = true;
                }
                $found[$key] = true;
            }
            unset($fld);
        }
        unset($sec);

        // Jika ada dokumen yang diaktifkan namun belum ada di skema, tambahkan ke bagian Alamat & Berkas / bagian aktif terakhir
        $targetSecIdx = null;
        foreach ($clean['sections'] as $idx => $sec) {
            if (($sec['id'] ?? '') === 'sec_alamat_berkas') {
                $targetSecIdx = $idx;
                break;
            }
        }
        if ($targetSecIdx === null) {
            foreach ($clean['sections'] as $idx => $sec) {
                if (!empty($sec['enabled'])) {
                    $targetSecIdx = $idx;
                }
            }
        }
        if ($targetSecIdx === null) {
            $targetSecIdx = count($clean['sections']) - 1;
        }

        foreach (self::DOKUMEN_FIELD_TO_FLAG as $fotoKey => $flagKey) {
            if (isset($found[$fotoKey])) {
                continue;
            }
            $mode = $dokumenConfig[$flagKey] ?? 'tidak';
            if ($mode === 'tidak' || !isset($catalog[$fotoKey])) {
                continue;
            }
            $meta = $catalog[$fotoKey];
            $clean['sections'][$targetSecIdx]['enabled'] = true;
            $clean['sections'][$targetSecIdx]['fields'][] = [
                'id'          => $fotoKey,
                'source'      => 'builtin',
                'key'         => $fotoKey,
                'label'       => $meta['label'],
                'type'        => $meta['type'],
                'placeholder' => $meta['placeholder'] ?? '',
                'help'        => '',
                'required'    => $mode === 'wajib',
                'enabled'     => true,
                'locked'      => false,
                'wide'        => (bool) ($meta['wide'] ?? false),
                'options'     => $meta['options'] ?? [],
            ];
        }

        return $clean;
    }

    /**
     * Resolve skema final untuk suatu event (fallback otomatis ke defaultSchema bila belum dikustomisasi).
     */
    public static function resolve(?array $storedSchema, array $dokumenConfig = [], bool $syncDokumenConfig = false): array
    {
        if (empty($storedSchema) || !is_array($storedSchema) || empty($storedSchema['sections'])) {
            return self::defaultSchema($dokumenConfig);
        }

        $resolved = self::sanitizeSubmittedSchema($storedSchema);
        if ($syncDokumenConfig && !empty($dokumenConfig)) {
            return self::applyDokumenConfig($resolved, $dokumenConfig);
        }

        return $resolved;
    }

    /**
     * Validasi & sanitasi skema dari Form Builder sebelum disimpan ke database.
     */
    public static function sanitizeSubmittedSchema(array $raw): array
    {
        $catalog = self::builtinCatalog();
        $allowedTypes = array_keys(self::FIELD_TYPES);

        $layout = in_array($raw['layout'] ?? 'wizard', ['wizard', 'single'], true)
            ? $raw['layout']
            : 'wizard';

        $description = trim((string) ($raw['description'] ?? ''));
        $rawSections = is_array($raw['sections'] ?? null) ? $raw['sections'] : [];

        $sections = [];
        $usedFieldIds = [];
        $seenBuiltins = [];

        foreach ($rawSections as $sIdx => $sec) {
            if (!is_array($sec)) {
                continue;
            }

            $secTitle = trim((string) ($sec['title'] ?? ''));
            if ($secTitle === '') {
                $secTitle = 'Bagian ' . ($sIdx + 1);
            }
            $secShort = trim((string) ($sec['short_title'] ?? ''));
            if ($secShort === '') {
                $secShort = Str::limit($secTitle, 14, '');
            }
            $secId = trim((string) ($sec['id'] ?? ''));
            if ($secId === '') {
                $secId = 'sec_' . ($sIdx + 1) . '_' . Str::lower(Str::random(5));
            }
            $secEnabled = array_key_exists('enabled', $sec) ? (bool) $sec['enabled'] : true;

            $cleanFields = [];
            $rawFields = is_array($sec['fields'] ?? null) ? $sec['fields'] : [];

            foreach ($rawFields as $fIdx => $fld) {
                if (!is_array($fld)) {
                    continue;
                }

                $source = ($fld['source'] ?? 'custom') === 'builtin' ? 'builtin' : 'custom';
                $key = trim((string) ($fld['key'] ?? ($fld['id'] ?? '')));

                if ($source === 'builtin') {
                    if (!isset($catalog[$key]) || isset($seenBuiltins[$key])) {
                        continue;
                    }
                    $seenBuiltins[$key] = true;
                    $meta = $catalog[$key];
                    $locked = in_array($key, self::LOCKED_BUILTIN_KEYS, true);

                    $label = trim((string) ($fld['label'] ?? ''));
                    if ($label === '') {
                        $label = $meta['label'];
                    }

                    $options = $meta['options'] ?? [];
                    if ($meta['type'] === 'select' && !$locked && !in_array($key, ['jenis_kelamin', 'penerima_kip', 'ayah_status', 'ibu_status', 'status_mondok'], true)) {
                        $submittedOpts = self::normalizeOptions($fld['options'] ?? []);
                        if (!empty($submittedOpts)) {
                            $options = $submittedOpts;
                        }
                    }

                    $cleanFields[] = [
                        'id'          => $key,
                        'source'      => 'builtin',
                        'key'         => $key,
                        'label'       => Str::limit($label, 120, ''),
                        'type'        => $meta['type'],
                        'placeholder' => Str::limit(trim((string) ($fld['placeholder'] ?? ($meta['placeholder'] ?? ''))), 140, ''),
                        'help'        => Str::limit(trim((string) ($fld['help'] ?? '')), 255, ''),
                        'required'    => $locked ? true : (bool) ($fld['required'] ?? false),
                        'enabled'     => $locked ? true : (array_key_exists('enabled', $fld) ? (bool) $fld['enabled'] : true),
                        'locked'      => $locked,
                        'wide'        => (bool) ($fld['wide'] ?? ($meta['wide'] ?? false)),
                        'options'     => $options,
                    ];
                    $usedFieldIds[$key] = true;
                } else {
                    $label = trim((string) ($fld['label'] ?? ''));
                    if ($label === '') {
                        continue;
                    }

                    $type = in_array($fld['type'] ?? 'text', $allowedTypes, true) ? $fld['type'] : 'text';
                    $id = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($fld['id'] ?? ''));
                    if ($id === '' || isset($usedFieldIds[$id]) || isset($catalog[$id])) {
                        $id = 'cf_' . Str::lower(Str::random(8));
                    }
                    $usedFieldIds[$id] = true;

                    $options = [];
                    if (in_array($type, ['radio', 'checkbox', 'select'], true)) {
                        $options = self::normalizeOptions($fld['options'] ?? []);
                        if (empty($options)) {
                            $options = ['Opsi 1'];
                        }
                    }

                    $cleanFields[] = [
                        'id'          => $id,
                        'source'      => 'custom',
                        'key'         => $id,
                        'label'       => Str::limit($label, 160, ''),
                        'type'        => $type,
                        'placeholder' => Str::limit(trim((string) ($fld['placeholder'] ?? '')), 140, ''),
                        'help'        => Str::limit(trim((string) ($fld['help'] ?? '')), 255, ''),
                        'required'    => (bool) ($fld['required'] ?? false),
                        'enabled'     => array_key_exists('enabled', $fld) ? (bool) $fld['enabled'] : true,
                        'locked'      => false,
                        'wide'        => (bool) ($fld['wide'] ?? in_array($type, ['textarea', 'radio', 'checkbox', 'file'], true)),
                        'options'     => $options,
                    ];
                }
            }

            $sections[] = [
                'id'          => $secId,
                'title'       => Str::limit($secTitle, 80, ''),
                'short_title' => Str::limit($secShort, 20, ''),
                'description' => Str::limit(trim((string) ($sec['description'] ?? '')), 300, ''),
                'enabled'     => $secEnabled,
                'fields'      => $cleanFields,
            ];
        }

        // Pastikan minimal ada 1 section
        if (empty($sections)) {
            $sections[] = [
                'id'          => 'sec_identitas',
                'title'       => 'Identitas',
                'short_title' => 'Identitas',
                'description' => '',
                'enabled'     => true,
                'fields'      => [],
            ];
        }

        // Pastikan field inti terkunci (jenjang & nama_lengkap) selalu ada & aktif pada section pertama yang aktif
        $firstActiveIdx = 0;
        foreach ($sections as $idx => $s) {
            if (!empty($s['enabled'])) {
                $firstActiveIdx = $idx;
                break;
            }
        }
        $sections[$firstActiveIdx]['enabled'] = true;

        foreach (array_reverse(self::LOCKED_BUILTIN_KEYS) as $lockedKey) {
            if (!isset($seenBuiltins[$lockedKey])) {
                $meta = $catalog[$lockedKey];
                array_unshift($sections[$firstActiveIdx]['fields'], [
                    'id'          => $lockedKey,
                    'source'      => 'builtin',
                    'key'         => $lockedKey,
                    'label'       => $meta['label'],
                    'type'        => $meta['type'],
                    'placeholder' => $meta['placeholder'] ?? '',
                    'help'        => '',
                    'required'    => true,
                    'enabled'     => true,
                    'locked'      => true,
                    'wide'        => false,
                    'options'     => $meta['options'] ?? [],
                ]);
            }
        }

        // Pastikan section yang mengandung field terkunci selalu enabled
        foreach ($sections as &$sec) {
            foreach ($sec['fields'] as $f) {
                if (!empty($f['locked'])) {
                    $sec['enabled'] = true;
                    break;
                }
            }
        }
        unset($sec);

        return [
            'version'     => 1,
            'layout'      => $layout,
            'description' => Str::limit($description, 600, ''),
            'sections'    => array_values($sections),
        ];
    }

    /**
     * Ambil daftar section & field yang benar-benar tampil di form publik.
     */
    public static function visibleSections(array $schema): array
    {
        $visible = [];
        foreach (($schema['sections'] ?? []) as $sec) {
            if (empty($sec['enabled'])) {
                continue;
            }
            $fields = array_values(array_filter(
                $sec['fields'] ?? [],
                fn ($f) => !empty($f['enabled'])
            ));
            if (empty($fields)) {
                continue;
            }
            $sec['fields'] = $fields;
            $visible[] = $sec;
        }

        return $visible;
    }

    /**
     * Ambil daftar flat seluruh field yang aktif beserta info nama bagiannya.
     */
    public static function activeFieldsFlat(array $schema): array
    {
        $list = [];
        foreach (self::visibleSections($schema) as $sIdx => $sec) {
            foreach ($sec['fields'] as $field) {
                $field['_section_index'] = $sIdx + 1;
                $field['_section_title'] = $sec['title'];
                $list[] = $field;
            }
        }
        return $list;
    }

    /**
     * Ekstrak konfigurasi syarat_dokumen (wajib/opsional/tidak) dari field dokumen bawaan di skema.
     */
    public static function extractDokumenConfig(array $schema): array
    {
        $result = [];
        foreach (self::DOKUMEN_FIELD_TO_FLAG as $fieldKey => $flagKey) {
            $result[$flagKey] = 'tidak';
        }

        foreach (($schema['sections'] ?? []) as $sec) {
            $secEnabled = !empty($sec['enabled']);
            foreach (($sec['fields'] ?? []) as $fld) {
                if (($fld['source'] ?? '') !== 'builtin') {
                    continue;
                }
                $key = $fld['key'] ?? '';
                if (!isset(self::DOKUMEN_FIELD_TO_FLAG[$key])) {
                    continue;
                }
                $flagKey = self::DOKUMEN_FIELD_TO_FLAG[$key];
                if ($secEnabled && !empty($fld['enabled'])) {
                    $result[$flagKey] = !empty($fld['required']) ? 'wajib' : 'opsional';
                } else {
                    $result[$flagKey] = 'tidak';
                }
            }
        }

        return $result;
    }

    /**
     * Hitung ringkasan skema untuk ditampilkan pada badge di Link Penerimaan.
     */
    public static function summary(array $schema): array
    {
        $visible = self::visibleSections($schema);
        $activeTotal = 0;
        $customTotal = 0;
        foreach ($visible as $sec) {
            foreach ($sec['fields'] as $f) {
                $activeTotal++;
                if (($f['source'] ?? '') === 'custom') {
                    $customTotal++;
                }
            }
        }

        return [
            'sections' => count($visible),
            'fields'   => $activeTotal,
            'custom'   => $customTotal,
            'layout'   => $schema['layout'] ?? 'wizard',
        ];
    }

    protected static function normalizeOptions($raw): array
    {
        if (is_string($raw)) {
            $raw = preg_split('/\r\n|\r|\n/', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }

        $clean = [];
        foreach ($raw as $item) {
            $v = trim((string) $item);
            if ($v !== '' && !in_array($v, $clean, true)) {
                $clean[] = Str::limit($v, 140, '');
            }
        }

        return array_values($clean);
    }
}
