<?php

namespace Database\Seeders;

use App\Models\Lembaga;
use App\Models\Pendaftaran;
use App\Models\Siswa;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class LembagaAndUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Seed Lembaga
        $lembagas = [
            [
                'kode'      => 'yayasan',
                'nama'      => 'Yayasan Pondok Pesantren Muttaqin',
                'singkatan' => 'Yayasan',
                'kategori'  => 'yayasan',
                'icon'      => 'shield',
                'urutan'    => 1,
            ],
            [
                'kode'      => 'pondok',
                'nama'      => 'Pondok Pesantren Al Muttaqin',
                'singkatan' => 'Pondok',
                'kategori'  => 'pesantren',
                'icon'      => 'home',
                'urutan'    => 2,
            ],
            [
                'kode'      => 'sma',
                'nama'      => 'SMA Al Muttaqin',
                'singkatan' => 'SMA',
                'kategori'  => 'formal',
                'icon'      => 'award',
                'urutan'    => 3,
            ],
            [
                'kode'      => 'smp',
                'nama'      => 'SMP Al Muttaqin',
                'singkatan' => 'SMP',
                'kategori'  => 'formal',
                'icon'      => 'book',
                'urutan'    => 4,
            ],
        ];

        $lembagaMap = [];
        foreach ($lembagas as $item) {
            $lembagaMap[$item['kode']] = Lembaga::updateOrCreate(
                ['kode' => $item['kode']],
                $item
            );
        }

        // 2. Seed Default Users & Permissions
        $defaultPassword = Hash::make('password');

        // User 1: Pimpinan Yayasan (Super Admin)
        $userYayasan = User::updateOrCreate(
            ['email' => 'yayasan@almuttaqin.id'],
            [
                'name'               => 'Pimpinan Yayasan',
                'password'           => $defaultPassword,
                'role'               => 'yayasan',
                'jabatan'            => 'Ketua Yayasan',
                'primary_lembaga_id' => $lembagaMap['yayasan']->id,
                'is_active'          => true,
            ]
        );
        $userYayasan->lembagas()->sync([
            $lembagaMap['yayasan']->id,
            $lembagaMap['pondok']->id,
            $lembagaMap['smp']->id,
            $lembagaMap['sma']->id,
        ]);

        // User 2: Admin SMP
        $userSMP = User::updateOrCreate(
            ['email' => 'admin.smp@almuttaqin.id'],
            [
                'name'               => 'Admin SMP Al-Muttaqin',
                'password'           => $defaultPassword,
                'role'               => 'admin_lembaga',
                'jabatan'            => 'Tata Usaha SMP',
                'primary_lembaga_id' => $lembagaMap['smp']->id,
                'is_active'          => true,
            ]
        );
        $userSMP->lembagas()->sync([$lembagaMap['smp']->id]);
        $this->assignPermissions($userSMP, [
            'siswa'             => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'pendaftaran'       => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'perizinan_sambang' => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
            'perizinan_sakit'   => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
            'pengguna'          => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
        ]);

        // User 3: Admin SMA
        $userSMA = User::updateOrCreate(
            ['email' => 'admin.sma@almuttaqin.id'],
            [
                'name'               => 'Admin SMA Al-Muttaqin',
                'password'           => $defaultPassword,
                'role'               => 'admin_lembaga',
                'jabatan'            => 'Tata Usaha SMA',
                'primary_lembaga_id' => $lembagaMap['sma']->id,
                'is_active'          => true,
            ]
        );
        $userSMA->lembagas()->sync([$lembagaMap['sma']->id]);
        $this->assignPermissions($userSMA, [
            'siswa'             => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'pendaftaran'       => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'perizinan_sambang' => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
            'perizinan_sakit'   => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
            'pengguna'          => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
        ]);

        // User 4: Admin Pondok Pesantren
        $userPondok = User::updateOrCreate(
            ['email' => 'admin.pondok@almuttaqin.id'],
            [
                'name'               => 'Admin Pondok Pesantren',
                'password'           => $defaultPassword,
                'role'               => 'admin_lembaga',
                'jabatan'            => 'Pengasuhan Asrama Santri',
                'primary_lembaga_id' => $lembagaMap['pondok']->id,
                'is_active'          => true,
            ]
        );
        $userPondok->lembagas()->sync([$lembagaMap['pondok']->id]);
        $this->assignPermissions($userPondok, [
            'siswa'             => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
            'pendaftaran'       => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
            'perizinan_sambang' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
            'perizinan_sakit'   => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
            'pengguna'          => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
        ]);

        // User 5: Guru Rangkap (Mengajar di Pondok DAN SMP)
        $userGuruRangkap = User::updateOrCreate(
            ['email' => 'guru.pondok.smp@almuttaqin.id'],
            [
                'name'               => 'Ustadz Syarifuddin, S.Pd.',
                'password'           => $defaultPassword,
                'role'               => 'guru',
                'jabatan'            => 'Guru PAI SMP & Pengasuh Pondok',
                'primary_lembaga_id' => $lembagaMap['pondok']->id,
                'is_active'          => true,
            ]
        );
        // Multi-Lembaga: Terdaftar di Pondok dan SMP
        $userGuruRangkap->lembagas()->sync([
            $lembagaMap['pondok']->id,
            $lembagaMap['smp']->id,
        ]);
        $this->assignPermissions($userGuruRangkap, [
            'siswa'             => ['view' => true, 'create' => false, 'edit' => true, 'delete' => false],
            'pendaftaran'       => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
            'perizinan_sambang' => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'perizinan_sakit'   => ['view' => true, 'create' => true, 'edit' => true, 'delete' => false],
            'pengguna'          => ['view' => false, 'create' => false, 'edit' => false, 'delete' => false],
        ]);

        // 3. Distribusikan jenjang pada data siswa yang ada (SMP, SMA, Reguler)
        $siswas = Siswa::all();
        foreach ($siswas as $index => $siswa) {
            // Sebar: misal indeks genap SMP, ganjil SMA, kelipatan 5 Reguler
            if ($index % 5 === 0) {
                $jenjang = 'Reguler';
            } elseif ($index % 2 === 0) {
                $jenjang = 'SMP';
            } else {
                $jenjang = 'SMA';
            }

            $siswa->update(['jenjang' => $jenjang]);
            if ($siswa->pendaftaran_id) {
                Pendaftaran::where('id', $siswa->pendaftaran_id)->update(['jenjang' => $jenjang]);
            }
        }
    }

    private function assignPermissions(User $user, array $matrix): void
    {
        foreach ($matrix as $menuCode => $rights) {
            UserPermission::updateOrCreate(
                [
                    'user_id'   => $user->id,
                    'menu_code' => $menuCode,
                ],
                [
                    'can_view'   => $rights['view'] ?? false,
                    'can_create' => $rights['create'] ?? false,
                    'can_edit'   => $rights['edit'] ?? false,
                    'can_delete' => $rights['delete'] ?? false,
                ]
            );
        }
    }
}
