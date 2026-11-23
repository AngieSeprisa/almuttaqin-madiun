<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rfid_uid',
        'name',
        'email',
        'foto',
        'password',
        'role',
        'jabatan',
        'mata_pelajaran',
        'mapel_diajukan',
        'mapel_status',
        'mapel_catatan',
        'mapel_diajukan_at',
        'mapel_diverifikasi_at',
        'mapel_diverifikasi_by',
        'nip',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'telepon',
        'alamat',
        'primary_lembaga_id',
        'is_guru_sekolah',
        'is_guru_pondok',
        'pondok_kelas_ids',
        'pondok_materi',
        'pondok_catatan',
        'is_active',
        'must_change_password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_guru_sekolah' => 'boolean',
            'is_guru_pondok' => 'boolean',
            'pondok_kelas_ids' => 'array',
            'must_change_password' => 'boolean',
            'mapel_diajukan_at' => 'datetime',
            'mapel_diverifikasi_at' => 'datetime',
        ];
    }

    /**
     * Relasi ke lembaga utama
     */
    public function primaryLembaga()
    {
        return $this->belongsTo(Lembaga::class, 'primary_lembaga_id');
    }

    /**
     * Relasi ke semua lembaga yang dapat diakses (bisa lebih dari satu)
     */
    public function lembagas()
    {
        return $this->belongsToMany(Lembaga::class, 'user_lembagas', 'user_id', 'lembaga_id');
    }

    /**
     * Relasi ke matriks hak akses menu
     */
    public function permissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    /**
     * Relasi ke data presensi guru
     */
    public function presensiGurus()
    {
        return $this->hasMany(PresensiGuru::class);
    }

    /**
     * Relasi ke user yayasan yang memverifikasi mapel
     */
    public function mapelDiverifikasiBy()
    {
        return $this->belongsTo(User::class, 'mapel_diverifikasi_by');
    }

    /**
     * Cek apakah user adalah pengurus yayasan / super admin
     */
    public function isYayasan(): bool
    {
        return in_array($this->role, ['yayasan', 'super_admin']);
    }

    /**
     * Cek apakah user memiliki akses ke kode lembaga tertentu
     */
    public function hasAccessToLembaga(string $kode): bool
    {
        if ($this->isYayasan()) {
            return true;
        }

        if ($this->primaryLembaga && $this->primaryLembaga->kode === $kode) {
            return true;
        }

        return $this->lembagas->contains('kode', $kode);
    }

    /**
     * Mengambil daftar lembaga yang dapat diakses user
     */
    public function accessibleLembagas()
    {
        if ($this->isYayasan()) {
            return Lembaga::where('is_active', true)->orderBy('urutan')->get();
        }

        $list = $this->lembagas()->where('is_active', true)->orderBy('urutan')->get();
        if ($list->isEmpty() && $this->primaryLembaga) {
            return collect([$this->primaryLembaga]);
        }

        return $list;
    }

    /**
     * Mendapatkan objek Lembaga yang sedang aktif di sesi user
     */
    public function currentLembaga(): ?Lembaga
    {
        $activeKode = session('active_lembaga');

        if ($activeKode && $this->hasAccessToLembaga($activeKode)) {
            $lembaga = Lembaga::where('kode', $activeKode)->where('is_active', true)->first();
            if ($lembaga) {
                return $lembaga;
            }
        }

        // Default ke lembaga pertama yang bisa diakses
        $accessible = $this->accessibleLembagas();
        if ($accessible->isNotEmpty()) {
            $first = $accessible->first();
            session(['active_lembaga' => $first->kode]);
            return $first;
        }

        return null;
    }

    /**
     * Relasi mata pelajaran yang diampu oleh guru ini
     */
    public function mataPelajaranPengampu()
    {
        return $this->hasMany(MataPelajaran::class, 'guru_id');
    }

    /**
     * Peta grup menu ke daftar kode submenu granular di dalamnya
     */
    public static function groupPermissionChildrenMap(): array
    {
        return [
            'data_warga'         => ['siswa', 'guru_warga', 'lulus_warga', 'mutasi_warga'],
            'sekolah'            => ['sekolah_jurnal', 'sekolah_rekap_jurnal', 'ekskul', 'jadwal_pelajaran', 'jadwal_saya'],
            'ujian'              => ['ujian_soal', 'ujian_ljk', 'ujian_sesi', 'ujian_koreksi', 'ujian_nilai'],
            'pondok'             => ['materi_pondok', 'target_pengajian', 'jurnal_mengaji'],
            'perpustakaan'       => ['daftar_buku', 'pinjam_buku'],
            'perizinan'          => ['perizinan_sambang', 'perizinan_sakit', 'perizinan_haid'],
            'rekap_absensi'      => ['rekap_sholat', 'rekap_pengajian', 'rekap_sekolah', 'rekap_guru', 'rekap_ekskul'],
            'rekap_pondok'       => ['rekap_sholat', 'rekap_pengajian'],
            'data_master'        => ['master_jam', 'master_lembaga', 'master_import_siswa'],
            'pengaturan_absensi' => ['pengaturan_sesi', 'monitor_rfid'],
            'pengaturan_sekolah' => ['pengaturan_sesi', 'monitor_rfid', 'guru_sekolah', 'plotting_mapel', 'plotting_kelas', 'plotting_ekskul'],
            'pengaturan_pondok'  => ['plotting_kamar', 'plotting_pengajian', 'guru_pondok'],
            'master_kelas'       => ['plotting_kelas'],
        ];
    }

    /**
     * Peta kode submenu granular ke kode menu induk (legacy fallback)
     */
    public static function legacyParentPermissionMap(): array
    {
        return [
            'guru_warga'               => 'siswa',
            'lulus_warga'              => 'siswa',
            'mutasi_warga'             => 'siswa',
            'nfc'                      => 'siswa',
            'sekolah_jurnal'           => 'sekolah',
            'sekolah_rekap_jurnal'     => 'sekolah',
            'ujian'                    => 'sekolah',
            'ujian_soal'               => 'ujian',
            'ujian_ljk'                => 'ujian',
            'ujian_sesi'               => 'ujian',
            'ujian_koreksi'            => 'ujian',
            'ujian_nilai'              => 'ujian',
            'ekskul'                   => 'sekolah',
            'jurnal_ekskul'            => 'ekskul',
            'jadwal_pelajaran'         => 'sekolah',
            'jadwal_saya'              => 'jadwal_pelajaran',
            'materi_pondok'            => 'pondok',
            'target_pengajian'         => 'pondok',
            'jurnal_mengaji'           => 'pondok',
            'daftar_buku'              => 'perpustakaan',
            'pinjam_buku'              => 'perpustakaan',
            'rekap_sholat'             => 'rekap_absensi',
            'rekap_pengajian'          => 'rekap_absensi',
            'rekap_sekolah'            => 'sekolah',
            'rekap_guru'               => 'rekap_absensi',
            'rekap_ekskul'             => 'rekap_absensi',
            'master_jam'               => 'data_master',
            'master_lembaga'           => 'data_master',
            'master_import_siswa'      => 'data_master',
            'master_kelas'             => 'plotting_kelas',
            'pengaturan_sesi'          => 'pengaturan_absensi',
            'monitor_rfid'             => 'pengaturan_absensi',
            'plotting_kelas_pengajian' => 'plotting_pengajian',
        ];
    }

    /**
     * Cek hak akses granular per menu dan tindakan
     *
     * @param string $menuCode
     * @param string $action (view, create, edit, delete)
     */
    public function canDo(string $menuCode, string $action = 'view'): bool
    {
        // Yayasan / Super Admin memiliki hak penuh untuk semua menu
        if ($this->isYayasan()) {
            return true;
        }

        $field = 'can_' . $action;
        $perms = $this->permissions;

        // 1. Jika yang dicek adalah kode grup (misal: pondok, sekolah, perpustakaan, data_warga, rekap_absensi),
        //    prioritaskan evaluasi dari submenu anak yang sudah tersimpan di database
        $groupMap = self::groupPermissionChildrenMap();
        if (isset($groupMap[$menuCode])) {
            $childPerms = $perms->whereIn('menu_code', $groupMap[$menuCode]);
            if ($childPerms->isNotEmpty()) {
                return $childPerms->contains(fn ($p) => (bool) ($p->{$field} ?? false));
            }

            $explicitGroupPerm = $perms->firstWhere('menu_code', $menuCode);
            if ($explicitGroupPerm !== null) {
                return (bool) ($explicitGroupPerm->{$field} ?? false);
            }
        }

        // 2. Cek izin eksplisit pada menu_code terkait di tabel user_permissions
        $perm = $perms->firstWhere('menu_code', $menuCode);
        if ($perm !== null) {
            return (bool) ($perm->{$field} ?? false);
        }

        // 3. Cek fallback ke menu induk (legacy parent) bila akun belum memiliki baris submenu spesifik
        $parentMap = self::legacyParentPermissionMap();
        $visited = [$menuCode];
        $currentCode = $menuCode;
        while (isset($parentMap[$currentCode])) {
            $parentCode = $parentMap[$currentCode];
            if (in_array($parentCode, $visited, true)) {
                break;
            }
            $visited[] = $parentCode;

            $parentPerm = $perms->firstWhere('menu_code', $parentCode);
            if ($parentPerm !== null) {
                return (bool) ($parentPerm->{$field} ?? false);
            }
            $currentCode = $parentCode;
        }

        // 4. Jika akun sudah diatur menggunakan matriks hak akses granular, jangan gunakan default role
        $granularCodes = [
            'sekolah_jurnal', 'sekolah_rekap_jurnal', 'jadwal_saya', 'ujian',
            'ujian_soal', 'ujian_ljk', 'ujian_sesi', 'ujian_koreksi', 'ujian_nilai',
            'materi_pondok', 'target_pengajian', 'jurnal_mengaji',
            'daftar_buku', 'pinjam_buku',
            'guru_warga', 'lulus_warga', 'mutasi_warga', 'nfc',
            'rekap_sholat', 'rekap_pengajian', 'rekap_sekolah', 'rekap_guru',
            'pengaturan_sesi', 'monitor_rfid', 'plotting_pengajian', 'guru_pondok',
            'guru_sekolah', 'plotting_mapel', 'plotting_kelas', 'plotting_ekskul',
            'master_jam', 'master_lembaga', 'master_import_siswa', 'perizinan_haid',
        ];
        if ($perms->whereIn('menu_code', $granularCodes)->isNotEmpty()) {
            return false;
        }

        // 5. Fallback default berdasarkan role hanya untuk akun yang belum dikonfigurasi matriks granularnya
        if (in_array($this->role, ['admin_lembaga', 'guru']) && in_array($menuCode, [
            'sekolah', 'sekolah_jurnal', 'sekolah_rekap_jurnal', 'jadwal_pelajaran', 'jadwal_saya', 'ujian',
            'ujian_soal', 'ujian_ljk', 'ujian_sesi', 'ujian_koreksi', 'ujian_nilai',
            'ekskul', 'jurnal_ekskul', 'plotting_ekskul', 'rekap_ekskul', 'rekap_sekolah'
        ], true)) {
            return true;
        }

        if ($this->role === 'admin_lembaga' && $this->hasAccessToLembaga('pondok') && in_array($menuCode, [
            'pondok', 'materi_pondok', 'target_pengajian', 'jurnal_mengaji',
            'pengaturan_pondok', 'plotting_kamar', 'plotting_pengajian', 'plotting_kelas_pengajian', 'guru_pondok',
            'rekap_pondok', 'rekap_sholat', 'rekap_pengajian', 'rekap_absensi', 'rekap_guru',
            'perizinan', 'perizinan_haid', 'perizinan_sambang', 'perizinan_sakit'
        ], true)) {
            return true;
        }

        if (in_array($this->role, ['guru', 'musyrif']) && in_array($menuCode, [
            'pondok', 'materi_pondok', 'target_pengajian', 'jurnal_mengaji',
            'perpustakaan', 'daftar_buku', 'pinjam_buku',
            'rekap_pondok', 'rekap_sholat', 'rekap_pengajian', 'rekap_absensi', 'rekap_guru',
            'perizinan', 'perizinan_haid', 'perizinan_sambang', 'perizinan_sakit'
        ], true)) {
            return true;
        }

        if ($this->role === 'admin_lembaga' && in_array($menuCode, [
            'pondok', 'materi_pondok', 'target_pengajian', 'jurnal_mengaji',
            'perpustakaan', 'daftar_buku', 'pinjam_buku',
            'presensi', 'rekap_absensi', 'rekap_pondok', 'rekap_sholat', 'rekap_pengajian', 'rekap_sekolah', 'rekap_guru',
            'pengaturan_absensi', 'pengaturan_sekolah', 'pengaturan_sesi', 'monitor_rfid', 'guru_sekolah',
            'plotting_mapel', 'plotting_kelas', 'plotting_ekskul', 'ekskul', 'jurnal_ekskul', 'rekap_ekskul',
            'jadwal_pelajaran', 'jadwal_saya',
            'data_master', 'master_kelas', 'master_jam', 'master_lembaga', 'master_import_siswa',
            'perizinan', 'perizinan_haid'
        ], true)) {
            return true;
        }

        return false;
    }

    /**
     * Relasi jadwal mengajar yang diampu guru ini.
     */
    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    /**
     * URL publik foto profil (null bila belum diunggah atau file tidak ada).
     */
    public function getFotoUrlAttribute(): ?string
    {
        if ($this->foto && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->foto)) {
            return asset('storage/' . $this->foto);
        }
        return null;
    }

    /**
     * Inisial nama pengguna (misal "Fulan Santri" => "FS", "Yayasan" => "YA" / "Y").
     */
    public function getInitialsAttribute(): string
    {
        $name = trim($this->name ?? '');
        if ($name === '') {
            return 'U';
        }
        $words = preg_split('/\s+/', $name);
        if (count($words) >= 2) {
            return strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1], 0, 1));
        }
        return strtoupper(mb_substr($name, 0, min(2, mb_strlen($name))));
    }

    /**
     * Daftar array mata pelajaran aktif yang diampu guru (mendukung 2 mapel atau lebih).
     */
    public function getDaftarMataPelajaranAttribute(): array
    {
        $raw = trim((string)($this->mata_pelajaran ?? ''));
        if (empty($raw)) return [];
        return array_values(array_filter(array_map('trim', preg_split('/[,|;]/', $raw))));
    }

    /**
     * Daftar array mata pelajaran yang diajukan guru (mendukung 2 mapel atau lebih).
     */
    public function getDaftarMapelDiajukanAttribute(): array
    {
        $raw = trim((string)($this->mapel_diajukan ?? ''));
        if (empty($raw)) return [];
        return array_values(array_filter(array_map('trim', preg_split('/[,|;]/', $raw))));
    }

    /**
     * Daftar array materi pondok yang diampu guru pondok.
     */
    public function getDaftarMateriPondokAttribute(): array
    {
        $raw = trim((string) ($this->pondok_materi ?? ''));
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', preg_split('/[,|;]/', $raw))));
    }

    /**
     * Daftar array ID kelas pengajian yang diampu guru pondok (integer).
     */
    public function getDaftarKelasPengajianIdsAttribute(): array
    {
        $ids = $this->pondok_kelas_ids;
        if (!is_array($ids)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map('intval', $ids), fn ($id) => $id > 0)));
    }

    /**
     * Scope pengguna yang terafiliasi dengan lembaga tertentu (primary_lembaga_id atau user_lembagas).
     */
    public function scopeForLembagaModel($query, ?Lembaga $lembaga)
    {
        if (!$lembaga) {
            return $query;
        }

        return $query->where(function ($q) use ($lembaga) {
            $q->where('primary_lembaga_id', $lembaga->id)
              ->orWhereHas('lembagas', function ($l) use ($lembaga) {
                  $l->where('lembagas.id', $lembaga->id);
              });
        });
    }

    /**
     * Resolusi Lembaga Sekolah untuk Guru / Pegawai (SMP, SMA, SD IT, RA, atau lembaga formal lainnya).
     * Menentukan lembaga sekolah asal guru berdasarkan Mapel Diampu, Jabatan, Primary Lembaga, Akses Lembagas, atau Device Tap.
     */
    public function resolveSchoolLembaga(?string $deviceId = null): ?Lembaga
    {
        $formalSchools = Lembaga::formalOptions();
        $formalIds = $formalSchools->pluck('id')->all();

        // 1. Cek dari Mata Pelajaran yang diampu (Guru Sekolah)
        if ($this->is_guru_sekolah || !empty($this->mata_pelajaran) || $this->mapel_status === 'disetujui') {
            $mapelList = $this->daftar_mata_pelajaran;
            if (!empty($mapelList) && !empty($formalIds)) {
                $mapelRecords = \App\Models\MataPelajaran::with('lembaga')
                    ->whereIn('mata_pelajaran', $mapelList)
                    ->whereIn('lembaga_id', $formalIds)
                    ->get();

                $distinctLembagas = $mapelRecords->pluck('lembaga')->filter()->unique('id')->values();
                if ($distinctLembagas->count() === 1) {
                    return $distinctLembagas->first();
                }

                if ($distinctLembagas->count() > 1) {
                    // Jika mapel sama ada di beberapa sekolah (misal Matematika di SMP & SD IT),
                    // cocokkan dulu dengan jabatan, lalu primary_lembaga_id, lalu relasi lembagas user
                    $jabatanCheck = strtolower((string) ($this->jabatan ?? ''));
                    if ($jabatanCheck !== '') {
                        foreach ($distinctLembagas as $cand) {
                            $tokens = array_unique(array_filter([
                                strtolower(trim((string) $cand->kode)),
                                strtolower(trim((string) $cand->singkatan)),
                            ]));
                            foreach ($tokens as $tok) {
                                if (preg_match('/(?<![a-z0-9])' . preg_quote($tok, '/') . '(?![a-z0-9])/i', $jabatanCheck)) {
                                    return $cand;
                                }
                            }
                        }
                    }

                    $userFormalIds = $this->lembagas()->whereIn('lembagas.id', $formalIds)->pluck('lembagas.id')->all();
                    if ($this->primary_lembaga_id && in_array($this->primary_lembaga_id, $formalIds, true)) {
                        $primaryMatch = $distinctLembagas->firstWhere('id', $this->primary_lembaga_id);
                        if ($primaryMatch) {
                            return $primaryMatch;
                        }
                    }
                    $relMatch = $distinctLembagas->first(fn ($l) => in_array($l->id, $userFormalIds, true));
                    if ($relMatch) {
                        return $relMatch;
                    }

                    return $distinctLembagas->first();
                }
            }

            // Cek relasi langsung mataPelajaranPengampu jika kolom guru_id tersedia di tabel
            if (!empty($formalIds) && \Illuminate\Support\Facades\Schema::hasColumn('mata_pelajarans', 'guru_id')) {
                $mapelPengampu = \App\Models\MataPelajaran::with('lembaga')
                    ->where('guru_id', $this->id)
                    ->whereIn('lembaga_id', $formalIds)
                    ->first();
                if ($mapelPengampu && $mapelPengampu->lembaga) {
                    return $mapelPengampu->lembaga;
                }
            }
        }

        // 2. Cek dari Jabatan (misal "Operator SMP", "Guru SD IT", "Kepala RA", dll)
        // Gunakan batas kata agar kode pendek seperti "ra" tidak cocok dengan kata "operator"
        $jabatan = strtolower((string) ($this->jabatan ?? ''));
        if ($jabatan !== '') {
            foreach ($formalSchools as $fs) {
                $tokens = array_unique(array_filter([
                    strtolower(trim((string) $fs->kode)),
                    strtolower(trim((string) $fs->singkatan)),
                ]));
                foreach ($tokens as $tok) {
                    if (preg_match('/(?<![a-z0-9])' . preg_quote($tok, '/') . '(?![a-z0-9])/i', $jabatan)) {
                        return $fs;
                    }
                }
            }
        }

        // 3. Cek primaryLembaga jika merupakan lembaga formal
        if ($this->primaryLembaga && $this->primaryLembaga->isFormalSchool()) {
            return $this->primaryLembaga;
        }

        // 4. Cek relasi lembagas yang tergolong lembaga formal
        $sekolahLembagas = !empty($formalIds)
            ? $this->lembagas()->whereIn('lembagas.id', $formalIds)->get()
            : collect();

        if ($sekolahLembagas->count() === 1) {
            return $sekolahLembagas->first();
        }

        // 5. Cek dari device_id jika tap fisik dilakukan di alat khusus lembaga formal tertentu
        if ($deviceId) {
            $devId = strtolower($deviceId);
            foreach ($formalSchools as $fs) {
                $kode = strtolower(trim((string) $fs->kode));
                if ($kode !== '' && preg_match('/(?<![a-z0-9])' . preg_quote($kode, '/') . '(?![a-z0-9])/i', $devId)) {
                    return $fs;
                }
            }
        }

        // 6. Jika memiliki sekolahLembagas lebih dari 1 dan belum teridentifikasi, ambil yang pertama
        if ($sekolahLembagas->isNotEmpty()) {
            return $sekolahLembagas->first();
        }

        // 7. Fallback ke primaryLembaga atau lembaga pertama
        return $this->primaryLembaga ?: $this->lembagas()->first();
    }

}
