<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('sesi_device_allocations')) {
            Schema::create('sesi_device_allocations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('master_sesi_id')
                      ->constrained('master_sesis')
                      ->onDelete('cascade');
                $table->string('device_id', 50)->nullable();
                $table->string('target_kategori', 50)->default('semua');
                $table->text('target_filter')->nullable();
                $table->enum('target_gender', ['ALL', 'L', 'P'])->default('ALL');
                $table->timestamps();

                $table->index(['master_sesi_id', 'device_id']);
            });
        }

        // Migrasi data sesi lama ke tabel alokasi multi-perangkat
        try {
            $existingSesis = DB::table('master_sesis')->get();
            foreach ($existingSesis as $sesi) {
                $alreadyExists = DB::table('sesi_device_allocations')
                    ->where('master_sesi_id', $sesi->id)
                    ->exists();

                if (!$alreadyExists) {
                    $targetKat = 'semua';
                    if ($sesi->target_kategori === 'kamar_khusus') {
                        $targetKat = 'kamar';
                    } elseif ($sesi->target_kategori === 'sekolah') {
                        $targetKat = 'kelas_sekolah';
                    } elseif ($sesi->target_kategori === 'kelas_pengajian') {
                        $targetKat = 'kelas_pengajian';
                    } elseif ($sesi->target_kategori === 'pegawai') {
                        $targetKat = 'pegawai';
                    }

                    $gender = 'ALL';
                    $rawFilter = (string) ($sesi->target_filter ?? '');
                    if (stripos($rawFilter, '[gender: putra]') !== false || stripos($rawFilter, 'putra') !== false && $targetKat === 'gender') {
                        $gender = 'L';
                    } elseif (stripos($rawFilter, '[gender: putri]') !== false || stripos($rawFilter, 'putri') !== false && $targetKat === 'gender') {
                        $gender = 'P';
                    }

                    $cleanFilter = trim(preg_replace('/\[gender:\s*(putra|putri)\]/i', '', $rawFilter));

                    DB::table('sesi_device_allocations')->insert([
                        'master_sesi_id' => $sesi->id,
                        'device_id' => $sesi->device_id_target ?: null,
                        'target_kategori' => $targetKat,
                        'target_filter' => $cleanFilter ?: null,
                        'target_gender' => $gender,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            // Abaikan jika migrasi berjalan di testing environment tanpa tabel master_sesis terisi
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesi_device_allocations');
    }
};
