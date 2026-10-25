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
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // 1. Bersihkan schedules dummy jika ada yang merujuk ke tabel subjects lama
        DB::table('schedules')->truncate();

        // 2. Drop foreign key lama yang merujuk ke tabel subjects
        Schema::table('schedules', function (Blueprint $table) {
            try {
                $table->dropForeign(['subject_id']);
            } catch (\Throwable $e) {}
        });

        // 3. Pasang foreign key baru yang merujuk ke tabel mata_pelajarans (Plotting Mata Pelajaran)
        Schema::table('schedules', function (Blueprint $table) {
            $table->foreign('subject_id')
                ->references('id')
                ->on('mata_pelajarans')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            try {
                $table->dropForeign(['subject_id']);
            } catch (\Throwable $e) {}
        });

        Schema::table('schedules', function (Blueprint $table) {
            if (Schema::hasTable('subjects')) {
                $table->foreign('subject_id')
                    ->references('id')
                    ->on('subjects')
                    ->cascadeOnDelete();
            }
        });
    }
};
