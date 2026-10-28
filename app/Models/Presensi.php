<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Presensi extends Model
{
    protected $table = 'presensis';

    protected $fillable = [
        'device_id',
        'sesi_id',
        'tipe_entitas',
        'entitas_id',
        'rfid_uid',
        'waktu_tap',
        'status_kehadiran',
    ];

    protected $casts = [
        'waktu_tap' => 'datetime',
    ];

    public function sesi()
    {
        return $this->belongsTo(MasterSesi::class, 'sesi_id');
    }

    public function device()
    {
        return $this->belongsTo(MasterDevice::class, 'device_id', 'device_id');
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'entitas_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'entitas_id');
    }

    public function getNamaEntitasAttribute(): string
    {
        if ($this->tipe_entitas === 'santri') {
            return $this->siswa?->nama_lengkap ?? 'Santri #' . $this->entitas_id;
        }
        return $this->user?->name ?? 'Pegawai #' . $this->entitas_id;
    }
}
