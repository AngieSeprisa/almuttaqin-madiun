<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('master_devices')) {
            return;
        }

        if (!Schema::hasColumn('master_devices', 'ip_address')) {
            Schema::table('master_devices', function (Blueprint $table) {
                $table->string('ip_address', 45)->nullable()->after('kategori_area');
            });
        }

        if (!Schema::hasColumn('master_devices', 'last_seen_at')) {
            Schema::table('master_devices', function (Blueprint $table) {
                $table->timestamp('last_seen_at')->nullable()->after('status_aktif');
            });
        }

        // Bersihkan perangkat dummy seeder yang belum pernah dipakai tap nyata sama sekali
        $dummyIds = [
            'ESP32_Presensi_Santri',
            'ESP32_MASJID',
            'ESP32_SEKOLAH',
            'ESP32_DAPUR',
        ];

        $usedDeviceIds = [];
        if (Schema::hasTable('presensis')) {
            $usedDeviceIds = DB::table('presensis')
                ->whereNotNull('device_id')
                ->distinct()
                ->pluck('device_id')
                ->all();

            // Sinkronkan waktu aktif terakhir bagi perangkat yang memang pernah tap nyata
            foreach ($usedDeviceIds as $usedId) {
                $latestTap = DB::table('presensis')
                    ->where('device_id', $usedId)
                    ->max('waktu_tap');

                if ($latestTap) {
                    DB::table('master_devices')
                        ->where('device_id', $usedId)
                        ->whereNull('last_seen_at')
                        ->update(['last_seen_at' => $latestTap]);
                }
            }
        }

        $unusedDummies = array_values(array_diff($dummyIds, $usedDeviceIds));

        if (!empty($unusedDummies)) {
            if (Schema::hasTable('master_sesis')) {
                DB::table('master_sesis')
                    ->whereIn('device_id_target', $unusedDummies)
                    ->update(['device_id_target' => null]);
            }

            DB::table('master_devices')
                ->whereIn('device_id', $unusedDummies)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('master_devices')) {
            return;
        }

        if (Schema::hasColumn('master_devices', 'last_seen_at')) {
            Schema::table('master_devices', function (Blueprint $table) {
                $table->dropColumn('last_seen_at');
            });
        }

        if (Schema::hasColumn('master_devices', 'ip_address')) {
            Schema::table('master_devices', function (Blueprint $table) {
                $table->dropColumn('ip_address');
            });
        }
    }
};
