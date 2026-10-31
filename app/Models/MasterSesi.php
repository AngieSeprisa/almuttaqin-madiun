<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterSesi extends Model
{
    protected $table = 'master_sesis';

    protected $fillable = [
        'device_id_target',
        'kode_sesi',
        'nama_sesi',
        'jam_mulai',
        'jam_selesai',
        'toleransi_menit',
        'kategori',
        'target_kategori',
        'target_filter',
    ];

    public function device()
    {
        return $this->belongsTo(MasterDevice::class, 'device_id_target', 'device_id');
    }

    public function allocations()
    {
        return $this->hasMany(SesiDeviceAllocation::class, 'master_sesi_id');
    }

    public function presensis()
    {
        return $this->hasMany(Presensi::class, 'sesi_id');
    }
}
