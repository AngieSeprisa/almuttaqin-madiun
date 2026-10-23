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
        Schema::create('lembagas', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 30)->unique(); // 'pondok', 'smp', 'sma', 'yayasan'
            $table->string('nama', 100);
            $table->string('singkatan', 30);
            $table->string('kategori', 30)->default('formal'); // pesantren, formal, yayasan
            $table->string('icon', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lembagas');
    }
};
