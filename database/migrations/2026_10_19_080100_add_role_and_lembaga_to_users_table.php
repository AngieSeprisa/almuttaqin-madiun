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
            $table->string('role', 30)->default('guru')->after('password'); // yayasan, admin_lembaga, guru
            $table->string('jabatan', 100)->nullable()->after('role');
            $table->foreignId('primary_lembaga_id')->nullable()->after('jabatan')->constrained('lembagas')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('primary_lembaga_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_lembaga_id']);
            $table->dropColumn(['role', 'jabatan', 'primary_lembaga_id', 'is_active']);
        });
    }
};
