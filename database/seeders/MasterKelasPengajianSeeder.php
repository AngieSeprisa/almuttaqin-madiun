<?php

namespace Database\Seeders;

use App\Models\MasterKelasPengajian;
use Illuminate\Database\Seeder;

class MasterKelasPengajianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MasterKelasPengajian::seedStandar();
    }
}

