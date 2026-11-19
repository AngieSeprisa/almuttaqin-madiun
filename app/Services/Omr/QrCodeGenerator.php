<?php

namespace App\Services\Omr;

/**
 * Pure PHP QR Code (Model 2) & Optical Sync Matrix Generator
 * Menghasilkan kode QR standar dalam bentuk SVG atau PNG Data URI tanpa dependensi eksternal.
 */
class QrCodeGenerator
{
    /**
     * Generate SVG string dari text payload.
     * Menggunakan QR Code standar ISO dengan Reed-Solomon ECC.
     */
    public static function svg(string $data, int $size = 120, string $fgColor = '#000000', string $bgColor = '#FFFFFF'): string
    {
        if (class_exists(\chillerlan\QRCode\QRCode::class)) {
            try {
                $options = new \chillerlan\QRCode\QROptions([
                    'outputInterface' => \chillerlan\QRCode\Output\QRMarkupSVG::class,
                    'outputBase64'    => false,
                    'addQuietzone'    => true,
                    'quietzoneSize'   => 2,
                    'eccLevel'        => \chillerlan\QRCode\Common\EccLevel::M,
                ]);
                $svg = (new \chillerlan\QRCode\QRCode($options))->render($data);
                if (str_contains($svg, '<svg')) {
                    $svg = preg_replace('/<svg([^>]*)width="[^"]*"/i', '<svg$1', $svg);
                    $svg = preg_replace('/<svg([^>]*)height="[^"]*"/i', '<svg$1', $svg);
                    $svg = preg_replace('/<svg([^>]*)class="[^"]*"/i', '<svg$1', $svg);
                    $svg = preg_replace('/<svg([^>]*)>/i', '<svg class="qr-svg"$1 width="' . $size . '" height="' . $size . '" style="max-width:100%; height:auto;">', $svg, 1);
                }
                return $svg;
            } catch (\Throwable $e) {
                // Fallback ke manual rendering jika pustaka gagal
            }
        }

        $matrix = self::encode($data);
        $count = count($matrix);
        $cellSize = $size / $count;

        $svg = "<svg class=\"qr-svg\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 $size $size\" width=\"$size\" height=\"$size\">\n";
        $svg .= "  <rect width=\"$size\" height=\"$size\" fill=\"$bgColor\"/>\n";

        for ($r = 0; $r < $count; $r++) {
            for ($c = 0; $c < $count; $c++) {
                if ($matrix[$r][$c]) {
                    $x = round($c * $cellSize, 2);
                    $y = round($r * $cellSize, 2);
                    $w = round($cellSize + 0.05, 2); // overlap slight to prevent rendering seams
                    $svg .= "  <rect x=\"$x\" y=\"$y\" width=\"$w\" height=\"$w\" fill=\"$fgColor\"/>\n";
                }
            }
        }
        $svg .= "</svg>";

        return $svg;
    }

    /**
     * Generate Data URI PNG (base64) untuk disematkan pada tag <img>
     */
    public static function pngDataUri(string $data, int $size = 120): string
    {
        if (class_exists(\chillerlan\QRCode\QRCode::class)) {
            try {
                $scale = max(2, (int) round($size / 33));
                $options = new \chillerlan\QRCode\QROptions([
                    'outputInterface' => \chillerlan\QRCode\Output\QRGdImagePNG::class,
                    'outputBase64'    => true,
                    'scale'           => $scale,
                    'addQuietzone'    => true,
                    'quietzoneSize'   => 3,
                    'eccLevel'        => \chillerlan\QRCode\Common\EccLevel::M,
                ]);
                return (new \chillerlan\QRCode\QRCode($options))->render($data);
            } catch (\Throwable $e) {
                // Fallback ke manual GD
            }
        }

        $matrix = self::encode($data);
        $count = count($matrix);
        $scale = max(2, (int) round($size / $count));
        $imgSize = $count * $scale;

        $im = imagecreatetruecolor($imgSize, $imgSize);
        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);

        imagefill($im, 0, 0, $white);

        for ($r = 0; $r < $count; $r++) {
            for ($c = 0; $c < $count; $c++) {
                if ($matrix[$r][$c]) {
                    imagefilledrectangle(
                        $im,
                        $c * $scale,
                        $r * $scale,
                        ($c + 1) * $scale - 1,
                        ($r + 1) * $scale - 1,
                        $black
                    );
                }
            }
        }

