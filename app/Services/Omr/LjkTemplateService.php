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

    // Posisi 4 Fiducial Markers (Kotak Hitam Sudut 10x10 mm)
    public const MARKER_SIZE_MM = 10.0;
    public const TL_X_MM = 12.0;
    public const TL_Y_MM = 12.0;
    public const TR_X_MM = 188.0;
    public const TR_Y_MM = 12.0;
    public const BL_X_MM = 12.0;
    public const BL_Y_MM = 275.0;
    public const BR_X_MM = 188.0;
    public const BR_Y_MM = 275.0;

    // Jarak aktif antara marker TL dan BR sebagai bidang referensi OMR (0.0 s.d. 1.0)
    public const REF_WIDTH_MM = self::TR_X_MM - self::TL_X_MM;   // 176 mm
    public const REF_HEIGHT_MM = self::BL_Y_MM - self::TL_Y_MM; // 263 mm

    /**
     * Hitung peta koordinat relatif normalisasi (u, v) untuk setiap bulatan nomor soal
     * u = (x_pos - TL_X) / REF_WIDTH
     * v = (y_pos - TL_Y) / REF_HEIGHT
     * Rentang u dan v adalah 0.0 s.d 1.0 terhadap kotak acuan 4 marker.
     */
    public static function getBubbleCoordinates(int $jumlahSoal = 40, int $jumlahOpsi = 4): array
    {
        $coords = [];
        $opsiList = $jumlahOpsi >= 5 ? ['A', 'B', 'C', 'D', 'E'] : ['A', 'B', 'C', 'D'];

        // Tentukan jumlah kolom grid (4 kolom untuk <= 40 soal, 5 kolom untuk 50, dst)
        $numCols = $jumlahSoal <= 40 ? 4 : 5;
        $soalPerCol = (int) ceil($jumlahSoal / $numCols);

        // Area OMR Grid di dalam kertas (dalam mm)
        $gridStartY_mm = 88.0; // Setelah header identitas dan petunjuk
        $gridEndY_mm   = 265.0;
        $colWidth_mm   = self::REF_WIDTH_MM / $numCols;
        $rowHeight_mm  = min(8.0, ($gridEndY_mm - $gridStartY_mm) / $soalPerCol);

        $bubbleSpacing_mm = $jumlahOpsi >= 5 ? 5.5 : 6.5;
        $bubbleStartOffset_mm = 11.0; // Jarak dari label nomor soal

        for ($soal = 1; $soal <= $jumlahSoal; $soal++) {
            $colIdx = (int) floor(($soal - 1) / $soalPerCol);
            $rowIdx = ($soal - 1) % $soalPerCol;

            $colOriginX_mm = self::TL_X_MM + ($colIdx * $colWidth_mm) + 2.0;
            $rowOriginY_mm = $gridStartY_mm + ($rowIdx * $rowHeight_mm) + ($rowHeight_mm / 2);

            $coords[$soal] = [];
            foreach ($opsiList as $optIdx => $opsi) {
                $bubbleX_mm = $colOriginX_mm + $bubbleStartOffset_mm + ($optIdx * $bubbleSpacing_mm);
                $bubbleY_mm = $rowOriginY_mm;

                // Normalisasi terhadap marker TL dan BR
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
}

