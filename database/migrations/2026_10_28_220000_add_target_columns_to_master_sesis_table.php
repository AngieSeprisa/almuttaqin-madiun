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
        if (!Schema::hasTable('master_sesis')) {
            return;
        }

        $newlyAdded = false;

        if (!Schema::hasColumn('master_sesis', 'target_kategori')) {
            Schema::table('master_sesis', function (Blueprint $table) {
                $table->enum('target_kategori', ['semua_santri', 'kamar_khusus', 'sekolah', 'pegawai'])
                    ->default('semua_santri')
                    ->after('kategori');
            });
            $newlyAdded = true;
        }

        if (!Schema::hasColumn('master_sesis', 'target_filter')) {
            Schema::table('master_sesis', function (Blueprint $table) {
                $table->string('target_filter', 100)
                    ->nullable()
                    ->comment('Contoh: Reguler, SMP, SMA, atau ALL')
                    ->after('target_kategori');
            });
        }

        // Sinkronkan nilai default awal bila kolom baru saja ditambahkan
        if ($newlyAdded) {
            DB::table('master_sesis')
                ->where('kode_sesi', 'REGULER_HARIAN')
                ->update([
                    'target_kategori' => 'kamar_khusus',
                    'target_filter' => 'Reguler',
                ]);

            DB::table('master_sesis')
                ->where('kode_sesi', 'like', 'SEKOLAH%')
                ->update([
                    'target_kategori' => 'sekolah',
                    'target_filter' => 'SMP',
                ]);

            DB::table('master_sesis')
                ->where('kode_sesi', 'PEGAWAI_MASUK')
                ->update([
                    'target_kategori' => 'pegawai',
                    'target_filter' => null,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('master_sesis')) {
            return;
        }

        if (Schema::hasColumn('master_sesis', 'target_filter')) {
            Schema::table('master_sesis', function (Blueprint $table) {
                $table->dropColumn('target_filter');
            });
        }

        if (Schema::hasColumn('master_sesis', 'target_kategori')) {
            Schema::table('master_sesis', function (Blueprint $table) {
                $table->dropColumn('target_kategori');
            });
        }
    }
};

