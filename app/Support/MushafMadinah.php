<?php

namespace App\Support;

class MushafMadinah
{
    public const TOTAL_HALAMAN = 604;

    /**
     * Daftar 114 Surat Al-Qur'an sesuai Mushaf Madinah (604 Halaman)
     * Format: nomor => [nama, jumlah_ayat, halaman_awal, halaman_akhir]
     */
    public static function daftarSurat(): array
    {
        return [
            1   => ['nomor' => 1,   'nama' => 'Al-Fatihah',     'jumlah_ayat' => 7,   'halaman_awal' => 1,   'halaman_akhir' => 1],
            2   => ['nomor' => 2,   'nama' => 'Al-Baqarah',     'jumlah_ayat' => 286, 'halaman_awal' => 2,   'halaman_akhir' => 49],
            3   => ['nomor' => 3,   'nama' => "Ali 'Imran",     'jumlah_ayat' => 200, 'halaman_awal' => 50,  'halaman_akhir' => 76],
            4   => ['nomor' => 4,   'nama' => "An-Nisa'",       'jumlah_ayat' => 176, 'halaman_awal' => 77,  'halaman_akhir' => 106],
            5   => ['nomor' => 5,   'nama' => "Al-Ma'idah",     'jumlah_ayat' => 120, 'halaman_awal' => 106, 'halaman_akhir' => 127],
            6   => ['nomor' => 6,   'nama' => "Al-An'am",       'jumlah_ayat' => 165, 'halaman_awal' => 128, 'halaman_akhir' => 150],
            7   => ['nomor' => 7,   'nama' => "Al-A'raf",       'jumlah_ayat' => 206, 'halaman_awal' => 151, 'halaman_akhir' => 176],
            8   => ['nomor' => 8,   'nama' => 'Al-Anfal',       'jumlah_ayat' => 75,  'halaman_awal' => 177, 'halaman_akhir' => 186],
            9   => ['nomor' => 9,   'nama' => 'At-Taubah',      'jumlah_ayat' => 129, 'halaman_awal' => 187, 'halaman_akhir' => 207],
            10  => ['nomor' => 10,  'nama' => 'Yunus',          'jumlah_ayat' => 109, 'halaman_awal' => 208, 'halaman_akhir' => 221],
            11  => ['nomor' => 11,  'nama' => 'Hud',            'jumlah_ayat' => 123, 'halaman_awal' => 221, 'halaman_akhir' => 235],
            12  => ['nomor' => 12,  'nama' => 'Yusuf',          'jumlah_ayat' => 111, 'halaman_awal' => 235, 'halaman_akhir' => 248],
            13  => ['nomor' => 13,  'nama' => "Ar-Ra'd",        'jumlah_ayat' => 43,  'halaman_awal' => 249, 'halaman_akhir' => 255],
            14  => ['nomor' => 14,  'nama' => 'Ibrahim',        'jumlah_ayat' => 52,  'halaman_awal' => 255, 'halaman_akhir' => 261],
            15  => ['nomor' => 15,  'nama' => 'Al-Hijr',        'jumlah_ayat' => 99,  'halaman_awal' => 262, 'halaman_akhir' => 267],
            16  => ['nomor' => 16,  'nama' => 'An-Nahl',        'jumlah_ayat' => 128, 'halaman_awal' => 267, 'halaman_akhir' => 281],
            17  => ['nomor' => 17,  'nama' => "Al-Isra'",       'jumlah_ayat' => 111, 'halaman_awal' => 282, 'halaman_akhir' => 293],
            18  => ['nomor' => 18,  'nama' => 'Al-Kahf',        'jumlah_ayat' => 110, 'halaman_awal' => 293, 'halaman_akhir' => 304],
            19  => ['nomor' => 19,  'nama' => 'Maryam',         'jumlah_ayat' => 98,  'halaman_awal' => 305, 'halaman_akhir' => 312],
            20  => ['nomor' => 20,  'nama' => 'Taha',           'jumlah_ayat' => 135, 'halaman_awal' => 312, 'halaman_akhir' => 321],
            21  => ['nomor' => 21,  'nama' => "Al-Anbiya'",     'jumlah_ayat' => 112, 'halaman_awal' => 322, 'halaman_akhir' => 331],
            22  => ['nomor' => 22,  'nama' => 'Al-Hajj',        'jumlah_ayat' => 78,  'halaman_awal' => 332, 'halaman_akhir' => 341],
            23  => ['nomor' => 23,  'nama' => "Al-Mu'minun",    'jumlah_ayat' => 118, 'halaman_awal' => 342, 'halaman_akhir' => 349],
            24  => ['nomor' => 24,  'nama' => 'An-Nur',         'jumlah_ayat' => 64,  'halaman_awal' => 350, 'halaman_akhir' => 359],
            25  => ['nomor' => 25,  'nama' => 'Al-Furqan',      'jumlah_ayat' => 77,  'halaman_awal' => 359, 'halaman_akhir' => 366],
            26  => ['nomor' => 26,  'nama' => "Asy-Syu'ara'",   'jumlah_ayat' => 227, 'halaman_awal' => 367, 'halaman_akhir' => 376],
            27  => ['nomor' => 27,  'nama' => 'An-Naml',        'jumlah_ayat' => 93,  'halaman_awal' => 377, 'halaman_akhir' => 385],
            28  => ['nomor' => 28,  'nama' => 'Al-Qasas',       'jumlah_ayat' => 88,  'halaman_awal' => 385, 'halaman_akhir' => 396],
            29  => ['nomor' => 29,  'nama' => "Al-'Ankabut",    'jumlah_ayat' => 69,  'halaman_awal' => 396, 'halaman_akhir' => 404],
            30  => ['nomor' => 30,  'nama' => 'Ar-Rum',         'jumlah_ayat' => 60,  'halaman_awal' => 404, 'halaman_akhir' => 410],
            31  => ['nomor' => 31,  'nama' => 'Luqman',         'jumlah_ayat' => 34,  'halaman_awal' => 411, 'halaman_akhir' => 414],
            32  => ['nomor' => 32,  'nama' => 'As-Sajdah',      'jumlah_ayat' => 30,  'halaman_awal' => 415, 'halaman_akhir' => 417],
            33  => ['nomor' => 33,  'nama' => 'Al-Ahzab',       'jumlah_ayat' => 73,  'halaman_awal' => 418, 'halaman_akhir' => 427],
            34  => ['nomor' => 34,  'nama' => "Saba'",          'jumlah_ayat' => 54,  'halaman_awal' => 428, 'halaman_akhir' => 434],
            35  => ['nomor' => 35,  'nama' => 'Fatir',          'jumlah_ayat' => 45,  'halaman_awal' => 434, 'halaman_akhir' => 440],
            36  => ['nomor' => 36,  'nama' => 'Yasin',          'jumlah_ayat' => 83,  'halaman_awal' => 440, 'halaman_akhir' => 445],
            37  => ['nomor' => 37,  'nama' => 'As-Saffat',      'jumlah_ayat' => 182, 'halaman_awal' => 446, 'halaman_akhir' => 452],
            38  => ['nomor' => 38,  'nama' => 'Sad',            'jumlah_ayat' => 88,  'halaman_awal' => 453, 'halaman_akhir' => 458],
            39  => ['nomor' => 39,  'nama' => 'Az-Zumar',       'jumlah_ayat' => 75,  'halaman_awal' => 458, 'halaman_akhir' => 467],
            40  => ['nomor' => 40,  'nama' => 'Ghafir',         'jumlah_ayat' => 85,  'halaman_awal' => 467, 'halaman_akhir' => 476],
            41  => ['nomor' => 41,  'nama' => 'Fussilat',       'jumlah_ayat' => 54,  'halaman_awal' => 477, 'halaman_akhir' => 482],
            42  => ['nomor' => 42,  'nama' => 'Asy-Syura',      'jumlah_ayat' => 53,  'halaman_awal' => 483, 'halaman_akhir' => 489],
            43  => ['nomor' => 43,  'nama' => 'Az-Zukhruf',     'jumlah_ayat' => 89,  'halaman_awal' => 489, 'halaman_akhir' => 495],
            44  => ['nomor' => 44,  'nama' => 'Ad-Dukhan',      'jumlah_ayat' => 59,  'halaman_awal' => 496, 'halaman_akhir' => 498],
            45  => ['nomor' => 45,  'nama' => 'Al-Jasiyah',     'jumlah_ayat' => 37,  'halaman_awal' => 499, 'halaman_akhir' => 502],
            46  => ['nomor' => 46,  'nama' => 'Al-Ahqaf',       'jumlah_ayat' => 35,  'halaman_awal' => 502, 'halaman_akhir' => 506],
            47  => ['nomor' => 47,  'nama' => 'Muhammad',       'jumlah_ayat' => 38,  'halaman_awal' => 507, 'halaman_akhir' => 510],
            48  => ['nomor' => 48,  'nama' => 'Al-Fath',        'jumlah_ayat' => 29,  'halaman_awal' => 511, 'halaman_akhir' => 515],
            49  => ['nomor' => 49,  'nama' => 'Al-Hujurat',     'jumlah_ayat' => 18,  'halaman_awal' => 515, 'halaman_akhir' => 517],
            50  => ['nomor' => 50,  'nama' => 'Qaf',            'jumlah_ayat' => 45,  'halaman_awal' => 518, 'halaman_akhir' => 520],
            51  => ['nomor' => 51,  'nama' => 'Az-Zariyat',     'jumlah_ayat' => 60,  'halaman_awal' => 520, 'halaman_akhir' => 523],
            52  => ['nomor' => 52,  'nama' => 'At-Tur',         'jumlah_ayat' => 49,  'halaman_awal' => 523, 'halaman_akhir' => 525],
            53  => ['nomor' => 53,  'nama' => 'An-Najm',        'jumlah_ayat' => 62,  'halaman_awal' => 526, 'halaman_akhir' => 528],
            54  => ['nomor' => 54,  'nama' => 'Al-Qamar',       'jumlah_ayat' => 55,  'halaman_awal' => 528, 'halaman_akhir' => 531],
            55  => ['nomor' => 55,  'nama' => 'Ar-Rahman',      'jumlah_ayat' => 78,  'halaman_awal' => 531, 'halaman_akhir' => 534],
            56  => ['nomor' => 56,  'nama' => "Al-Waqi'ah",     'jumlah_ayat' => 96,  'halaman_awal' => 534, 'halaman_akhir' => 537],
            57  => ['nomor' => 57,  'nama' => 'Al-Hadid',       'jumlah_ayat' => 29,  'halaman_awal' => 537, 'halaman_akhir' => 541],
            58  => ['nomor' => 58,  'nama' => 'Al-Mujadalah',   'jumlah_ayat' => 22,  'halaman_awal' => 542, 'halaman_akhir' => 545],
            59  => ['nomor' => 59,  'nama' => 'Al-Hasyr',       'jumlah_ayat' => 24,  'halaman_awal' => 545, 'halaman_akhir' => 548],
            60  => ['nomor' => 60,  'nama' => 'Al-Mumtahanah',  'jumlah_ayat' => 13,  'halaman_awal' => 549, 'halaman_akhir' => 551],
            61  => ['nomor' => 61,  'nama' => 'As-Saff',        'jumlah_ayat' => 14,  'halaman_awal' => 551, 'halaman_akhir' => 552],
            62  => ['nomor' => 62,  'nama' => "Al-Jumu'ah",     'jumlah_ayat' => 11,  'halaman_awal' => 553, 'halaman_akhir' => 554],
            63  => ['nomor' => 63,  'nama' => 'Al-Munafiqun',   'jumlah_ayat' => 11,  'halaman_awal' => 554, 'halaman_akhir' => 555],
            64  => ['nomor' => 64,  'nama' => 'At-Tagabun',     'jumlah_ayat' => 18,  'halaman_awal' => 556, 'halaman_akhir' => 557],
            65  => ['nomor' => 65,  'nama' => 'At-Talaq',       'jumlah_ayat' => 12,  'halaman_awal' => 558, 'halaman_akhir' => 559],
            66  => ['nomor' => 66,  'nama' => 'At-Tahrim',      'jumlah_ayat' => 12,  'halaman_awal' => 560, 'halaman_akhir' => 561],
            67  => ['nomor' => 67,  'nama' => 'Al-Mulk',        'jumlah_ayat' => 30,  'halaman_awal' => 562, 'halaman_akhir' => 564],
            68  => ['nomor' => 68,  'nama' => 'Al-Qalam',       'jumlah_ayat' => 52,  'halaman_awal' => 564, 'halaman_akhir' => 566],
            69  => ['nomor' => 69,  'nama' => 'Al-Haqqah',      'jumlah_ayat' => 52,  'halaman_awal' => 566, 'halaman_akhir' => 568],
            70  => ['nomor' => 70,  'nama' => "Al-Ma'arij",     'jumlah_ayat' => 44,  'halaman_awal' => 568, 'halaman_akhir' => 570],
            71  => ['nomor' => 71,  'nama' => 'Nuh',            'jumlah_ayat' => 28,  'halaman_awal' => 570, 'halaman_akhir' => 571],
            72  => ['nomor' => 72,  'nama' => 'Al-Jinn',        'jumlah_ayat' => 28,  'halaman_awal' => 572, 'halaman_akhir' => 573],
            73  => ['nomor' => 73,  'nama' => 'Al-Muzzammil',   'jumlah_ayat' => 20,  'halaman_awal' => 574, 'halaman_akhir' => 575],
            74  => ['nomor' => 74,  'nama' => 'Al-Muddassir',   'jumlah_ayat' => 56,  'halaman_awal' => 575, 'halaman_akhir' => 577],
            75  => ['nomor' => 75,  'nama' => 'Al-Qiyamah',     'jumlah_ayat' => 40,  'halaman_awal' => 577, 'halaman_akhir' => 578],
            76  => ['nomor' => 76,  'nama' => 'Al-Insan',       'jumlah_ayat' => 31,  'halaman_awal' => 578, 'halaman_akhir' => 580],
            77  => ['nomor' => 77,  'nama' => 'Al-Mursalat',    'jumlah_ayat' => 50,  'halaman_awal' => 580, 'halaman_akhir' => 581],
            78  => ['nomor' => 78,  'nama' => "An-Naba'",       'jumlah_ayat' => 40,  'halaman_awal' => 582, 'halaman_akhir' => 583],
            79  => ['nomor' => 79,  'nama' => "An-Nazi'at",     'jumlah_ayat' => 46,  'halaman_awal' => 583, 'halaman_akhir' => 584],
            80  => ['nomor' => 80,  'nama' => "'Abasa",         'jumlah_ayat' => 42,  'halaman_awal' => 585, 'halaman_akhir' => 585],
            81  => ['nomor' => 81,  'nama' => 'At-Takwir',      'jumlah_ayat' => 29,  'halaman_awal' => 586, 'halaman_akhir' => 586],
            82  => ['nomor' => 82,  'nama' => 'Al-Infitar',     'jumlah_ayat' => 19,  'halaman_awal' => 587, 'halaman_akhir' => 587],
            83  => ['nomor' => 83,  'nama' => 'Al-Mutaffifin',  'jumlah_ayat' => 36,  'halaman_awal' => 587, 'halaman_akhir' => 589],
            84  => ['nomor' => 84,  'nama' => 'Al-Insyiqaq',    'jumlah_ayat' => 25,  'halaman_awal' => 589, 'halaman_akhir' => 589],
            85  => ['nomor' => 85,  'nama' => 'Al-Buruj',       'jumlah_ayat' => 22,  'halaman_awal' => 590, 'halaman_akhir' => 590],
            86  => ['nomor' => 86,  'nama' => 'At-Tariq',       'jumlah_ayat' => 17,  'halaman_awal' => 591, 'halaman_akhir' => 591],
            87  => ['nomor' => 87,  'nama' => "Al-A'la",        'jumlah_ayat' => 19,  'halaman_awal' => 591, 'halaman_akhir' => 592],
            88  => ['nomor' => 88,  'nama' => 'Al-Gasyiyah',    'jumlah_ayat' => 26,  'halaman_awal' => 592, 'halaman_akhir' => 592],
            89  => ['nomor' => 89,  'nama' => 'Al-Fajr',        'jumlah_ayat' => 30,  'halaman_awal' => 593, 'halaman_akhir' => 594],
            90  => ['nomor' => 90,  'nama' => 'Al-Balad',       'jumlah_ayat' => 20,  'halaman_awal' => 594, 'halaman_akhir' => 594],
            91  => ['nomor' => 91,  'nama' => 'Asy-Syams',      'jumlah_ayat' => 15,  'halaman_awal' => 595, 'halaman_akhir' => 595],
            92  => ['nomor' => 92,  'nama' => 'Al-Lail',        'jumlah_ayat' => 21,  'halaman_awal' => 595, 'halaman_akhir' => 596],
            93  => ['nomor' => 93,  'nama' => 'Ad-Duha',        'jumlah_ayat' => 11,  'halaman_awal' => 596, 'halaman_akhir' => 596],
            94  => ['nomor' => 94,  'nama' => 'Asy-Syarh',      'jumlah_ayat' => 8,   'halaman_awal' => 596, 'halaman_akhir' => 596],
            95  => ['nomor' => 95,  'nama' => 'At-Tin',         'jumlah_ayat' => 8,   'halaman_awal' => 597, 'halaman_akhir' => 597],
            96  => ['nomor' => 96,  'nama' => "Al-'Alaq",       'jumlah_ayat' => 19,  'halaman_awal' => 597, 'halaman_akhir' => 597],
            97  => ['nomor' => 97,  'nama' => 'Al-Qadr',        'jumlah_ayat' => 5,   'halaman_awal' => 598, 'halaman_akhir' => 598],
            98  => ['nomor' => 98,  'nama' => 'Al-Bayyinah',    'jumlah_ayat' => 8,   'halaman_awal' => 598, 'halaman_akhir' => 599],
            99  => ['nomor' => 99,  'nama' => 'Az-Zalzalah',    'jumlah_ayat' => 8,   'halaman_awal' => 599, 'halaman_akhir' => 599],
            100 => ['nomor' => 100, 'nama' => "Al-'Adiyat",     'jumlah_ayat' => 11,  'halaman_awal' => 599, 'halaman_akhir' => 600],
            101 => ['nomor' => 101, 'nama' => "Al-Qari'ah",     'jumlah_ayat' => 11,  'halaman_awal' => 600, 'halaman_akhir' => 600],
            102 => ['nomor' => 102, 'nama' => 'At-Takasur',     'jumlah_ayat' => 8,   'halaman_awal' => 600, 'halaman_akhir' => 600],
            103 => ['nomor' => 103, 'nama' => "Al-'Asr",        'jumlah_ayat' => 3,   'halaman_awal' => 601, 'halaman_akhir' => 601],
            104 => ['nomor' => 104, 'nama' => 'Al-Humazah',     'jumlah_ayat' => 9,   'halaman_awal' => 601, 'halaman_akhir' => 601],
            105 => ['nomor' => 105, 'nama' => 'Al-Fil',         'jumlah_ayat' => 5,   'halaman_awal' => 601, 'halaman_akhir' => 601],
            106 => ['nomor' => 106, 'nama' => 'Quraisy',        'jumlah_ayat' => 4,   'halaman_awal' => 602, 'halaman_akhir' => 602],
            107 => ['nomor' => 107, 'nama' => "Al-Ma'un",       'jumlah_ayat' => 7,   'halaman_awal' => 602, 'halaman_akhir' => 602],
            108 => ['nomor' => 108, 'nama' => 'Al-Kausar',      'jumlah_ayat' => 3,   'halaman_awal' => 602, 'halaman_akhir' => 602],
            109 => ['nomor' => 109, 'nama' => 'Al-Kafirun',     'jumlah_ayat' => 6,   'halaman_awal' => 603, 'halaman_akhir' => 603],
            110 => ['nomor' => 110, 'nama' => 'An-Nasr',        'jumlah_ayat' => 3,   'halaman_awal' => 603, 'halaman_akhir' => 603],
            111 => ['nomor' => 111, 'nama' => 'Al-Lahab',       'jumlah_ayat' => 5,   'halaman_awal' => 603, 'halaman_akhir' => 603],
            112 => ['nomor' => 112, 'nama' => 'Al-Ikhlas',      'jumlah_ayat' => 4,   'halaman_awal' => 604, 'halaman_akhir' => 604],
            113 => ['nomor' => 113, 'nama' => 'Al-Falaq',       'jumlah_ayat' => 5,   'halaman_awal' => 604, 'halaman_akhir' => 604],
            114 => ['nomor' => 114, 'nama' => 'An-Nas',         'jumlah_ayat' => 6,   'halaman_awal' => 604, 'halaman_akhir' => 604],
        ];
    }

