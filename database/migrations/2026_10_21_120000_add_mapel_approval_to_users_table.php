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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'mapel_diajukan')) {
                $table->string('mapel_diajukan', 100)->nullable()->after('mata_pelajaran');
            }
            if (!Schema::hasColumn('users', 'mapel_status')) {
                $table->string('mapel_status', 30)->default('belum_mengajukan')->after('mapel_diajukan'); // belum_mengajukan, menunggu, disetujui, ditolak
            }
            if (!Schema::hasColumn('users', 'mapel_catatan')) {
                $table->text('mapel_catatan')->nullable()->after('mapel_status');
            }
            if (!Schema::hasColumn('users', 'mapel_diajukan_at')) {
                $table->timestamp('mapel_diajukan_at')->nullable()->after('mapel_catatan');
            }
            if (!Schema::hasColumn('users', 'mapel_diverifikasi_at')) {
                $table->timestamp('mapel_diverifikasi_at')->nullable()->after('mapel_diajukan_at');
            }
            if (!Schema::hasColumn('users', 'mapel_diverifikasi_by')) {
                $table->foreignId('mapel_diverifikasi_by')->nullable()->after('mapel_diverifikasi_at')->constrained('users')->nullOnDelete();
            }
        });

        // Inisialisasi guru yang sudah memiliki mata_pelajaran agar langsung berstatus disetujui
        DB::table('users')
            ->whereNotNull('mata_pelajaran')
            ->where('mata_pelajaran', '!=', '')
            ->update([
                'mapel_diajukan'        => DB::raw('mata_pelajaran'),
                'mapel_status'          => 'disetujui',
                'mapel_diverifikasi_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mapel_diverifikasi_by')) {
                $table->dropForeign(['mapel_diverifikasi_by']);
                $table->dropColumn('mapel_diverifikasi_by');
            }
            $columnsToDrop = [];
            foreach (['mapel_diverifikasi_at', 'mapel_diajukan_at', 'mapel_catatan', 'mapel_status', 'mapel_diajukan'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
