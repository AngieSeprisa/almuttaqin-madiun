<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penerimaan_santris', function (Blueprint $table) {
            $table->string('jenjang', 100)->default('Semua')->change();
            $table->json('jenjangs')->nullable()->after('jenjang');
            $table->json('kontak_wa_per_jenjang')->nullable()->after('kontak_wa');
        });

        Schema::table('lembagas', function (Blueprint $table) {
            $table->text('alamat')->nullable()->after('kategori');
            $table->string('logo')->nullable()->after('alamat');
            $table->string('telepon', 40)->nullable()->after('logo');
            $table->string('email', 120)->nullable()->after('telepon');
        });
    }

    public function down(): void
    {
        Schema::table('penerimaan_santris', function (Blueprint $table) {
            $table->dropColumn(['jenjangs', 'kontak_wa_per_jenjang']);
        });

        Schema::table('lembagas', function (Blueprint $table) {
            $table->dropColumn(['alamat', 'logo', 'telepon', 'email']);
        });
    }
};
