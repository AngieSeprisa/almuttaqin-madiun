<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('penerimaan_santris') && !Schema::hasColumn('penerimaan_santris', 'form_schema')) {
            Schema::table('penerimaan_santris', function (Blueprint $table) {
                $table->json('form_schema')->nullable()->after('syarat_dokumen');
            });
        }

        if (Schema::hasTable('pendaftarans') && !Schema::hasColumn('pendaftarans', 'custom_responses')) {
            Schema::table('pendaftarans', function (Blueprint $table) {
                $table->json('custom_responses')->nullable()->after('foto_surat_pernyataan');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('penerimaan_santris') && Schema::hasColumn('penerimaan_santris', 'form_schema')) {
            Schema::table('penerimaan_santris', function (Blueprint $table) {
                $table->dropColumn('form_schema');
            });
        }

        if (Schema::hasTable('pendaftarans') && Schema::hasColumn('pendaftarans', 'custom_responses')) {
            Schema::table('pendaftarans', function (Blueprint $table) {
                $table->dropColumn('custom_responses');
            });
        }
    }
};
