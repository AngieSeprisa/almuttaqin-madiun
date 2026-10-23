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
        if (!Schema::hasTable('mata_pelajarans')) {
            Schema::create('mata_pelajarans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lembaga_id')->constrained('lembagas')->cascadeOnDelete();
                $table->string('kode', 30);
                $table->string('mata_pelajaran', 100);
                $table->timestamps();

                $table->index(['lembaga_id', 'kode']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mata_pelajarans');
    }
};
