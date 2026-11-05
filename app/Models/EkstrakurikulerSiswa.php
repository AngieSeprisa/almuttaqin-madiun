<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EkstrakurikulerSiswa extends Model
{
    use HasFactory;

    protected $table = 'ekstrakurikuler_siswas';

    protected $fillable = [
        'ekstrakurikuler_id',
        'siswa_id',
        'peran',
        'tanggal_bergabung',
        'is_active',
    ];

    protected $casts = [
        'tanggal_bergabung' => 'date',
        'is_active'          => 'boolean',
    ];

    public function ekstrakurikuler(): BelongsTo
    {
        return $this->belongsTo(Ekstrakurikuler::class, 'ekstrakurikuler_id');
    }

    public function siswa(): BelongsTo
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }
}
