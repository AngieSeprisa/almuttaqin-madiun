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
        if (Schema::hasTable('master_sesis') && Schema::hasColumn('master_sesis', 'target_filter')) {
            Schema::table('master_sesis', function (Blueprint $table) {
                $table->string('target_filter', 255)->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('master_sesis') && Schema::hasColumn('master_sesis', 'target_filter')) {
            Schema::table('master_sesis', function (Blueprint $table) {
                $table->string('target_filter', 100)->nullable()->change();
            });
        }
    }
};

