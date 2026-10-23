<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Buat index terpisah untuk lembaga_id terlebih dahulu
        // agar foreign key lembaga_id tidak menghalangi penghapusan index komposit lama
        try {
            Schema::table('mata_pelajarans', function (Blueprint $table) {
                $table->index('lembaga_id', 'mata_pelajarans_lembaga_id_index');
            });
        } catch (\Throwable $e) {}

        // 2. Drop foreign key guru_id jika ada
        try {
            DB::statement("ALTER TABLE `mata_pelajarans` DROP FOREIGN KEY `mata_pelajarans_guru_id_foreign`");
        } catch (\Throwable $e) {}

        // 3. Drop index-index lama yang sudah tidak terpakai
        try {
            DB::statement("ALTER TABLE `mata_pelajarans` DROP INDEX `mata_pelajarans_guru_id_foreign`");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `mata_pelajarans` DROP INDEX `mata_pelajarans_lembaga_id_is_active_index`");
        } catch (\Throwable $e) {}

        // 4. Drop kolom-kolom yang tidak diperlukan di menu plotting pelajaran
        Schema::table('mata_pelajarans', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['guru_id', 'kelompok', 'tingkat_kelas', 'jam_per_minggu', 'is_active', 'keterangan'] as $col) {
                if (Schema::hasColumn('mata_pelajarans', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // 5. Sesuaikan nama kolom menjadi kode dan mata_pelajaran
        Schema::table('mata_pelajarans', function (Blueprint $table) {
            if (Schema::hasColumn('mata_pelajarans', 'kode_mapel') && !Schema::hasColumn('mata_pelajarans', 'kode')) {
                $table->renameColumn('kode_mapel', 'kode');
            }
            if (Schema::hasColumn('mata_pelajarans', 'nama_pelajaran') && !Schema::hasColumn('mata_pelajarans', 'mata_pelajaran')) {
                $table->renameColumn('nama_pelajaran', 'mata_pelajaran');
            }
        });

        // 6. Index untuk pencarian berdasarkan lembaga dan kode
        try {
            Schema::table('mata_pelajarans', function (Blueprint $table) {
                $table->index(['lembaga_id', 'kode'], 'mata_pelajarans_lembaga_kode_index');
            });
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mata_pelajarans', function (Blueprint $table) {
            if (Schema::hasColumn('mata_pelajarans', 'kode') && !Schema::hasColumn('mata_pelajarans', 'kode_mapel')) {
                $table->renameColumn('kode', 'kode_mapel');
            }
            if (Schema::hasColumn('mata_pelajarans', 'mata_pelajaran') && !Schema::hasColumn('mata_pelajarans', 'nama_pelajaran')) {
                $table->renameColumn('mata_pelajaran', 'nama_pelajaran');
            }

            if (!Schema::hasColumn('mata_pelajarans', 'kelompok')) {
                $table->string('kelompok', 50)->default('Wajib');
            }
            if (!Schema::hasColumn('mata_pelajarans', 'tingkat_kelas')) {
                $table->string('tingkat_kelas', 50)->nullable()->default('Semua Kelas');
            }
            if (!Schema::hasColumn('mata_pelajarans', 'guru_id')) {
                $table->foreignId('guru_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('mata_pelajarans', 'jam_per_minggu')) {
                $table->integer('jam_per_minggu')->default(2);
            }
            if (!Schema::hasColumn('mata_pelajarans', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            if (!Schema::hasColumn('mata_pelajarans', 'keterangan')) {
                $table->text('keterangan')->nullable();
            }
        });
    }
};