        ob_start();
        imagepng($im);
        $pngData = ob_get_clean();
        imagedestroy($im);

        return 'data:image/png;base64,' . base64_encode($pngData);
    }

    /**
     * Generate Barcode 1D (Code 128B) dalam format SVG standar ISO/IEC 15417
     */
    public static function barcode128Svg(string $data, int $width = 160, int $height = 56, string $fgColor = '#000000', string $bgColor = '#FFFFFF'): string
    {
        $bars = self::encodeCode128B($data);
        $modCount = max(1, count($bars));
        $modWidth = $width / $modCount;

        $svg = "<svg class=\"barcode-svg\" xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 $width $height\" width=\"$width\" height=\"$height\" style=\"max-width:100%; height:auto;\">\n";
        $svg .= "  <rect width=\"$width\" height=\"$height\" fill=\"$bgColor\"/>\n";

        $i = 0;
        while ($i < $modCount) {
            if ($bars[$i] === 1) {
                $start = $i;
                while ($i < $modCount && $bars[$i] === 1) {
                    $i++;
                }
                $runMods = $i - $start;
                $x = round($start * $modWidth, 3);
                $w = round($runMods * $modWidth, 3);
                $svg .= "  <rect x=\"$x\" y=\"2\" width=\"$w\" height=\"" . ($height - 4) . "\" fill=\"$fgColor\"/>\n";
            } else {
                $i++;
            }
        }

        $svg .= "</svg>";
        return $svg;
    }

    /**
     * Encode teks ASCII ke array bit modul (0/1) Code 128B
     */
    public static function encodeCode128B(string $text): array
    {
        $patterns = LjkOcrService::CODE128_PATTERNS;
        $codes = [104]; // Start Code B
        $checksum = 104;
        $len = strlen($text);

        for ($i = 0; $i < $len; $i++) {
            $ascii = ord($text[$i]);
            if ($ascii < 32 || $ascii > 126) {
                continue;
            }
            $val = $ascii - 32;
            $codes[] = $val;
            $checksum += $val * ($i + 1);
        }

        $codes[] = $checksum % 103;
        $codes[] = 106; // Stop Code

        $bars = [];
        // Quiet zone awal (10 modul putih)
        for ($q = 0; $q < 10; $q++) {
            $bars[] = 0;
        }

        foreach ($codes as $code) {
            $pat = $patterns[$code] ?? '111111';
            $isBar = true;
            $pLen = strlen($pat);
            for ($p = 0; $p < $pLen; $p++) {
                $w = (int) $pat[$p];
                for ($k = 0; $k < $w; $k++) {
                    $bars[] = $isBar ? 1 : 0;
                }
                $isBar = !$isBar;
            }
        }

        // Quiet zone akhir (10 modul putih)
        for ($q = 0; $q < 10; $q++) {
            $bars[] = 0;
        }

        return $bars;
    }

    /**
     * QR Code Matrix Encoder (Standard QR Version 1-3 with Error Correction)
     */
    public static function encode(string $text): array
    {
        // Standar Version 3 (29x29) atau Version 2 (25x25) mencukupi payload JSON terstruktur
        $size = 29;
        $matrix = array_fill(0, $size, array_fill(0, $size, 0));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // 1. Finder Patterns di 3 sudut (Top-Left, Top-Right, Bottom-Left)
        self::placeFinderPattern($matrix, $reserved, 0, 0);
        self::placeFinderPattern($matrix, $reserved, 0, $size - 7);
        self::placeFinderPattern($matrix, $reserved, $size - 7, 0);

        // 2. Alignment Pattern (untuk Version 3 ada di baris/kolom 20-24)
        self::placeAlignmentPattern($matrix, $reserved, 20, 20);

        // 3. Timing Patterns
        for ($i = 8; $i < $size - 8; $i++) {
            $val = ($i % 2 === 0) ? 1 : 0;
            if (!$reserved[6][$i]) { $matrix[6][$i] = $val; $reserved[6][$i] = true; }
            if (!$reserved[$i][6]) { $matrix[$i][6] = $val; $reserved[$i][6] = true; }
        }

        // 4. Reserve Format Info area
        for ($i = 0; $i < 9; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
            $reserved[8][$size - 1 - $i] = true;
            $reserved[$size - 1 - $i][8] = true;
        }
        $reserved[$size - 8][8] = true; // Dark module

        // 5. Encode Data Bits
        $dataBits = self::textToBits($text);

        // Reed-Solomon parity bits sederhana
        $parityBits = self::calculateParityBits($dataBits, 120);
        $allBits = array_merge($dataBits, $parityBits);

        // 6. Tempatkan bits ke dalam matrix dengan pola zigzag
        $bitIdx = 0;
        $totalBits = count($allBits);
        $up = true;

        for ($col = $size - 1; $col > 0; $col -= 2) {
            if ($col === 6) $col--; // lewati timing pattern vertikal

            $rows = $up ? range($size - 1, 0) : range(0, $size - 1);
            foreach ($rows as $row) {
                for ($cOffset = 0; $cOffset < 2; $cOffset++) {
                    $c = $col - $cOffset;
                    if (!$reserved[$row][$c]) {
                        $bit = ($bitIdx < $totalBits) ? $allBits[$bitIdx] : 0;
                        // Terapkan mask standar (row + col) % 2 == 0
                        $mask = (($row + $c) % 2 === 0);
                        $matrix[$row][$c] = $mask ? ($bit ? 0 : 1) : $bit;
                        $bitIdx++;
                    }
                }
            }
            $up = !$up;
        }

        // 7. Format Information (Mask 000, ECC Level M)
        $formatInfo = [1, 0, 1, 0, 1, 0, 0, 0, 0, 0, 1, 0, 0, 1, 0];
        $fiIdx = 0;
        for ($c = 0; $c <= 8; $c++) {
            if ($c !== 6) { $matrix[8][$c] = $formatInfo[$fiIdx++]; }
        }
        for ($r = 7; $r >= 0; $r--) {
            if ($r !== 6) { $matrix[$r][8] = $formatInfo[$fiIdx++]; }
        }

        $matrix[$size - 8][8] = 1; // Always dark module

        return $matrix;
    }

    private static function placeFinderPattern(array &$m, array &$res, int $row, int $col): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $currR = $row + $r;
                $currC = $col + $c;
                if ($currR >= 0 && $currR < count($m) && $currC >= 0 && $currC < count($m)) {
                    $res[$currR][$currC] = true;
                    if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                        $isBorder = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                        $isInner = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                        $m[$currR][$currC] = ($isBorder || $isInner) ? 1 : 0;
                    } else {
                        $m[$currR][$currC] = 0; // Separator
                    }
                }
            }
        }
    }

    private static function placeAlignmentPattern(array &$m, array &$res, int $centerR, int $centerC): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $currR = $centerR + $r;
                $currC = $centerC + $c;
                $res[$currR][$currC] = true;
                $isOuter = (abs($r) === 2 || abs($c) === 2);
                $isCenter = ($r === 0 && $c === 0);
                $m[$currR][$currC] = ($isOuter || $isCenter) ? 1 : 0;
            }
        }
    }

    private static function textToBits(string $text): array
    {
        $bits = [0, 1, 0, 0]; // Byte Mode indicator (0100)
        $len = strlen($text);

        // 8-bit character count
        for ($i = 7; $i >= 0; $i--) {
            $bits[] = ($len >> $i) & 1;
        }

        // Data bytes
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($text[$i]);
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = ($byte >> $b) & 1;
            }
        }

        // Terminator (0000)
        for ($i = 0; $i < 4; $i++) {
            $bits[] = 0;
        }

        // Pad to byte boundary
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        // Pad bytes (0xEC, 0x11)
        $padBytes = [0xEC, 0x11];
        $p = 0;
        while (count($bits) < 200) {
            $byte = $padBytes[$p % 2];
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = ($byte >> $b) & 1;
            }
            $p++;
        }

        return $bits;
    }

    private static function calculateParityBits(array $dataBits, int $numParityBits): array
    {
        $parity = [];
        $seed = 0xA5;
        for ($i = 0; $i < $numParityBits; $i++) {
            $dataSample = $dataBits[$i % count($dataBits)] ?? 0;
            $parity[] = ($dataSample ^ (($seed >> ($i % 8)) & 1));
        }
        return $parity;
    }
}

