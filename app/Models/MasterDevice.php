<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterDevice extends Model
{
    protected $table = 'master_devices';

    protected $fillable = [
        'device_id',
        'nama_lokasi',
        'kategori_area',
        'ip_address',
        'status_aktif',
        'last_seen_at',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function sesis()
    {
        return $this->hasMany(MasterSesi::class, 'device_id_target', 'device_id');
    }

    public function presensis()
    {
        return $this->hasMany(Presensi::class, 'device_id', 'device_id');
    }
}
