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
        if (Schema::hasTable('pondok_materis')) {
            Schema::table('pondok_materis', function (Blueprint $table) {
                if (!Schema::hasColumn('pondok_materis', 'paket_quran')) {
                    $table->string('paket_quran', 30)->nullable()->after('kategori')->comment('cepatan | lambatan | reguler | kustom');
                }
                if (!Schema::hasColumn('pondok_materis', 'halaman_awal')) {
                    $table->unsignedSmallInteger('halaman_awal')->nullable()->default(1)->after('paket_quran');
                }
                if (!Schema::hasColumn('pondok_materis', 'halaman_akhir')) {
                    $table->unsignedSmallInteger('halaman_akhir')->nullable()->default(604)->after('halaman_awal');
                }
            });

            DB::table('pondok_materis')
                ->where('kategori', 'al_quran')
                ->where(function ($q) {
                    $q->whereIn('kode_materi', ['CPT', 'QUR-CPT'])
                      ->orWhere('nama_materi', 'like', '%Cepatan%')
                      ->orWhere('nama_materi', 'like', '%cepatan%');
                })
                ->update([
                    'paket_quran'    => 'cepatan',
                    'halaman_awal'   => 1,
                    'halaman_akhir'  => 341,
                    'jumlah_halaman' => 341,
                ]);

            DB::table('pondok_materis')
                ->where('kategori', 'al_quran')
                ->where(function ($q) {
                    $q->whereIn('kode_materi', ['LBT', 'QUR-LMB'])
                      ->orWhere('nama_materi', 'like', '%Lambatan%')
                      ->orWhere('nama_materi', 'like', '%lambatan%');
                })
                ->update([
                    'paket_quran'    => 'lambatan',
                    'halaman_awal'   => 342,
                    'halaman_akhir'  => 604,
                    'jumlah_halaman' => 263,
                ]);

            DB::table('pondok_materis')
                ->where('kategori', 'al_quran')
                ->whereNull('paket_quran')
                ->update([
                    'paket_quran'    => 'reguler',
                    'halaman_awal'   => 1,
                    'halaman_akhir'  => 604,
                    'jumlah_halaman' => 604,
                ]);
        }

        if (Schema::hasTable('pondok_target_pengajians')) {
            Schema::table('pondok_target_pengajians', function (Blueprint $table) {
                if (!Schema::hasColumn('pondok_target_pengajians', 'sesi_pengajian')) {
                    $table->string('sesi_pengajian', 40)->nullable()->default('bada_maghrib')->after('tanggal');
                }
            });
        }

        if (Schema::hasTable('pondok_jurnal_mengajis')) {
            Schema::table('pondok_jurnal_mengajis', function (Blueprint $table) {
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'sesi_pengajian')) {
                    $table->string('sesi_pengajian', 40)->nullable()->default('bada_maghrib')->after('tanggal');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'total_santri')) {
                    $table->unsignedSmallInteger('total_santri')->default(0)->after('status_ketercapaian');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'total_hadir')) {
                    $table->unsignedSmallInteger('total_hadir')->default(0)->after('total_santri');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'total_terlambat')) {
                    $table->unsignedSmallInteger('total_terlambat')->default(0)->after('total_hadir');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'total_izin')) {
                    $table->unsignedSmallInteger('total_izin')->default(0)->after('total_terlambat');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'total_sakit')) {
                    $table->unsignedSmallInteger('total_sakit')->default(0)->after('total_izin');
                }
                if (!Schema::hasColumn('pondok_jurnal_mengajis', 'total_alpa')) {
                    $table->unsignedSmallInteger('total_alpa')->default(0)->after('total_sakit');
                }
            });
        }

        if (!Schema::hasTable('pondok_jurnal_absensis')) {
            Schema::create('pondok_jurnal_absensis', function (Blueprint $table) {
                $table->id();
                $table->foreignId('jurnal_mengaji_id')->constrained('pondok_jurnal_mengajis')->cascadeOnDelete();
                $table->foreignId('siswa_id')->constrained('siswas')->cascadeOnDelete();
                $table->string('status', 25)->default('hadir')->index()->comment('hadir | terlambat | izin | sakit | alpa');
                $table->string('keterangan', 255)->nullable();
                $table->timestamps();

                $table->unique(['jurnal_mengaji_id', 'siswa_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pondok_jurnal_absensis');
    }
};

