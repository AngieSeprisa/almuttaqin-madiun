<?php

namespace App\Models;

/**
 * Class Subject
 * Model ini merupakan alias/ekstensi dari MataPelajaran (Plotting Mata Pelajaran)
 * agar tersinkronisasi penuh dengan tabel mata_pelajarans.
 */
class Subject extends MataPelajaran
{
    protected $table = 'mata_pelajarans';
}
