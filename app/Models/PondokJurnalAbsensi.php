<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PondokJurnalAbsensi extends Model
{
    use HasFactory;

    public const STATUS_HADIR     = 'hadir';
    public const STATUS_TERLAMBAT = 'terlambat';
    public const STATUS_IZIN      = 'izin';
    public const STATUS_SAKIT     = 'sakit';
    public const STATUS_ALPA      = 'alpa';

    protected $table = 'pondok_jurnal_absensis';

    protected $fillable = [
        'jurnal_mengaji_id',
        'siswa_id',
        'status',
        'keterangan',
    ];

    public function jurnal(): BelongsTo
    {
        return $this->belongsTo(PondokJurnalMengaji::class, 'jurnal_mengaji_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}

