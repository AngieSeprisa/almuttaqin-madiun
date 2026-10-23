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
            $table->string('nip', 50)->nullable()->after('jabatan');
            $table->string('jenis_kelamin', 20)->nullable()->after('nip');
            $table->string('tempat_lahir', 100)->nullable()->after('jenis_kelamin');
            $table->date('tanggal_lahir')->nullable()->after('tempat_lahir');
            $table->string('telepon', 30)->nullable()->after('tanggal_lahir');
            $table->text('alamat')->nullable()->after('telepon');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir', 'telepon', 'alamat']);
        });
    }
};
