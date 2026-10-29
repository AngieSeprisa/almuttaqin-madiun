<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class Kamar extends Model
{
    use HasFactory;

    protected $table = 'kamars';

    protected $fillable = [
        'lembaga_id',
        'nama_kamar',
        'kategori_asrama',
        'kapasitas',
        'wali_kamar',
        'urutan',
        'is_active',
    ];

    protected $casts = [
        'kapasitas' => 'integer',
        'urutan'    => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke Lembaga (Pondok Pesantren).
     */
    public function lembaga()
    {
        return $this->belongsTo(Lembaga::class, 'lembaga_id');
    }

    /**
     * Scope kamar yang aktif saja.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Format tampilan kamar yang rapi (contoh: "Kamar Abu Bakar").
     */
    public function getDisplayNameAttribute(): string
    {
        $nama = trim((string) $this->nama_kamar);
        if (str_starts_with(mb_strtolower($nama), 'kamar ')) {
            return $nama;
        }

        return 'Kamar ' . $nama;
    }

    /**
     * Gabungkan daftar kamar aktif dari tabel kamars dan kolom siswas.kamar yang ada.
     */
    public static function daftarNamaKamarAktif(): Collection
    {
        $masterKamars = collect();
        if (Schema::hasTable('kamars')) {
            $masterKamars = self::where('is_active', true)
                ->orderBy('urutan')
                ->orderBy('nama_kamar')
                ->pluck('nama_kamar');
        }

        $siswaKamars = collect();
        if (Schema::hasTable('siswas')) {
            $siswaKamars = Siswa::whereNotNull('kamar')
                ->where('kamar', '!=', '')
                ->where('kamar', '!=', '-')
                ->distinct()
                ->orderBy('kamar')
                ->pluck('kamar');
        }

        $merged = collect();
        foreach ($masterKamars->concat($siswaKamars) as $nama) {
            $clean = trim((string) $nama);
            if ($clean === '' || $clean === '-') {
                continue;
            }
            $exists = $merged->contains(fn ($existing) => mb_strtolower($existing) === mb_strtolower($clean));
            if (!$exists) {
                $merged->push($clean);
            }
        }

        return $merged->values();
    }
}

