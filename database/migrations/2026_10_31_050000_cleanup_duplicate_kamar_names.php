<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Bersihkan duplikasi kata "Kamar Kamar" pada tabel siswas
        if (Schema::hasTable('siswas')) {
            DB::statement("UPDATE siswas SET kamar = REPLACE(kamar, 'Kamar Kamar ', 'Kamar ') WHERE kamar LIKE 'Kamar Kamar %'");
            DB::statement("UPDATE siswas SET kamar = 'Kamar Kelas 9 Putri' WHERE TRIM(kamar) = 'Kelas 9 Putri'");
            DB::statement("UPDATE siswas SET kamar = 'Kamar Kelas 7 Putra' WHERE TRIM(kamar) = 'Kelas 7 Putra'");
            DB::statement("UPDATE siswas SET kamar = 'Kamar Kelas 7 Putri' WHERE TRIM(kamar) = 'Kelas 7 Putri'");
            DB::statement("UPDATE siswas SET kamar = 'Kamar Kelas 8 Putra' WHERE TRIM(kamar) = 'Kelas 8 Putra'");
            DB::statement("UPDATE siswas SET kamar = 'Kamar Kelas 8 Putri' WHERE TRIM(kamar) = 'Kelas 8 Putri'");
            DB::statement("UPDATE siswas SET kamar = 'Kamar Kelas 9 Putra' WHERE TRIM(kamar) = 'Kelas 9 Putra'");
        }

        // 2. Bersihkan duplikasi pada tabel master kamars jika ada
        if (Schema::hasTable('kamars')) {
            DB::statement("UPDATE kamars SET nama_kamar = REPLACE(nama_kamar, 'Kamar Kamar ', 'Kamar ') WHERE nama_kamar LIKE 'Kamar Kamar %'");

            // Cek apakah ada record 'Kelas 9 Putri' saat 'Kamar Kelas 9 Putri' sudah ada
            $kamarCanonical = DB::table('kamars')->where('nama_kamar', 'Kamar Kelas 9 Putri')->first();
            $kamarNonPrefix = DB::table('kamars')->where('nama_kamar', 'Kelas 9 Putri')->first();

            if ($kamarCanonical && $kamarNonPrefix) {
                DB::table('kamars')->where('id', $kamarNonPrefix->id)->delete();
            } elseif ($kamarNonPrefix && !$kamarCanonical) {
                DB::table('kamars')->where('id', $kamarNonPrefix->id)->update(['nama_kamar' => 'Kamar Kelas 9 Putri']);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for data cleanup
    }
};

