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
     * Daftar lembaga formal aktif yang terdaftar (untuk pilihan jenjang SPMB).
     */
    public static function formalOptions()
    {
        return static::where('kategori', 'formal')
            ->where('is_active', true)
            ->orderBy('urutan')
            ->orderBy('id')
            ->get();
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

        $nama = $lembaga
            ? strtoupper($lembaga->nama)
            : 'YAYASAN PONDOK PESANTREN MUTTAQIN JOSENAN MADIUN';

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
            'lembaga'  => $lembaga,
            'nama'     => $nama,
            'alamat'   => $alamat,
            'telepon'  => $telepon,
            'email'    => $email,
            'logo_url' => $logoUrl,
        ];
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
     * Teks judul lembaga untuk sidebar.
     */
    public function getBrandTitleAttribute(): string
    {
        return match ($this->kode) {
            'smp'     => 'SMP AL MUTTAQIN',
            'sma'     => 'SMA AL MUTTAQIN',
            'yayasan' => 'YAYASAN PONDOK PESANTREN MUTTAQIN',
            default   => 'PONDOK PESANTREN AL MUTTAQIN',
        };
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

