<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesiDeviceAllocation extends Model
{
    protected $table = 'sesi_device_allocations';

    protected $fillable = [
        'master_sesi_id',
        'device_id',
        'target_kategori',
        'target_filter',
        'target_gender',
    ];

    public function sesi()
    {
        return $this->belongsTo(MasterSesi::class, 'master_sesi_id');
    }

    public function device()
    {
        return $this->belongsTo(MasterDevice::class, 'device_id', 'device_id');
    }
}
