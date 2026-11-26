<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PondokCapaianMateri extends Model
{
    use HasFactory;

    protected $table = 'pondok_capaian_materis';

    protected $fillable = [
        'siswa_id',
        'scope_key',
        'kategori',
        'pondok_materi_id',
        'juz',
        'halaman_selesai',
        'jumlah_selesai',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'juz'             => 'integer',
            'halaman_selesai' => 'array',
            'jumlah_selesai'  => 'integer',
        ];
    }

    public static function scopeKeyForQuranJuz(int $juz): string
    {
        return 'quran_juz_' . $juz;
    }

    public static function scopeKeyForHadist(int $materiId): string
    {
        return 'hadist_' . $materiId;
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function materi(): BelongsTo
    {
        return $this->belongsTo(PondokMateri::class, 'pondok_materi_id');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
