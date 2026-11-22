<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->foreignId('penerimaan_santri_id')
                ->nullable()
                ->after('id')
                ->constrained('penerimaan_santris')
                ->nullOnDelete();
            $table->foreignId('penerimaan_gelombang_id')
                ->nullable()
                ->after('penerimaan_santri_id')
                ->constrained('penerimaan_gelombangs')
                ->nullOnDelete();
            $table->string('public_token', 64)
                ->nullable()
                ->unique()
                ->after('no_induk');
            $table->string('status_pendaftaran', 50)
                ->default('Pending')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('pendaftarans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('penerimaan_gelombang_id');
            $table->dropConstrainedForeignId('penerimaan_santri_id');
            $table->dropUnique(['public_token']);
            $table->dropColumn('public_token');
        });
    }
};
