<?php

namespace App\Services\Omr;

use App\Models\Ujian;
use App\Models\Siswa;

/**
 * Service pembuat template dan kalkulator koordinat presisi LJK A4
 */
class LjkTemplateService
{
    // Dimensi Kertas A4 (Milimeter)
    public const PAGE_WIDTH_MM = 210.0;
    public const PAGE_HEIGHT_MM = 297.0;

    // Posisi 4 Fiducial Markers (Kotak Hitam Sudut 10x10 mm pada margin 12mm)
    // Pusat kotak marker berada pada 12mm + 5mm = 17mm dari tepi kertas
    public const MARKER_SIZE_MM = 10.0;
    public const TL_X_MM = 17.0;
    public const TL_Y_MM = 17.0;
    public const TR_X_MM = 193.0;
    public const TR_Y_MM = 17.0;
    public const BL_X_MM = 17.0;
    public const BL_Y_MM = 280.0;
    public const BR_X_MM = 193.0;
    public const BR_Y_MM = 280.0;

    // Jarak aktif antara pusat marker TL dan BR sebagai bidang referensi OMR (0.0 s.d. 1.0)
    public const REF_WIDTH_MM = self::TR_X_MM - self::TL_X_MM;   // 176 mm
    public const REF_HEIGHT_MM = self::BL_Y_MM - self::TL_Y_MM; // 263 mm

    // Posisi nominal tepi bawah header hitam kolom (.omr-col-header) pada cetak-ljk.blade.php
    public const NOMINAL_GRID_START_Y_MM = 86.2;

    // Tinggi tiap baris soal (.omr-row = 3.5px + 17px + 3.5px + 1px = 25px @ 96dpi)
    public const ROW_HEIGHT_MM = 6.6146;

    /**
     * Hitung peta koordinat relatif normalisasi (u, v) untuk setiap bulatan nomor soal
     * Mengikuti tata letak presisi CSS cetak-ljk.blade.php (A4 210x297mm, .ljk-content margin 24mm).
     * u = (x_pos - TL_X_MM) / REF_WIDTH_MM
     * v = (y_pos - TL_Y_MM) / REF_HEIGHT_MM
     */
    public static function getBubbleCoordinates(int $jumlahSoal = 40, int $jumlahOpsi = 4): array
    {
        $coords = [];
        $opsiList = $jumlahOpsi >= 5 ? ['A', 'B', 'C', 'D', 'E'] : ['A', 'B', 'C', 'D'];
        $opsiCount = count($opsiList);

        // Tentukan jumlah kolom grid (4 kolom untuk <= 40 soal, 5 kolom untuk > 40 soal)
        $numCols = $jumlahSoal <= 40 ? 4 : 5;
        $soalPerCol = (int) ceil($jumlahSoal / $numCols);

        // Lebar area konten: 210mm - 24mm (kiri) - 24mm (kanan) = 162mm
        $contentLeft_mm = 24.0;
        $contentWidth_mm = 162.0;
        $colGap_mm = 12.0 / 3.77952756; // 12px = 3.175 mm
        $colWidth_mm = ($contentWidth_mm - (($numCols - 1) * $colGap_mm)) / $numCols;

        // Geometri bulatan pada .omr-row (rata kanan via justify-content: space-between)
        $bubbleDiam_mm = 17.0 / 3.77952756;  // 17px = 4.4979 mm
        $bubbleGap_mm  = 5.0 / 3.77952756;   // 5px  = 1.3229 mm
        $bubbleStep_mm = $bubbleDiam_mm + $bubbleGap_mm; // 5.8208 mm
        $rightPad_mm   = 7.0 / 3.77952756;   // 1px border + 6px padding-right = 1.8521 mm

        $gridStartY_mm = self::NOMINAL_GRID_START_Y_MM;
        $rowHeight_mm  = self::ROW_HEIGHT_MM;

        for ($soal = 1; $soal <= $jumlahSoal; $soal++) {
            $colIdx = (int) floor(($soal - 1) / $soalPerCol);
            $rowIdx = ($soal - 1) % $soalPerCol;

            $colLeftX_mm  = $contentLeft_mm + ($colIdx * ($colWidth_mm + $colGap_mm));
            $colRightX_mm = $colLeftX_mm + $colWidth_mm;
            $lastBubbleX_mm = $colRightX_mm - $rightPad_mm - ($bubbleDiam_mm / 2.0);

            $rowCenterY_mm = $gridStartY_mm + ($rowIdx * $rowHeight_mm) + ($rowHeight_mm / 2.0);

            $coords[$soal] = [];
            foreach ($opsiList as $optIdx => $opsi) {
                $bubbleX_mm = $lastBubbleX_mm - (($opsiCount - 1 - $optIdx) * $bubbleStep_mm);
                $bubbleY_mm = $rowCenterY_mm;

                // Normalisasi terhadap pusat marker TL (17, 17) dan BR (193, 280)
                $u = ($bubbleX_mm - self::TL_X_MM) / self::REF_WIDTH_MM;
                $v = ($bubbleY_mm - self::TL_Y_MM) / self::REF_HEIGHT_MM;

                $coords[$soal][$opsi] = [
                    'u' => round($u, 5),
                    'v' => round($v, 5),
                    'x_mm' => round($bubbleX_mm, 2),
                    'y_mm' => round($bubbleY_mm, 2),
                ];
            }
        }

        return $coords;
    }

