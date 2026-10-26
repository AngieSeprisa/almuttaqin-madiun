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

        // Admin Lembaga & Guru memiliki akses default ke modul sekolah & jadwal pelajaran
        if (in_array($this->role, ['admin_lembaga', 'guru']) && in_array($menuCode, ['sekolah', 'jadwal_pelajaran'])) {
            return true;
        }

        // Admin Lembaga memiliki akses default ke modul absensi, guru sekolah, plotting & master data
        if ($this->role === 'admin_lembaga' && in_array($menuCode, [
            'presensi', 'rekap_absensi', 'pengaturan_absensi', 'guru_sekolah', 
            'plotting_mapel', 'plotting_kelas', 'jadwal_pelajaran',
            'data_master', 'master_kelas', 'master_jam', 'master_lembaga', 'master_import_siswa'
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

}
