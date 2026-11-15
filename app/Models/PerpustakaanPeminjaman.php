<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerpustakaanPeminjaman extends Model
{
    use HasFactory;

    protected $table = 'perpustakaan_peminjamans';

    protected $fillable = [
        'kode_pinjam',
        'perpustakaan_buku_id',
        'siswa_id',
        'nama_peminjam',
        'kelas_kamar',
        'jumlah',
        'tanggal_pinjam',
        'tanggal_jatuh_tempo',
        'tanggal_kembali',
        'status',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'perpustakaan_buku_id' => 'integer',
            'siswa_id'             => 'integer',
            'jumlah'               => 'integer',
            'tanggal_pinjam'       => 'date',
            'tanggal_jatuh_tempo'  => 'date',
            'tanggal_kembali'      => 'date',
            'created_by'           => 'integer',
        ];
    }

    public function buku(): BelongsTo
    {
        return $this->belongsTo(PerpustakaanBuku::class, 'perpustakaan_buku_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Buat kode peminjaman otomatis: PJM-YYYYMMDD-001
     */
    public static function generateKodePinjam(): string
    {
        $prefix = 'PJM-' . Carbon::now()->format('Ymd') . '-';
        $countToday = self::where('kode_pinjam', 'like', $prefix . '%')->count();
        $seq = $countToday + 1;

        do {
            $kode = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            $exists = self::where('kode_pinjam', $kode)->exists();
            if ($exists) {
                $seq++;
            }
        } while ($exists);

        return $kode;
    }

    /**
     * Status efektif: dikembalikan | terlambat | dipinjam
     */
    public function getEffectiveStatusAttribute(): string
    {
        if ($this->status === 'dikembalikan') {
            return 'dikembalikan';
        }

        if ($this->tanggal_jatuh_tempo && Carbon::today()->gt($this->tanggal_jatuh_tempo)) {
            return 'terlambat';
        }

        return 'dipinjam';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->effective_status) {
            'dikembalikan' => 'Sudah Dikembalikan',
            'terlambat'    => 'Melewati Jatuh Tempo',
            default        => 'Sedang Dipinjam',
        };
    }
}

