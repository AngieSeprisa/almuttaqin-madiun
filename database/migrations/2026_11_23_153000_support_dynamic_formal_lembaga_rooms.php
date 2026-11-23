<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siswas') && Schema::hasColumn('siswas', 'jenjang')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('jenjang', 50)->default('Reguler')->change();
            });
        }

        if (Schema::hasTable('classrooms') && Schema::hasColumn('classrooms', 'jenjang')) {
            Schema::table('classrooms', function (Blueprint $table) {
                $table->string('jenjang', 50)->change();
            });
        }

        if (Schema::hasTable('time_slots') && Schema::hasColumn('time_slots', 'jenjang')) {
            Schema::table('time_slots', function (Blueprint $table) {
                $table->string('jenjang', 50)->default('Semua')->change();
            });
        }

        if (Schema::hasTable('ekstrakurikulers') && Schema::hasColumn('ekstrakurikulers', 'jenjang')) {
            Schema::table('ekstrakurikulers', function (Blueprint $table) {
                $table->string('jenjang', 50)->default('SMP')->change();
            });
        }

        if (Schema::hasTable('ujian') && !Schema::hasColumn('ujian', 'lembaga_id')) {
            Schema::table('ujian', function (Blueprint $table) {
                $table->foreignId('lembaga_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('lembagas')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ujian') && Schema::hasColumn('ujian', 'lembaga_id')) {
            Schema::table('ujian', function (Blueprint $table) {
                $table->dropConstrainedForeignId('lembaga_id');
            });
        }
    }
};