    public static function getSurat(int $nomor): ?array
    {
        return self::daftarSurat()[$nomor] ?? null;
    }

    public static function findByName(?string $nama): ?array
    {
        if (!$nama) {
            return null;
        }
        $clean = mb_strtolower(trim($nama));
        foreach (self::daftarSurat() as $s) {
            if (mb_strtolower($s['nama']) === $clean) {
                return $s;
            }
        }
        return null;
    }

    /**
     * Estimasi nomor halaman Mushaf Madinah (1–604) berdasarkan nomor surat dan nomor ayat.
     */
    public static function estimasiHalaman(int $suratNomor, int $ayat): int
    {
        $surat = self::getSurat($suratNomor);
        if (!$surat) {
            return 1;
        }

        $jmlAyat = max(1, (int) $surat['jumlah_ayat']);
        $ayatClamped = max(1, min($jmlAyat, $ayat));
        $halAwal = (int) $surat['halaman_awal'];
        $halAkhir = (int) $surat['halaman_akhir'];

        if ($halAwal === $halAkhir || $jmlAyat <= 1) {
            return $halAwal;
        }

        $ratio = ($ayatClamped - 1) / ($jmlAyat - 1);
        return (int) round($halAwal + $ratio * ($halAkhir - $halAwal));
    }
}

