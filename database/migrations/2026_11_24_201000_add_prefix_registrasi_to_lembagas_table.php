<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lembagas') && !Schema::hasColumn('lembagas', 'prefix_registrasi')) {
            Schema::table('lembagas', function (Blueprint $table) {
                $table->string('prefix_registrasi', 20)->nullable()->after('singkatan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lembagas') && Schema::hasColumn('lembagas', 'prefix_registrasi')) {
            Schema::table('lembagas', function (Blueprint $table) {
                $table->dropColumn('prefix_registrasi');
            });
        }
    }
};