    /**
     * Pita horizontal normalisasi (u1, u2) untuk tiap kolom OMR (digunakan untuk mendeteksi bar header hitam .omr-col-header)
     */
    public static function getColumnHeaderBands(int $jumlahSoal = 40): array
    {
        $numCols = $jumlahSoal <= 40 ? 4 : 5;
        $contentLeft_mm = 24.0;
        $contentWidth_mm = 162.0;
        $colGap_mm = 12.0 / 3.77952756;
        $colWidth_mm = ($contentWidth_mm - (($numCols - 1) * $colGap_mm)) / $numCols;

        $bands = [];
        for ($c = 0; $c < $numCols; $c++) {
            $x1 = $contentLeft_mm + ($c * ($colWidth_mm + $colGap_mm)) + 2.0;
            $x2 = $contentLeft_mm + ($c * ($colWidth_mm + $colGap_mm)) + $colWidth_mm - 2.0;
            $bands[] = [
                'u1' => round(($x1 - self::TL_X_MM) / self::REF_WIDTH_MM, 5),
                'u2' => round(($x2 - self::TL_X_MM) / self::REF_WIDTH_MM, 5),
            ];
        }

        return $bands;
    }

    /**
     * Nilai v normalisasi untuk tepi bawah nominal bar header kolom hitam
     */
    public static function getNominalHeaderBottomV(): float
    {
        return round((self::NOMINAL_GRID_START_Y_MM - self::TL_Y_MM) / self::REF_HEIGHT_MM, 5);
    }

    /**
     * Hitung posisi normalisasi area QR Code (Pojok kanan atas)
     */
    public static function getQrCodeArea(): array
    {
        // QR Code terletak di x: 145mm - 182mm, y: 16mm - 53mm
        $x1 = 145.0;
        $y1 = 16.0;
        $x2 = 182.0;
        $y2 = 53.0;

        return [
            'u1' => round(($x1 - self::TL_X_MM) / self::REF_WIDTH_MM, 5),
            'v1' => round(($y1 - self::TL_Y_MM) / self::REF_HEIGHT_MM, 5),
            'u2' => round(($x2 - self::TL_X_MM) / self::REF_WIDTH_MM, 5),
            'v2' => round(($y2 - self::TL_Y_MM) / self::REF_HEIGHT_MM, 5),
        ];
    }

