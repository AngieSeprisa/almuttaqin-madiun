<?php

namespace App\Services\Omr;

use App\Models\Siswa;
use App\Models\Ujian;

/**
 * Pure-PHP Optical Character Recognition (OCR) & 1D Code 128 Barcode Decoder untuk LJK
 * Membaca:
 * 1. Barcode 1D (Code 128B) pada kotak identitas LJK
 * 2. Baris Teks Monospace "ID: UJN-XXXX-0192" di bawah QR/Barcode
 * 3. Baris Teks "Nama Siswa : NOVA ABDUL MUTTHALIB" pada header LJK
 */
class LjkOcrService
{
    /**
     * Tabel pola standar ISO/IEC 15417 Code 128 (nilai 0 s.d. 106)
     */
    public const CODE128_PATTERNS = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232', '2331112',
    ];

    /**
     * Template 5x7 Normalized Grid untuk Angka Monospace Bold ('0'..'9')
     */
    public const DIGIT_TEMPLATES = [
        '0' => [0.17,0.75,0.97,0.72,0.12,0.64,0.75,0.32,0.81,0.52,0.91,0.38,0.29,0.85,0.76,1,0.68,0.83,0.71,0.83,0.93,0.78,0.22,0.51,0.75,0.68,0.73,0.33,0.83,0.47,0.21,0.8,0.96,0.66,0.08],
        '1' => [0.26,0.66,0.97,0.32,0,0.81,0.77,0.93,0.32,0,0.19,0.11,0.85,0.32,0,0,0.06,0.85,0.32,0,0,0.06,0.85,0.32,0,0.2,0.29,0.88,0.49,0.22,0.81,1,1,1,0.87],
        '2' => [0.51,0.88,0.97,0.74,0.18,0.45,0.4,0.41,0.95,0.54,0,0,0.12,0.89,0.55,0,0,0.46,0.9,0.23,0.02,0.38,0.79,0.3,0,0.47,0.95,0.54,0.25,0.21,0.9,1,1,1,0.84],
        '3' => [0.61,0.96,0.97,0.75,0.19,0.29,0.29,0.39,0.94,0.49,0.06,0.37,0.54,0.83,0.27,0.12,0.68,0.84,0.86,0.37,0,0,0.08,0.7,0.8,0.33,0.27,0.34,0.83,0.69,0.77,0.98,0.93,0.7,0.21],
        '4' => [0,0.09,0.85,0.8,0,0,0.43,0.89,0.8,0,0.13,0.76,0.48,0.8,0,0.6,0.58,0.25,0.8,0,0.96,0.73,0.7,0.92,0.53,0.5,0.5,0.63,0.9,0.45,0,0,0.26,0.8,0],
        '5' => [0.94,1,1,1,0.62,0.94,0.51,0.25,0.25,0.16,0.94,0.6,0.37,0.22,0.03,0.7,0.76,0.87,0.96,0.59,0,0.01,0.1,0.76,0.98,0.34,0.26,0.4,0.88,0.81,0.9,0.98,0.9,0.63,0.19],
        '6' => [0.12,0.58,0.89,0.99,0.5,0.6,0.88,0.4,0.26,0.12,0.92,0.66,0.37,0.31,0.08,1,0.93,0.79,0.95,0.67,0.99,0.47,0.03,0.54,0.95,0.82,0.74,0.31,0.73,0.83,0.33,0.84,0.97,0.81,0.32],
        '7' => [1,1,1,1,0.83,0.25,0.25,0.32,0.9,0.66,0,0,0.37,0.93,0.26,0,0.01,0.7,0.6,0.01,0,0.29,0.87,0.2,0,0.04,0.7,0.63,0,0,0.3,0.96,0.35,0,0],
        '8' => [0.41,0.87,0.98,0.84,0.35,0.86,0.65,0.29,0.75,0.71,0.72,0.76,0.41,0.81,0.46,0.29,0.93,0.98,0.85,0.2,0.82,0.62,0.27,0.77,0.73,0.97,0.59,0.29,0.71,0.8,0.54,0.91,0.98,0.82,0.34],
        '9' => [0.27,0.82,0.98,0.8,0.2,0.77,0.67,0.29,0.76,0.62,0.89,0.48,0.02,0.53,0.82,0.62,0.93,0.78,0.93,0.85,0.07,0.32,0.38,0.69,0.73,0.14,0.26,0.41,0.86,0.41,0.57,0.98,0.89,0.53,0.04],
    ];

    /**
     * Template 5x7 Normalized Grid & Rasio Lebar untuk Huruf Kapital Arial Bold ('A'..'Z')
     */
    public const LETTER_TEMPLATES = [
        'A' => ['ratio' => 1, 'grid' => [0,0.24,0.95,0.12,0,0,0.46,0.87,0.35,0,0,0.72,0.56,0.62,0,0.14,0.85,0.25,0.86,0.07,0.4,0.92,0.63,0.97,0.31,0.65,0.63,0.4,0.7,0.57,0.87,0.23,0,0.33,0.8]],
        'B' => ['ratio' => 0.815, 'grid' => [1,1,0.99,0.86,0.27,1,0.37,0.22,0.71,0.65,1,0.37,0.22,0.72,0.55,1,0.84,0.82,0.93,0.42,1,0.21,0.02,0.47,0.9,1,0.37,0.21,0.58,0.87,1,1,0.99,0.91,0.42]],
        'C' => ['ratio' => 0.852, 'grid' => [0.11,0.71,0.97,0.83,0.26,0.62,0.68,0.24,0.58,0.69,0.94,0.29,0,0.05,0.09,1,0.22,0,0,0,0.95,0.29,0,0.17,0.36,0.63,0.68,0.24,0.65,0.74,0.12,0.72,0.98,0.81,0.21]],
        'D' => ['ratio' => 0.852, 'grid' => [0.87,1,0.99,0.77,0.14,0.87,0.47,0.24,0.7,0.61,0.87,0.34,0,0.34,0.89,0.87,0.34,0,0.28,0.96,0.87,0.34,0,0.33,0.89,0.87,0.47,0.22,0.67,0.62,0.87,1,0.99,0.76,0.14]],
        'E' => ['ratio' => 0.778, 'grid' => [0.86,1,1,1,0.87,0.86,0.53,0.2,0.2,0.17,0.86,0.65,0.4,0.4,0.27,0.86,0.76,0.6,0.6,0.4,0.86,0.41,0,0,0,0.86,0.53,0.2,0.2,0.19,0.86,1,1,1,0.97]],
        'F' => ['ratio' => 0.667, 'grid' => [1,1,1,1,0.97,1,0.53,0.2,0.2,0.19,1,0.41,0,0,0,1,0.88,0.8,0.8,0.42,1,0.53,0.2,0.2,0.11,1,0.41,0,0,0,1,0.41,0,0,0]],
        'G' => ['ratio' => 0.926, 'grid' => [0.16,0.78,0.98,0.79,0.2,0.66,0.6,0.24,0.66,0.6,0.9,0.14,0,0.06,0.07,0.89,0.06,0.4,0.8,0.73,0.9,0.16,0.1,0.45,0.91,0.65,0.62,0.25,0.64,0.9,0.15,0.76,0.98,0.83,0.4]],
        'H' => ['ratio' => 0.778, 'grid' => [1,0.21,0,0.29,0.98,1,0.21,0,0.29,0.98,1,0.37,0.2,0.43,0.98,1,0.84,0.8,0.86,0.98,1,0.21,0,0.29,0.98,1,0.21,0,0.29,0.98,1,0.21,0,0.29,0.98]],
        'I' => ['ratio' => 0.222, 'grid' => [0.73,1,1,1,0.94,0.73,1,1,1,0.94,0.73,1,1,1,0.94,0.73,1,1,1,0.94,0.73,1,1,1,0.94,0.73,1,1,1,0.94,0.73,1,1,1,0.94]],
        'J' => ['ratio' => 0.667, 'grid' => [0,0,0,0.55,0.89,0,0,0,0.55,0.89,0,0,0,0.55,0.89,0,0,0,0.55,0.89,0.22,0.19,0,0.55,0.88,0.74,0.74,0.24,0.73,0.74,0.28,0.84,0.98,0.81,0.25]],
        'K' => ['ratio' => 0.889, 'grid' => [1,0.18,0.18,0.91,0.44,1,0.24,0.71,0.64,0.03,1,0.7,0.87,0.1,0,1,0.94,0.9,0.19,0,1,0.43,0.63,0.6,0,1,0.18,0.24,0.92,0.24,1,0.18,0.01,0.74,0.67]],
        'L' => ['ratio' => 0.704, 'grid' => [1,0.44,0,0,0,1,0.44,0,0,0,1,0.44,0,0,0,1,0.44,0,0,0,1,0.44,0,0,0,1,0.55,0.2,0.2,0.17,1,1,1,1,0.87]],
        'M' => ['ratio' => 0.963, 'grid' => [0.9,0.69,0,0.55,1,0.88,0.82,0.03,0.7,0.98,0.83,0.83,0.24,0.76,0.85,0.83,0.68,0.57,0.71,0.79,0.83,0.52,0.9,0.55,0.79,0.83,0.35,1,0.39,0.79,0.83,0.21,0.94,0.24,0.79]],
        'N' => ['ratio' => 0.778, 'grid' => [1,0.4,0,0.24,0.95,0.99,0.77,0.06,0.24,0.95,0.95,0.73,0.41,0.24,0.95,0.94,0.32,0.74,0.33,0.95,0.94,0.14,0.54,0.7,0.95,0.94,0.14,0.13,0.86,0.95,0.94,0.14,0,0.54,0.95]],
        'O' => ['ratio' => 0.963, 'grid' => [0.1,0.72,0.97,0.79,0.18,0.54,0.7,0.25,0.59,0.68,0.82,0.29,0,0.14,0.9,0.89,0.2,0,0.05,0.88,0.82,0.29,0,0.14,0.91,0.54,0.72,0.25,0.6,0.69,0.1,0.71,0.97,0.8,0.18]],
        'P' => ['ratio' => 0.778, 'grid' => [0.86,1,0.99,0.92,0.43,0.86,0.53,0.22,0.6,0.89,0.86,0.41,0,0.38,0.96,0.86,0.76,0.62,0.86,0.65,0.86,0.65,0.39,0.32,0.06,0.86,0.41,0,0,0,0.86,0.41,0,0,0]],
        'Q' => ['ratio' => 0.931, 'grid' => [0.17,0.75,0.83,0.75,0.17,0.63,0.57,0.06,0.59,0.62,0.84,0.24,0,0.26,0.83,0.88,0.2,0,0.22,0.86,0.77,0.35,0.28,0.48,0.73,0.42,0.81,0.72,0.98,0.57,0.02,0.37,0.57,0.51,0.74]],
        'R' => ['ratio' => 0.852, 'grid' => [1,1,0.99,0.85,0.21,1,0.34,0.21,0.71,0.59,1,0.18,0.02,0.63,0.61,1,0.84,0.82,0.78,0.21,1,0.37,0.7,0.66,0.03,1,0.18,0.14,0.87,0.4,1,0.18,0,0.53,0.84]],
        'S' => ['ratio' => 0.815, 'grid' => [0.22,0.8,0.97,0.81,0.24,0.65,0.69,0.24,0.71,0.7,0.64,0.84,0.42,0.24,0.16,0.16,0.65,0.93,0.93,0.47,0.26,0.14,0.15,0.59,0.92,0.77,0.68,0.24,0.58,0.85,0.27,0.82,0.98,0.86,0.34]],
        'T' => ['ratio' => 0.778, 'grid' => [1,1,1,1,0.97,0.2,0.41,1,0.36,0.19,0,0.27,1,0.2,0,0,0.27,1,0.2,0,0,0.27,1,0.2,0,0,0.27,1,0.2,0,0,0.27,1,0.2,0]],
        'U' => ['ratio' => 0.815, 'grid' => [0.87,0.4,0,0.32,0.96,0.87,0.4,0,0.32,0.96,0.87,0.4,0,0.32,0.96,0.87,0.4,0,0.32,0.95,0.84,0.42,0,0.33,0.93,0.69,0.72,0.24,0.63,0.79,0.23,0.81,0.98,0.86,0.31]],
        'V' => ['ratio' => 0.926, 'grid' => [0.88,0.24,0,0.45,0.79,0.68,0.44,0,0.64,0.54,0.45,0.66,0.04,0.84,0.26,0.21,0.87,0.3,0.81,0.03,0.03,0.81,0.73,0.58,0,0,0.58,0.99,0.35,0,0,0.38,0.96,0.14,0]],
        'W' => ['ratio' => 1.296, 'grid' => [0.68,0.14,0.9,0.05,0.76,0.67,0.26,0.98,0.23,0.75,0.66,0.46,0.82,0.46,0.66,0.59,0.7,0.56,0.69,0.52,0.47,0.88,0.3,0.91,0.39,0.36,0.83,0.09,0.95,0.25,0.25,0.72,0,0.82,0.13]],
        'X' => ['ratio' => 0.889, 'grid' => [0.57,0.73,0.02,0.62,0.7,0.15,0.87,0.45,0.88,0.26,0,0.51,0.97,0.61,0,0,0.28,1,0.39,0,0.01,0.63,0.9,0.74,0.04,0.28,0.89,0.27,0.86,0.41,0.74,0.61,0,0.52,0.85]],
        'Y' => ['ratio' => 0.926, 'grid' => [0.8,0.4,0,0.6,0.69,0.44,0.73,0.14,0.86,0.26,0.08,0.83,0.73,0.64,0,0,0.45,0.96,0.22,0,0,0.23,0.84,0,0,0,0.23,0.83,0,0,0,0.23,0.83,0,0]],
        'Z' => ['ratio' => 0.815, 'grid' => [0.61,1,1,1,0.87,0.12,0.2,0.37,0.96,0.63,0,0.02,0.66,0.8,0.11,0,0.41,0.91,0.24,0,0.24,0.92,0.51,0,0,0.78,0.88,0.28,0.2,0.2,0.92,1,1,1,0.98]],
    ];

    /**
     * Identifikasi siswa melalui 3 jalur cadangan otomatis:
     * 1. Barcode 1D (Code 128B)
     * 2. OCR Teks "ID: UJN-...-XXXX"
     * 3. OCR Teks "Nama Siswa : ..."
     */
    public static function identifyStudent($im, array $markers, Ujian $ujian, ?iterable $candidateSiswas = null): array
    {
        if ($candidateSiswas === null) {
            $candidateSiswas = self::getCandidateSiswas($ujian);
        }

        // 1. Coba Decode Barcode 1D (Code 128B) dari area kotak kanan atas
        $barcodeText = self::decodeBarcode128FromRegion($im, $markers);
        if (!empty($barcodeText)) {
            if (preg_match('/-(\d{1,6})$/', trim($barcodeText), $m)) {
                $sid = (int) $m[1];
                if ($sid > 0 && Siswa::where('id', $sid)->exists()) {
                    return [
                        'siswa_id' => $sid,
                        'ujian_id' => $ujian->id,
                        'method'   => 'barcode_1d',
                    ];
                }
            }
        }

        // 2. Coba OCR pada baris "ID: UJN-...-0192"
        $idResult = self::recognizeIdCaption($im, $markers, $ujian, $candidateSiswas);

        // 3. Coba OCR pada baris "Nama Siswa : NOVA ABDUL MUTTHALIB"
        $nameResult = self::recognizeNamaSiswa($im, $markers, $candidateSiswas);

        // Evaluasi gabungan hasil OCR ID dan OCR Nama Siswa
        if (!empty($idResult['siswa_id']) && !empty($nameResult['siswa_id'])) {
            if ($idResult['siswa_id'] === $nameResult['siswa_id']) {
                return [
                    'siswa_id' => $idResult['siswa_id'],
                    'ujian_id' => $ujian->id,
                    'method'   => 'ocr_id_and_nama',
                ];
            }
            // Pilih yang memiliki skor kepercayaan lebih tinggi
            if (($idResult['confidence'] ?? 0) >= ($nameResult['confidence'] ?? 0)) {
                return [
                    'siswa_id' => $idResult['siswa_id'],
                    'ujian_id' => $ujian->id,
                    'method'   => 'ocr_id',
                ];
            }
            return [
                'siswa_id' => $nameResult['siswa_id'],
                'ujian_id' => $ujian->id,
                'method'   => 'ocr_nama',
            ];
        }

        if (!empty($idResult['siswa_id']) && ($idResult['confidence'] ?? 0) >= 0.85) {
            return [
                'siswa_id' => $idResult['siswa_id'],
                'ujian_id' => $ujian->id,
                'method'   => 'ocr_id',
            ];
        }

        if (!empty($nameResult['siswa_id']) && ($nameResult['confidence'] ?? 0) >= 0.82) {
            return [
                'siswa_id' => $nameResult['siswa_id'],
                'ujian_id' => $ujian->id,
                'method'   => 'ocr_nama',
            ];
        }

        return [];
    }

    /**
     * Ambil daftar kandidat siswa untuk kelas ujian
     */
    public static function getCandidateSiswas(Ujian $ujian)
    {
        if ($ujian->kelas_id && $ujian->kelas) {
            $namaKelas = $ujian->kelas->nama_kelas;
            $list = Siswa::where(function ($q) use ($namaKelas) {
                $q->where('kelas', $namaKelas)
                  ->orWhereHas('pendaftaran', fn($sq) => $sq->where('kelas', $namaKelas));
            })->get(['id', 'nama_lengkap', 'kelas']);

            if ($list->isNotEmpty()) {
                return $list;
            }
        }

        return Siswa::take(250)->get(['id', 'nama_lengkap', 'kelas']);
    }

    /**
     * Decode Barcode 1D (Code 128B) dari area kotak identitas pojok kanan atas
     */
    public static function decodeBarcode128FromRegion($im, array $markers): ?string
    {
        $p1 = self::mapPoint(0.65, -0.01, $markers);
        $p2 = self::mapPoint(0.96, 0.13, $markers);

        $imgW = imagesx($im);
        $imgH = imagesy($im);
        $x1 = max(0, min($imgW - 1, min($p1['x'], $p2['x'])));
        $x2 = max(0, min($imgW - 1, max($p1['x'], $p2['x'])));
        $y1 = max(0, min($imgH - 1, min($p1['y'], $p2['y'])));
        $y2 = max(0, min($imgH - 1, max($p1['y'], $p2['y'])));

        if (($x2 - $x1) < 60 || ($y2 - $y1) < 15) {
            return null;
        }

        $patMap = array_flip(self::CODE128_PATTERNS);
        $stepY = max(2, (int) floor(($y2 - $y1) / 14));

        for ($y = $y1 + 4; $y <= $y2 - 4; $y += $stepY) {
            $lums = [];
            for ($x = $x1; $x <= $x2; $x++) {
                $rgb = imagecolorat($im, $x, $y);
                $lums[] = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
            }

            $sorted = $lums;
            sort($sorted);
            $bg = $sorted[(int) (count($sorted) * 0.85)] ?? 220;
            $fg = $sorted[(int) (count($sorted) * 0.10)] ?? 40;
            if (($bg - $fg) < 40) {
                continue;
            }

            foreach ([0.50, 0.58, 0.42] as $ratio) {
                $thresh = $fg + ($bg - $fg) * $ratio;
                $binRow = [];
                foreach ($lums as $l) {
                    $binRow[] = ($l < $thresh) ? 1 : 0;
                }
                $decoded = self::decodeCode128Scanline($binRow, $patMap);
                if ($decoded !== null && strlen($decoded) >= 4) {
                    return $decoded;
                }
            }
        }

        return null;
    }

    /**
     * Decode 1 baris scanline biner Code 128B
     */
    public static function decodeCode128Scanline(array $binaryRow, ?array $patMap = null): ?string
    {
        if ($patMap === null) {
            $patMap = array_flip(self::CODE128_PATTERNS);
        }

        $runs = [];
        $n = count($binaryRow);
        $i = 0;
        while ($i < $n) {
            $b = $binaryRow[$i];
            $start = $i;
            while ($i < $n && $binaryRow[$i] === $b) {
                $i++;
            }
            $runs[] = ['b' => $b, 'w' => $i - $start];
        }

        $rc = count($runs);
        for ($s = 0; $s <= $rc - 19; $s++) {
            if ($runs[$s]['b'] !== 1) {
                continue;
            }
            $pStr = self::normalizeRuns(array_slice($runs, $s, 6), 11);
            if ($pStr !== '211214') { // Start Code B = 104
                continue;
            }

            $codes = [];
            $pos = $s + 6;
            while ($pos + 6 <= $rc) {
                $symPat = self::normalizeRuns(array_slice($runs, $pos, 6), 11);
                if (!isset($patMap[$symPat])) {
                    break;
                }
                $code = $patMap[$symPat];
                if ($code === 106) {
                    break;
                }
                $codes[] = $code;
                $pos += 6;

                if ($pos + 7 <= $rc) {
                    $stopPat = self::normalizeRuns(array_slice($runs, $pos, 7), 13);
                    if ($stopPat === '2331112' && count($codes) >= 2) {
                        $expectedCheck = array_pop($codes);
                        $calcCheck = 104;
                        $str = '';
                        foreach ($codes as $idx => $cVal) {
                            $calcCheck += $cVal * ($idx + 1);
                            if ($cVal >= 0 && $cVal <= 94) {
                                $str .= chr($cVal + 32);
                            }
                        }
                        if (($calcCheck % 103) === $expectedCheck) {
                            return $str;
                        }
                    }
                }
            }
        }

        return null;
    }

    private static function normalizeRuns(array $slice, int $modules): string
    {
        $total = 0;
        foreach ($slice as $r) {
            $total += $r['w'];
        }
        if ($total <= 0) {
            return '';
        }
        $vals = [];
        foreach ($slice as $r) {
            $vals[] = max(1, min(4, (int) round(($r['w'] * (float) $modules) / $total)));
        }
        return implode('', $vals);
    }

    /**
     * OCR Baris Caption "ID: UJN-...-0192" di bawah QR Code / Barcode
     */
    public static function recognizeIdCaption($im, array $markers, Ujian $ujian, iterable $candidateSiswas): array
    {
        $p1 = self::mapPoint(0.68, 0.075, $markers);
        $p2 = self::mapPoint(0.94, 0.145, $markers);

        $imgW = imagesx($im);
        $imgH = imagesy($im);
        $rx1 = max(0, min($imgW - 1, min($p1['x'], $p2['x'])));
        $rx2 = max(0, min($imgW - 1, max($p1['x'], $p2['x'])));
        $ry1 = max(0, min($imgH - 1, min($p1['y'], $p2['y'])));
        $ry2 = max(0, min($imgH - 1, max($p1['y'], $p2['y'])));

        $w = $rx2 - $rx1 + 1;
        $h = $ry2 - $ry1 + 1;
        if ($w < 60 || $h < 12) {
            return [];
        }

        $lum = [];
        $allLums = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($im, $rx1 + $x, $ry1 + $y);
                $l = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
                $lum[$y][$x] = $l;
                $allLums[] = $l;
            }
        }
        sort($allLums);
        $bgLum = $allLums[(int) (count($allLums) * 0.85)] ?? 240.0;
        $thresh = $bgLum * 0.72;

        // Cari pita baris teks yang memiliki banyak transisi horizontal (ciri khas barisan huruf ID: ...)
        $rowDark = [];
        $rowTrans = [];
        for ($y = 0; $y < $h; $y++) {
            $d = 0;
            $t = 0;
            $prev = false;
            for ($x = 6; $x < $w - 6; $x++) {
                $isDark = ($lum[$y][$x] < $thresh);
                if ($isDark) {
                    $d++;
                }
                if ($isDark !== $prev) {
                    $t++;
                    $prev = $isDark;
                }
            }
            $rowDark[$y] = $d;
            $rowTrans[$y] = $t;
        }

        $bestBand = null;
        $inBand = false;
        $bStart = 0;
        for ($y = 0; $y < $h; $y++) {
            $isTextLine = ($rowDark[$y] >= 3 && $rowDark[$y] < ($w * 0.60) && $rowTrans[$y] >= 12);
            if ($isTextLine) {
                if (!$inBand) {
                    $inBand = true;
                    $bStart = $y;
                }
            } else {
                if ($inBand) {
                    $inBand = false;
                    $bEnd = $y - 1;
                    $bh = $bEnd - $bStart + 1;
                    if ($bh >= 5 && $bh <= 28) {
                        $sumT = 0;
                        for ($ry = $bStart; $ry <= $bEnd; $ry++) {
                            $sumT += $rowTrans[$ry];
                        }
                        $avgT = $sumT / $bh;
                        if ($bestBand === null || $avgT > $bestBand['avgTrans']) {
                            $bestBand = ['y1' => max(0, $bStart - 1), 'y2' => min($h - 1, $bEnd + 1), 'h' => $bh, 'avgTrans' => $avgT];
                        }
                    }
                }
            }
        }

        if (!$bestBand) {
            return [];
        }

        $y1 = $bestBand['y1'];
        $y2 = $bestBand['y2'];
        $bandH = max(1, $y2 - $y1 + 1);

        // Segmentasi karakter secara horizontal
        $colDark = [];
        for ($x = 0; $x < $w; $x++) {
            $d = 0;
            for ($y = $y1; $y <= $y2; $y++) {
                if ($lum[$y][$x] < $thresh) {
                    $d++;
                }
            }
            $colDark[$x] = $d;
        }

        $chars = [];
        $inChar = false;
        $cStart = 0;
        for ($x = 2; $x < $w - 2; $x++) {
            if ($colDark[$x] > 0) {
                if (!$inChar) {
                    $inChar = true;
                    $cStart = $x;
                }
            } else {
                if ($inChar) {
                    $inChar = false;
                    $cEnd = $x - 1;
                    $cw = $cEnd - $cStart + 1;
                    // Abaikan garis bingkai vertikal (yang menerus ke atas/bawah di luar pita teks)
                    $borderTouch = 0;
                    if ($y1 > 2 && ($lum[$y1 - 2][$cStart] ?? 255) < $thresh) {
                        $borderTouch++;
                    }
                    if ($y2 < $h - 3 && ($lum[$y2 + 2][$cStart] ?? 255) < $thresh) {
                        $borderTouch++;
                    }
                    if ($borderTouch >= 2 && $cw <= 4) {
                        continue;
                    }

                    if ($cw >= 2 && $cw <= 22) {
                        $cy1 = $y2;
                        $cy2 = $y1;
                        for ($cy = $y1; $cy <= $y2; $cy++) {
                            for ($cx = $cStart; $cx <= $cEnd; $cx++) {
                                if ($lum[$cy][$cx] < $thresh) {
                                    if ($cy < $cy1) {
                                        $cy1 = $cy;
                                    }
                                    if ($cy > $cy2) {
                                        $cy2 = $cy;
                                    }
                                }
                            }
                        }
                        $ch = max(1, $cy2 - $cy1 + 1);
                        $chars[] = [
                            'x1'   => $cStart,
                            'x2'   => $cEnd,
                            'y1'   => $cy1,
                            'y2'   => $cy2,
                            'w'    => $cw,
                            'h'    => $ch,
                            'grid' => self::extractRegion5x7($lum, $bgLum, $cStart, $cEnd, $cy1, $cy2),
                        ];
                    }
                }
            }
        }

        if (count($chars) < 5) {
            return [];
        }

        // Temukan tanda hubung '-' terakhir (karakter pendek di tengah baris)
        $dashIndices = [];
        foreach ($chars as $idx => $c) {
            if ($c['h'] <= max(4, (int) round($bandH * 0.42)) && $c['w'] >= 2) {
                $dashIndices[] = $idx;
            }
        }

        $idDigitChars = [];
        if (!empty($dashIndices)) {
            $lastDash = end($dashIndices);
            $afterDash = array_slice($chars, $lastDash + 1);
            if (count($afterDash) >= 4) {
                $idDigitChars = array_slice($afterDash, 0, 4);
            }
        }
        if (count($idDigitChars) !== 4) {
            $idDigitChars = array_slice($chars, -4);
        }

        // Klasifikasi 4 digit ID siswa
        $digitScoresPerPos = [];
        $recognizedStr = '';
        $totalBestSim = 0.0;

        foreach ($idDigitChars as $pos => $ch) {
            $bestD = '0';
            $bestSim = -1.0;
            $posMap = [];
            foreach (self::DIGIT_TEMPLATES as $d => $tmpl) {
                $sim = self::cosineSimilarity($ch['grid'], $tmpl);
                $posMap[(string) $d] = $sim;
                if ($sim > $bestSim) {
                    $bestSim = $sim;
                    $bestD = (string) $d;
                }
            }
            $digitScoresPerPos[$pos] = $posMap;
            $recognizedStr .= $bestD;
            $totalBestSim += $bestSim;
        }

        $avgSim = $totalBestSim / 4.0;
        $directId = (int) $recognizedStr;

        // Cek apakah directId langsung cocok dengan salah satu kandidat siswa atau siswa di DB
        $candidateMap = [];
        foreach ($candidateSiswas as $s) {
            $candidateMap[(int) $s->id] = $s;
        }

        if ($directId > 0 && (isset($candidateMap[$directId]) || Siswa::where('id', $directId)->exists())) {
            return [
                'siswa_id'   => $directId,
                'confidence' => round($avgSim, 4),
                'raw_digits' => $recognizedStr,
            ];
        }

        // Jika ada 1 digit yang mirip, skor terhadap semua kandidat siswa di kelas
        $bestCandId = null;
        $bestCandScore = -1.0;
        foreach ($candidateMap as $sid => $sObj) {
            $padded = str_pad((string) $sid, 4, '0', STR_PAD_LEFT);
            if (strlen($padded) !== 4) {
                continue;
            }
            $score = 0.0;
            for ($p = 0; $p < 4; $p++) {
                $dChar = $padded[$p];
                $score += $digitScoresPerPos[$p][$dChar] ?? 0.0;
            }
            $score /= 4.0;
            if ($score > $bestCandScore) {
                $bestCandScore = $score;
                $bestCandId = $sid;
            }
        }

        if ($bestCandId !== null && $bestCandScore >= 0.86) {
            return [
                'siswa_id'   => $bestCandId,
                'confidence' => round($bestCandScore, 4),
                'raw_digits' => str_pad((string) $bestCandId, 4, '0', STR_PAD_LEFT),
            ];
        }

        return [];
    }

    /**
     * OCR Baris "Nama Siswa : NOVA ABDUL MUTTHALIB" pada Header LJK
     */
    public static function recognizeNamaSiswa($im, array $markers, iterable $candidateSiswas): array
    {
        $p1 = self::mapPoint(0.39, 0.028, $markers);
        $p2 = self::mapPoint(0.73, 0.062, $markers);

        $imgW = imagesx($im);
        $imgH = imagesy($im);
        $rx1 = max(0, min($imgW - 1, min($p1['x'], $p2['x'])));
        $rx2 = max(0, min($imgW - 1, max($p1['x'], $p2['x'])));
        $ry1 = max(0, min($imgH - 1, min($p1['y'], $p2['y'])));
        $ry2 = max(0, min($imgH - 1, max($p1['y'], $p2['y'])));

        $w = $rx2 - $rx1 + 1;
        $h = $ry2 - $ry1 + 1;
        if ($w < 80 || $h < 12) {
            return [];
        }

        $lum = [];
        $allLums = [];
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgb = imagecolorat($im, $rx1 + $x, $ry1 + $y);
                $l = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
                $lum[$y][$x] = $l;
                $allLums[] = $l;
            }
        }
        sort($allLums);
        $bgLum = $allLums[(int) (count($allLums) * 0.85)] ?? 240.0;
        $thresh = $bgLum * 0.68;

        // Cari pita baris utama teks nama siswa (abaikan ekor huruf dari baris di atas/bawahnya)
        $bestNameBand = null;
        $inNB = false;
        $nbStart = 0;
        $rowDarks = [];
        for ($y = 0; $y < $h; $y++) {
            $d = 0;
            for ($x = 0; $x < $w; $x++) {
                if ($lum[$y][$x] < $thresh) {
                    $d++;
                }
            }
            $rowDarks[$y] = $d;
            if ($d >= 5) {
                if (!$inNB) {
                    $inNB = true;
                    $nbStart = $y;
                }
            } else {
                if ($inNB) {
                    $inNB = false;
                    $nbEnd = $y - 1;
                    $nbH = $nbEnd - $nbStart + 1;
                    if ($nbH >= 6 && $nbH <= 28) {
                        $sumD = array_sum(array_slice($rowDarks, $nbStart, $nbH));
                        if ($bestNameBand === null || $sumD > $bestNameBand['sumD']) {
                            $bestNameBand = ['y1' => $nbStart, 'y2' => $nbEnd, 'sumD' => $sumD];
                        }
                    }
                }
            }
        }
        if ($inNB) {
            $nbEnd = $h - 1;
            $nbH = $nbEnd - $nbStart + 1;
            if ($nbH >= 6 && $nbH <= 28) {
                $sumD = array_sum(array_slice($rowDarks, $nbStart, $nbH));
                if ($bestNameBand === null || $sumD > $bestNameBand['sumD']) {
                    $bestNameBand = ['y1' => $nbStart, 'y2' => $nbEnd, 'sumD' => $sumD];
                }
            }
        }

        if (!$bestNameBand) {
            return [];
        }

        $y1 = $bestNameBand['y1'];
        $y2 = $bestNameBand['y2'];

        // Segmentasi gumpalan karakter secara horizontal
        $colDark = [];
        for ($x = 0; $x < $w; $x++) {
            $d = 0;
            for ($y = $y1; $y <= $y2; $y++) {
                if ($lum[$y][$x] < $thresh) {
                    $d++;
                }
            }
            $colDark[$x] = $d;
        }

        $blobs = [];
        $inB = false;
        $bS = 0;
        for ($x = 0; $x < $w; $x++) {
            if ($colDark[$x] > 0) {
                if (!$inB) {
                    $inB = true;
                    $bS = $x;
                }
            } else {
                if ($inB) {
                    $inB = false;
                    $blobs[] = ['x1' => $bS, 'x2' => $x - 1, 'w' => $x - $bS];
                }
            }
        }

        if (empty($blobs)) {
            return [];
        }

        // Lewati titik dua ':' di sebelah kiri jika ada
        $startIdx = 0;
        foreach ($blobs as $i => $b) {
            if ($b['w'] <= 5 && $b['x1'] < ($w * 0.22)) {
                $startIdx = $i + 1;
            }
        }

        $nameBlobs = array_slice($blobs, $startIdx);
        if (empty($nameBlobs)) {
            return [];
        }

        // Kelompokkan gumpalan karakter menjadi kata-kata (spasi antar kata >= 4px)
        $lineH = max(8, $y2 - $y1 + 1);
        $wordGapThresh = max(4, (int) round($lineH * 0.30));

        $words = [];
        $currWord = [];
        foreach ($nameBlobs as $b) {
            if (!empty($currWord)) {
                $prev = end($currWord);
                $gap = $b['x1'] - $prev['x2'] - 1;
                if ($gap >= $wordGapThresh) {
                    $words[] = $currWord;
                    $currWord = [];
                }
            }
            $currWord[] = $b;
        }
        if (!empty($currWord)) {
            $words[] = $currWord;
        }

        if (empty($words)) {
            return [];
        }

        // Hitung skor kecocokan untuk setiap siswa kandidat di kelas
        $bestSiswa = null;
        $bestScore = -1.0;
        $secondScore = -1.0;

        foreach ($candidateSiswas as $siswa) {
            $score = self::scoreCandidateName($siswa->nama_lengkap, $words, $lum, $bgLum, $y1, $y2);
            if ($score > $bestScore) {
                $secondScore = $bestScore;
                $bestScore = $score;
                $bestSiswa = $siswa;
            } elseif ($score > $secondScore) {
                $secondScore = $score;
            }
        }

        if ($bestSiswa && $bestScore >= 0.80 && ($bestScore - $secondScore) >= 0.04) {
            return [
                'siswa_id'   => (int) $bestSiswa->id,
                'nama_siswa' => $bestSiswa->nama_lengkap,
                'confidence' => round($bestScore, 4),
            ];
        }

        return [];
    }

    /**
     * Hitung skor kemiripan visual sebuah nama siswa terhadap barisan kata pada gambar
     */
    private static function scoreCandidateName(string $name, array $words, array $lum, float $bgLum, int $y1, int $y2): float
    {
        $clean = preg_replace('/[^A-Z ]/', '', strtoupper(trim($name)));
        $candWords = array_values(array_filter(explode(' ', $clean)));
        if (empty($candWords) || empty($words)) {
            return 0.0;
        }

        $wordCountDiff = abs(count($candWords) - count($words));
        $wordCountPenalty = max(0.35, 1.0 - 0.25 * $wordCountDiff);
        $compareCount = min(count($candWords), count($words));

        $totalLetterSim = 0.0;
        $totalLetters = 0;
        $expectedWordWeights = [];
        $observedWordWidths = [];

        for ($wi = 0; $wi < $compareCount; $wi++) {
            $cwStr = $candWords[$wi];
            $wBlobs = $words[$wi];
            $wx1 = $wBlobs[0]['x1'];
            $wx2 = end($wBlobs)['x2'];
            $wordWidthPx = max(1, $wx2 - $wx1 + 1);
            $observedWordWidths[] = $wordWidthPx;

            $letters = str_split($cwStr);
            $numL = count($letters);
            $weights = [];
            $gapWeight = 0.12;
            foreach ($letters as $L) {
                $weights[] = self::LETTER_TEMPLATES[$L]['ratio'] ?? 0.85;
            }
            $totalWeight = array_sum($weights) + max(0, $numL - 1) * $gapWeight;
            $expectedWordWeights[] = $totalWeight;

            $cursor = 0.0;
            foreach ($letters as $li => $L) {
                $lw = $weights[$li];
                $lx1 = (int) round($wx1 + ($cursor / $totalWeight) * $wordWidthPx);
                $lx2 = (int) round($wx1 + (($cursor + $lw) / $totalWeight) * $wordWidthPx);
                $lx2 = max($lx1, min($wx2, $lx2));

                if (isset(self::LETTER_TEMPLATES[$L])) {
                    $obsGrid = self::extractRegion5x7($lum, $bgLum, $lx1, $lx2, $y1, $y2);
                    $sim = self::cosineSimilarity($obsGrid, self::LETTER_TEMPLATES[$L]['grid']);
                    $totalLetterSim += $sim;
                    $totalLetters++;
                }

                $cursor += $lw + $gapWeight;
            }
        }

        $avgLetterSim = $totalLetters > 0 ? ($totalLetterSim / $totalLetters) : 0.0;

        // Korelasi proporsi panjang kata
        $widthRatioSim = 1.0;
        if (count($expectedWordWeights) >= 2) {
            $widthRatioSim = self::cosineSimilarity($expectedWordWeights, $observedWordWidths);
        }

        return round($avgLetterSim * $wordCountPenalty * (0.35 + 0.65 * $widthRatioSim), 4);
    }

    /**
     * Ekstrak vektor densitas 5x7 dari area piksel
     */
    private static function extractRegion5x7(array $lum, float $bgLum, int $rx1, int $rx2, int $ry1, int $ry2): array
    {
        $cw = max(1, $rx2 - $rx1 + 1);
        $ch = max(1, $ry2 - $ry1 + 1);
        $grid = [];
        $normDiv = max(1.0, $bgLum * 0.6);

        for ($gy = 0; $gy < 7; $gy++) {
            for ($gx = 0; $gx < 5; $gx++) {
                $sx1 = $rx1 + ($gx * $cw) / 5.0;
                $sx2 = $rx1 + (($gx + 1) * $cw) / 5.0;
                $sy1 = $ry1 + ($gy * $ch) / 7.0;
                $sy2 = $ry1 + (($gy + 1) * $ch) / 7.0;
                $sum = 0.0;
                $cnt = 0;
                for ($py = (int) floor($sy1); $py <= min($ry2, (int) floor($sy2)); $py++) {
                    for ($px = (int) floor($sx1); $px <= min($rx2, (int) floor($sx2)); $px++) {
                        $val = $lum[$py][$px] ?? $bgLum;
                        $d = max(0.0, min(1.0, ($bgLum - $val) / $normDiv));
                        $sum += $d;
                        $cnt++;
                    }
                }
                $grid[] = $cnt > 0 ? round($sum / $cnt, 3) : 0.0;
            }
        }

        return $grid;
    }

    /**
     * Hitung Cosine Similarity antara dua vektor
     */
    public static function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        $len = min(count($a), count($b));
        for ($i = 0; $i < $len; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }

        return ($na > 0 && $nb > 0) ? ($dot / sqrt($na * $nb)) : 0.0;
    }

    private static function mapPoint(float $u, float $v, array $m): array
    {
        $tl = $m['TL'];
        $tr = $m['TR'];
        $bl = $m['BL'];
        $br = $m['BR'];

        $x = (1 - $u) * (1 - $v) * $tl['x'] +
             $u * (1 - $v) * $tr['x'] +
             (1 - $u) * $v * $bl['x'] +
             $u * $v * $br['x'];

        $y = (1 - $u) * (1 - $v) * $tl['y'] +
             $u * (1 - $v) * $tr['y'] +
             (1 - $u) * $v * $bl['y'] +
             $u * $v * $br['y'];

        return ['x' => (int) round($x), 'y' => (int) round($y)];
    }
}
