<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('siswas') && Schema::hasColumn('siswas', 'kamar')) {
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('kamar', 100)->nullable()->change();
            });
        }

        if (!Schema::hasTable('kamars')) {
            Schema::create('kamars', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lembaga_id')->nullable()->constrained('lembagas')->nullOnDelete();
                $table->string('nama_kamar', 100)->unique();
                $table->string('kategori_asrama', 30)->default('Putra'); // Putra, Putri, Umum
                $table->integer('kapasitas')->nullable();
                $table->string('wali_kamar', 100)->nullable();
                $table->integer('urutan')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kamars');

        if (Schema::hasTable('siswas') && Schema::hasColumn('siswas', 'kamar')) {
            DB::table('siswas')->whereNull('kamar')->update(['kamar' => '-']);
            Schema::table('siswas', function (Blueprint $table) {
                $table->string('kamar', 100)->nullable(false)->change();
            });
        }
    }
};

