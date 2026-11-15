<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_permissions')) {
            return;
        }

        $groupToChildren = [
            'sekolah'            => ['sekolah_jurnal', 'sekolah_rekap_jurnal', 'ekskul', 'jadwal_pelajaran', 'jadwal_saya'],
            'pondok'             => ['materi_pondok', 'target_pengajian', 'jurnal_mengaji'],
            'perpustakaan'       => ['daftar_buku', 'pinjam_buku'],
            'rekap_absensi'      => ['rekap_sholat', 'rekap_pengajian', 'rekap_sekolah', 'rekap_guru', 'rekap_ekskul'],
            'pengaturan_absensi' => ['pengaturan_sesi', 'monitor_rfid'],
            'plotting_kamar'     => ['plotting_pengajian'],
            'perizinan_sakit'    => ['perizinan_haid'],
        ];

        $userIds = DB::table('user_permissions')
            ->whereIn('menu_code', ['sekolah', 'pondok', 'perpustakaan', 'rekap_absensi', 'pengaturan_absensi'])
            ->distinct()
            ->pluck('user_id');

        $now = now();

        foreach ($userIds as $userId) {
            $existingRows = DB::table('user_permissions')
                ->where('user_id', $userId)
                ->get()
                ->keyBy('menu_code');

            // Ekspansi Data Warga & NFC dari siswa bila akun sudah pernah disimpan dari form matriks lama
            if ($existingRows->has('siswa')) {
                $siswaRow = $existingRows->get('siswa');
                foreach (['guru_warga', 'lulus_warga', 'mutasi_warga', 'nfc'] as $childCode) {
                    if (!$existingRows->has($childCode)) {
                        DB::table('user_permissions')->insert([
                            'user_id'    => $userId,
                            'menu_code'  => $childCode,
                            'can_view'   => (bool) $siswaRow->can_view,
                            'can_create' => (bool) $siswaRow->can_create,
                            'can_edit'   => (bool) $siswaRow->can_edit,
                            'can_delete' => (bool) $siswaRow->can_delete,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }

            foreach ($groupToChildren as $parentCode => $childCodes) {
                if (!$existingRows->has($parentCode)) {
                    continue;
                }
                $parentRow = $existingRows->get($parentCode);
                foreach ($childCodes as $childCode) {
                    if (!$existingRows->has($childCode)) {
                        DB::table('user_permissions')->insert([
                            'user_id'    => $userId,
                            'menu_code'  => $childCode,
                            'can_view'   => (bool) $parentRow->can_view,
                            'can_create' => (bool) $parentRow->can_create,
                            'can_edit'   => (bool) $parentRow->can_edit,
                            'can_delete' => (bool) $parentRow->can_delete,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        // Tidak menghapus hak akses granular yang telah disimpan pengguna
    }
};

