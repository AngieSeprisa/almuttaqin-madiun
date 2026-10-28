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
        'status_aktif',
    ];

    protected $casts = [
        'status_aktif' => 'boolean',
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
