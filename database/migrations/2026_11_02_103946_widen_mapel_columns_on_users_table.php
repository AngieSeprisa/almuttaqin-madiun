<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mata_pelajaran')) {
                $table->text('mata_pelajaran')->nullable()->change();
            }
            if (Schema::hasColumn('users', 'mapel_diajukan')) {
                $table->text('mapel_diajukan')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'mata_pelajaran')) {
                $table->string('mata_pelajaran', 100)->nullable()->change();
            }
            if (Schema::hasColumn('users', 'mapel_diajukan')) {
                $table->string('mapel_diajukan', 100)->nullable()->change();
            }
        });
    }
};
