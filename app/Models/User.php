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
     * Cek hak akses granular per menu dan tindakan
     *
     * @param string $menuCode (siswa, pendaftaran, perizinan_sambang, perizinan_sakit, pengguna)
     * @param string $action (view, create, edit, delete)
     */
    public function canDo(string $menuCode, string $action = 'view'): bool
    {
        // Yayasan / Super Admin memiliki hak penuh untuk semua menu
        if ($this->isYayasan()) {
            return true;
        }

        // Menu NFC mengikuti perizinan modul siswa
        if ($menuCode === 'nfc') {
            return $this->canDo('siswa', $action);
        }

        // Admin Lembaga & Guru memiliki akses default ke modul sekolah, jadwal pelajaran & ekstrakurikuler
        if (in_array($this->role, ['admin_lembaga', 'guru']) && in_array($menuCode, ['sekolah', 'jadwal_pelajaran', 'ekskul', 'jurnal_ekskul', 'plotting_ekskul', 'rekap_ekskul'])) {
            return true;
        }

        // Admin Lembaga Pondok memiliki akses default ke modul Pengaturan Pondok & Plotting Kamar / Pengajian & Rekap & Perizinan
        if ($this->role === 'admin_lembaga' && $this->hasAccessToLembaga('pondok') && in_array($menuCode, ['pengaturan_pondok', 'plotting_kamar', 'plotting_pengajian', 'plotting_kelas_pengajian', 'rekap_pondok', 'rekap_sholat', 'rekap_pengajian', 'rekap_absensi', 'perizinan_haid', 'perizinan_sambang', 'perizinan_sakit'])) {
            return true;
        }

        // Guru & Musyrif memiliki akses ke Rekap Sholat, Pengajian, Pondok, Rekap Absensi, & Perizinan Pondok (Haid, Sakit, Sambang)
        if (in_array($this->role, ['guru', 'musyrif']) && in_array($menuCode, ['rekap_pondok', 'rekap_sholat', 'rekap_pengajian', 'rekap_absensi', 'perizinan_haid', 'perizinan_sambang', 'perizinan_sakit'])) {
            return true;
        }

        // Admin Lembaga memiliki akses default ke modul absensi, guru sekolah, plotting & master data
        if ($this->role === 'admin_lembaga' && in_array($menuCode, [
            'presensi', 'rekap_absensi', 'rekap_pondok', 'rekap_sholat', 'rekap_pengajian', 'pengaturan_absensi', 'guru_sekolah', 
            'plotting_mapel', 'plotting_kelas', 'plotting_ekskul', 'ekskul', 'jurnal_ekskul', 'rekap_ekskul', 'jadwal_pelajaran',
            'data_master', 'master_kelas', 'master_jam', 'master_lembaga', 'master_import_siswa', 'perizinan_haid'
        ])) {
            return true;
        }

        $perm = $this->permissions->firstWhere('menu_code', $menuCode);
        if (!$perm) {
            return false;
        }

        $field = 'can_' . $action;
        return (bool) ($perm->{$field} ?? false);
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
     * Resolusi Lembaga Sekolah untuk Guru / Pegawai (SMP atau SMA).
     * Menentukan lembaga sekolah asal guru berdasarkan Mapel Diampu, Jabatan, Primary Lembaga, Akses Lembagas, atau Device Tap.
     */
    public function resolveSchoolLembaga(?string $deviceId = null): ?Lembaga
    {
        // 1. Cek dari Mata Pelajaran yang diampu (Guru Sekolah)
        if ($this->is_guru_sekolah || !empty($this->mata_pelajaran) || $this->mapel_status === 'disetujui') {
            $mapelList = $this->daftar_mata_pelajaran;
            if (!empty($mapelList)) {
                $mapelRecord = \App\Models\MataPelajaran::with('lembaga')
                    ->whereIn('mata_pelajaran', $mapelList)
                    ->whereHas('lembaga', function ($q) {
                        $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(kode)'), ['smp', 'sma'])
                          ->orWhereIn(\Illuminate\Support\Facades\DB::raw('UPPER(singkatan)'), ['SMP', 'SMA']);
                    })
                    ->first();
                if ($mapelRecord && $mapelRecord->lembaga) {
                    return $mapelRecord->lembaga;
                }
            }

            // Cek relasi langsung mataPelajaranPengampu
            $mapelPengampu = \App\Models\MataPelajaran::with('lembaga')
                ->where('guru_id', $this->id)
                ->whereHas('lembaga', function ($q) {
                    $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(kode)'), ['smp', 'sma'])
                      ->orWhereIn(\Illuminate\Support\Facades\DB::raw('UPPER(singkatan)'), ['SMP', 'SMA']);
                })
                ->first();
            if ($mapelPengampu && $mapelPengampu->lembaga) {
                return $mapelPengampu->lembaga;
            }
        }

        // 2. Cek dari Jabatan (misal "Operator SMP", "Guru Bahasa SMP", "Kepala Sekolah SMA", dll)
        $jabatan = strtolower((string) ($this->jabatan ?? ''));
        if (str_contains($jabatan, 'smp')) {
            $smp = Lembaga::where(fn($q) => $q->where(\Illuminate\Support\Facades\DB::raw('LOWER(kode)'), 'smp')->orWhere(\Illuminate\Support\Facades\DB::raw('UPPER(singkatan)'), 'SMP'))->first();
            if ($smp) return $smp;
        } elseif (str_contains($jabatan, 'sma')) {
            $sma = Lembaga::where(fn($q) => $q->where(\Illuminate\Support\Facades\DB::raw('LOWER(kode)'), 'sma')->orWhere(\Illuminate\Support\Facades\DB::raw('UPPER(singkatan)'), 'SMA'))->first();
            if ($sma) return $sma;
        }

        // 3. Cek primaryLembaga jika merupakan sekolah (SMP/SMA)
        if ($this->primaryLembaga) {
            $kode = strtolower($this->primaryLembaga->kode ?? '');
            $singkat = strtoupper($this->primaryLembaga->singkatan ?? '');
            if (in_array($kode, ['smp', 'sma']) || in_array($singkat, ['SMP', 'SMA'])) {
                return $this->primaryLembaga;
            }
        }

        // 4. Cek relasi lembagas yang tergolong sekolah (SMP/SMA)
        $sekolahLembagas = $this->lembagas()
            ->where(function ($q) {
                $q->whereIn(\Illuminate\Support\Facades\DB::raw('LOWER(kode)'), ['smp', 'sma'])
                  ->orWhereIn(\Illuminate\Support\Facades\DB::raw('UPPER(singkatan)'), ['SMP', 'SMA']);
            })
            ->get();

        if ($sekolahLembagas->count() === 1) {
            return $sekolahLembagas->first();
        }

        // 5. Cek dari device_id jika tap fisik dilakukan di alat khusus SMP atau SMA
        if ($deviceId) {
            $devId = strtolower($deviceId);
            if (str_contains($devId, 'smp')) {
                $smp = Lembaga::where(fn($q) => $q->where(\Illuminate\Support\Facades\DB::raw('LOWER(kode)'), 'smp')->orWhere(\Illuminate\Support\Facades\DB::raw('UPPER(singkatan)'), 'SMP'))->first();
                if ($smp) return $smp;
            } elseif (str_contains($devId, 'sma')) {
                $sma = Lembaga::where(fn($q) => $q->where(\Illuminate\Support\Facades\DB::raw('LOWER(kode)'), 'sma')->orWhere(\Illuminate\Support\Facades\DB::raw('UPPER(singkatan)'), 'SMA'))->first();
                if ($sma) return $sma;
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
