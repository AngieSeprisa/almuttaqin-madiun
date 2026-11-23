<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 120)->unique();
            $table->string('default_role', 30)->nullable();
            $table->json('lembaga_ids')->nullable();
            $table->json('permissions');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_templates');
    }
};
