<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'is_guru_pondok')) {
                $table->boolean('is_guru_pondok')->default(false)->after('is_guru_sekolah');
            }
            if (!Schema::hasColumn('users', 'pondok_kelas_ids')) {
                $table->json('pondok_kelas_ids')->nullable()->after('is_guru_pondok');
            }
            if (!Schema::hasColumn('users', 'pondok_materi')) {
                $table->text('pondok_materi')->nullable()->after('pondok_kelas_ids');
            }
            if (!Schema::hasColumn('users', 'pondok_catatan')) {
                $table->string('pondok_catatan', 255)->nullable()->after('pondok_materi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $columns = ['pondok_catatan', 'pondok_materi', 'pondok_kelas_ids', 'is_guru_pondok'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
