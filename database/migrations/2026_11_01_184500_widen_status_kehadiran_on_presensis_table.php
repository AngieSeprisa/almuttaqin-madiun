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
        if (Schema::hasTable('presensis') && Schema::hasColumn('presensis', 'status_kehadiran')) {
            Schema::table('presensis', function (Blueprint $table) {
                $table->string('status_kehadiran', 30)->default('tepat_waktu')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('presensis') && Schema::hasColumn('presensis', 'status_kehadiran')) {
            Schema::table('presensis', function (Blueprint $table) {
                $table->enum('status_kehadiran', ['tepat_waktu', 'terlambat', 'sudah_makan'])->default('tepat_waktu')->change();
            });
        }
    }
};

