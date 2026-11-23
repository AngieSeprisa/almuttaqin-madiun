<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermissionTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'default_role',
        'lembaga_ids',
        'permissions',
        'created_by',
    ];

    protected $casts = [
        'lembaga_ids' => 'array',
        'permissions' => 'array',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Jumlah menu aktif pada template ini.
     */
    public function menuCount(): int
    {
        $perms = is_array($this->permissions) ? $this->permissions : [];
        $count = 0;

        foreach ($perms as $actions) {
            if (is_array($actions) && !empty($actions)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Cek apakah suatu menu & aksi dicentang pada template ini.
     */
    public function hasAction(string $menuCode, string $action): bool
    {
        $perms = is_array($this->permissions) ? $this->permissions : [];
        $actions = $perms[$menuCode] ?? [];

        return is_array($actions) && in_array($action, $actions, true);
    }

    /**
     * Label peran sistem default.
     */
    public function roleLabel(): string
    {
        return match ($this->default_role) {
            'yayasan'       => 'Yayasan / Super Admin',
            'admin_lembaga' => 'Admin Lembaga',
            'guru'          => 'Guru / Pengajar',
            default         => '-',
        };
    }
}
