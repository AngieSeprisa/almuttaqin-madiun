<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class PenerimaanSantri extends Model
{
    use HasFactory;

    public const JENJANG_OPTIONS = [
        'Semua'   => 'Semua Jenjang (SMP, SMA, Reguler)',
        'SMP'     => 'SMP',
        'SMA'     => 'SMA',
        'Reguler' => 'Reguler (Pondok)',
    ];

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
        'kontak_wa',
        'jadwal_seleksi',
        'lokasi_seleksi',
        'syarat_dokumen',
        'catatan_syarat',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'syarat_dokumen' => 'array',
        'is_active'      => 'boolean',
    ];

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
     * Mengembalikan gelombang yang sedang aktif pada tanggal tertentu (dan kuota belum penuh).
     */
    public function activeGelombang(?Carbon $date = null): ?PenerimaanGelombang
    {
        $today = ($date ?? now())->toDateString();
        $gelombangs = $this->relationLoaded('gelombangs')
            ? $this->gelombangs
            : $this->gelombangs()->withCount('pendaftarans')->get();

        foreach ($gelombangs as $gel) {
            $start = $gel->tanggal_mulai?->toDateString();
            $end = $gel->tanggal_selesai?->toDateString();
            if ($start && $end && $today >= $start && $today <= $end) {
                $count = $gel->pendaftarans_count ?? $gel->pendaftarans()->count();
                if ($gel->kuota === null || $count < $gel->kuota) {
                    return $gel;
                }
            }
        }

        return null;
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
            : $this->gelombangs()->withCount('pendaftarans')->get();

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
            : $this->gelombangs()->withCount('pendaftarans')->get();

        if ($gelombangs->isEmpty()) {
            return ['code' => 'buka', 'label' => 'Dibuka'];
        }

        $active = $this->activeGelombang($date);
        if ($active) {
            return ['code' => 'buka', 'label' => 'Dibuka (' . $active->nama_gelombang . ')'];
        }

        $today = ($date ?? now())->toDateString();

        // Cek apakah sedang dalam rentang tanggal tetapi kuota penuh
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
     * Pilihan jenjang yang diizinkan pada form publik event ini.
     */
    public function allowedJenjangs(): array
    {
        return match ($this->jenjang) {
            'SMP'     => ['SMP'],
            'SMA'     => ['SMA'],
            'Reguler' => ['Reguler'],
            default   => ['SMP', 'SMA', 'Reguler'],
        };
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
     * URL WhatsApp panitia (null bila kontak_wa kosong).
     */
    public function waUrl(?string $message = null): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->kontak_wa);
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
}
