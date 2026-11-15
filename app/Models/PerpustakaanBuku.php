<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PerpustakaanBuku extends Model
{
    use HasFactory;

    protected $table = 'perpustakaan_bukus';

    protected $fillable = [
        'kode_buku',
        'barcode',
        'judul_buku',
        'penulis',
        'penerbit',
        'kategori',
        'stok_total',
        'stok_tersedia',
        'rak',
        'keterangan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'stok_total'    => 'integer',
            'stok_tersedia' => 'integer',
            'created_by'    => 'integer',
        ];
    }

    public function peminjamans(): HasMany
    {
        return $this->hasMany(PerpustakaanPeminjaman::class, 'perpustakaan_buku_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Buat kode buku otomatis dengan format BK-0001, BK-0002, dst.
     */
    public static function generateKodeBuku(): string
    {
        $lastBuku = self::query()
            ->where('kode_buku', 'like', 'BK-%')
            ->orderByDesc('id')
            ->first();

        $nextNumber = 1;
        if ($lastBuku && preg_match('/^BK-(\d+)$/i', $lastBuku->kode_buku, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        } else {
            $nextNumber = self::count() + 1;
        }

        do {
            $kode = 'BK-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            $exists = self::where('kode_buku', $kode)->exists();
            if ($exists) {
                $nextNumber++;
            }
        } while ($exists);

        return $kode;
    }

    /**
     * Sinkronisasi stok_tersedia berdasarkan stok_total dikurangi jumlah yang masih dipinjam.
     */
    public function syncStokTersedia(): void
    {
        $sedangDipinjam = (int) $this->peminjamans()
            ->where('status', 'dipinjam')
            ->sum('jumlah');

        $tersedia = max(0, ((int) $this->stok_total) - $sedangDipinjam);
        $this->update(['stok_tersedia' => $tersedia]);
    }
}