    /**
     * Buat payload unik JSON untuk QR Code LJK Siswa
     */
    public static function makeQrPayload(Ujian $ujian, Siswa $siswa): string
    {
        $checksum = substr(hash('sha256', "MUTTAQIN_{$ujian->id}_{$siswa->id}_{$ujian->kode_ujian}"), 0, 8);
        return json_encode([
            'u' => $ujian->id,
            's' => $siswa->id,
            'k' => $ujian->kode_ujian,
            'c' => $checksum,
        ]);
    }

    /**
     * Buat payload ringkas untuk Barcode 1D (Code 128B) LJK Siswa
     */
    public static function makeBarcodePayload(Ujian $ujian, Siswa $siswa): string
    {
        return 'LJK-' . $ujian->id . '-' . str_pad((string) $siswa->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Hitung tata letak otomatis area jawaban Essay pada LJK A4:
     * - Prioritaskan muat dalam 1 halaman A4 (di bawah grid OMR PG tanpa menggeser koordinat OMR).
     * - Jika ruang halaman 1 tidak mencukupi seluruh nomor essay, otomatis tambahkan halaman belakang lanjutan.
     */
    public static function calculateEssayLayout(int $jumlahPg, int $jumlahEssay): array
    {
        $jumlahPg = max(0, $jumlahPg);
        $jumlahEssay = max(0, $jumlahEssay);

        if ($jumlahEssay === 0) {
            return [
                'has_essay'            => false,
                'page1_numbers'        => [],
                'page1_lines'          => 0,
                'extra_pages'          => [],
                'total_pages_per_siswa' => 1,
            ];
        }

        // Batas bawah aman area konten halaman 1 (di atas .ljk-footer dan marker bawah)
        $safeBottomPage1Mm = 255.0;
        $essayHeaderMm = 8.5;

        if ($jumlahPg > 0) {
            $numCols = $jumlahPg <= 40 ? 4 : 5;
            $rows = (int) ceil($jumlahPg / $numCols);
            $pgBottomMm = self::NOMINAL_GRID_START_Y_MM + ($rows * self::ROW_HEIGHT_MM) + 4.0;
            $minItemHeightMm = 21.5; // 1 baris label nomor + 3 garis tulis @ 5.3mm
        } else {
            // Ujian khusus Essay (tanpa grid bulatan PG)
            $pgBottomMm = 74.0;
            $minItemHeightMm = 28.0; // ruang lebih lega pada halaman 1
        }

        $availPage1Mm = max(0.0, $safeBottomPage1Mm - $pgBottomMm);
        $maxFitPage1 = $availPage1Mm >= ($essayHeaderMm + $minItemHeightMm)
            ? (int) floor(($availPage1Mm - $essayHeaderMm) / $minItemHeightMm)
            : 0;

        $countPage1 = min($jumlahEssay, $maxFitPage1);
        $page1Numbers = $countPage1 > 0 ? range(1, $countPage1) : [];

        $page1Lines = 0;
        if ($countPage1 > 0) {
            $perItemMm = ($availPage1Mm - $essayHeaderMm) / $countPage1;
            $page1Lines = min(5, max(3, (int) floor(($perItemMm - 5.5) / 5.3)));
        }

        $extraPages = [];
        if ($countPage1 < $jumlahEssay) {
            $remaining = range($countPage1 + 1, $jumlahEssay);
            $maxPerBackPage = 6; // maks 6 nomor essay per halaman belakang A4 agar tetap lega ditulis
            $chunks = array_chunk($remaining, $maxPerBackPage);

            foreach ($chunks as $idx => $chunk) {
                $chunkCount = count($chunk);
                $perItemBackMm = 226.0 / max(1, $chunkCount);
                $linesBack = min(8, max(4, (int) floor(($perItemBackMm - 6.5) / 6.0)));

                $extraPages[] = [
                    'page_number' => $idx + 2,
                    'numbers'     => $chunk,
                    'lines'       => $linesBack,
                ];
            }
        }

        return [
            'has_essay'             => true,
            'page1_numbers'         => $page1Numbers,
            'page1_lines'           => $page1Lines,
            'extra_pages'           => $extraPages,
            'total_pages_per_siswa' => 1 + count($extraPages),
        ];
    }
}

