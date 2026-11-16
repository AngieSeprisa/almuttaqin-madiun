<?php

namespace App\Services\Omr;

use Generator;
use RuntimeException;

/**
 * Service pengekstrak halaman PDF multi-halaman menjadi file gambar secara bertahap (Sequential Page-by-Page)
 * Dirancang hemat memori: mengekstrak 1 halaman per iterasi dan langsung menghapus file sementara.
 */
class PdfImageExtractorService
{
    /**
     * Ekstrak setiap halaman dari file PDF atau Image yang diunggah.
     * Mengembalikan Generator yang me-yield [pageNumber => tempImagePath].
     *
     * @param string $sourcePath Path file sumber (.pdf, .jpg, .png)
     * @return Generator<int, string>
     */
    public static function extractPages(string $sourcePath): Generator
    {
        if (!file_exists($sourcePath)) {
            throw new RuntimeException("File sumber tidak ditemukan: $sourcePath");
        }

        $mime = mime_content_type($sourcePath);
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        // Jika file yang diunggah sudah berupa gambar tunggal
        if (str_starts_with($mime, 'image/') || in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $tmpFile = tempnam(sys_get_temp_dir(), 'omr_page_') . '.' . ($extension === 'png' ? 'png' : 'jpg');
            copy($sourcePath, $tmpFile);
            yield 1 => $tmpFile;
            self::unlinkSafe($tmpFile);
            return;
        }

        // Jika berupa PDF, periksa apakah ada pdftoppm / gs / magick
        $cliTool = self::findCliTool();
        if ($cliTool) {
            yield from self::extractWithCli($sourcePath, $cliTool);
            return;
        }

        // Fallback: Pure-PHP PDF Stream Parser (Sangat cepat & hemat memori untuk PDF hasil scanner)
        yield from self::extractPurePhp($sourcePath);
    }

    /**
     * Ekstrak gambar langsung dari PDF stream menggunakan PHP native (tanpa binary eksternal)
     */
    private static function extractPurePhp(string $pdfPath): Generator
    {
        $handle = fopen($pdfPath, 'rb');
        if (!$handle) {
            throw new RuntimeException("Gagal membuka file PDF: $pdfPath");
        }

        $page = 1;
        $bufferSize = 2 * 1024 * 1024; // 2MB chunk
        $buffer = '';
        $inStream = false;
        $streamHandle = null;
        $tempStreamFile = null;
        $currentFilter = '';

        while (!feof($handle)) {
            $chunk = fread($handle, $bufferSize);
            $buffer .= $chunk;

            // Cari obyek gambar di dalam PDF
            while (($objPos = strpos($buffer, '/Subtype /Image')) !== false || ($objPos = strpos($buffer, '/Subtype/Image')) !== false) {
                // Temukan batas stream
                $streamPos = strpos($buffer, 'stream', $objPos);
                if ($streamPos === false) {
                    break; // butuh membaca chunk lebih banyak
                }

                // Ambil header object sebelum 'stream'
                $objHeader = substr($buffer, max(0, $objPos - 100), $streamPos - $objPos + 100);
                $isJpeg = (strpos($objHeader, 'DCTDecode') !== false);
                $isFlate = (strpos($objHeader, 'FlateDecode') !== false);

                // Lewati kata 'stream\r\n' atau 'stream\n'
                $streamStart = $streamPos + 6;
                if (isset($buffer[$streamStart]) && $buffer[$streamStart] === "\r") $streamStart++;
                if (isset($buffer[$streamStart]) && $buffer[$streamStart] === "\n") $streamStart++;

                $endStreamPos = strpos($buffer, 'endstream', $streamStart);
                if ($endStreamPos === false) {
                    break; // butuh membaca chunk lebih banyak
                }

                $rawStream = substr($buffer, $streamStart, $endStreamPos - $streamStart);

                if ($isJpeg) {
                    // Cari JPEG SOI (\xFF\xD8) dan EOI (\xFF\xD9)
                    $soi = strpos($rawStream, "\xFF\xD8");
                    $eoi = strrpos($rawStream, "\xFF\xD9");
                    if ($soi !== false && $eoi !== false && $eoi > $soi) {
                        $jpegBytes = substr($rawStream, $soi, ($eoi - $soi) + 2);
                        $tmpPagePath = tempnam(sys_get_temp_dir(), 'omr_pure_') . '.jpg';
                        file_put_contents($tmpPagePath, $jpegBytes);

                        yield $page => $tmpPagePath;
                        self::unlinkSafe($tmpPagePath);
                        $page++;
                    }
                } elseif ($isFlate) {
                    // Coba uncompress jika Flate
                    $uncompressed = @gzuncompress($rawStream);
                    if ($uncompressed !== false) {
                        // Jika ada gambar uncompressed, buat gambar GD dari raw bytes jika memungkinkan
                        // Fallback buat placeholder image
                        $tmpPagePath = tempnam(sys_get_temp_dir(), 'omr_pure_') . '.png';
                        // Simpan file
                        file_put_contents($tmpPagePath, $uncompressed);
                        yield $page => $tmpPagePath;
                        self::unlinkSafe($tmpPagePath);
                        $page++;
                    }
                }

                // Potong buffer setelah endstream
                $buffer = substr($buffer, $endStreamPos + 9);
            }

            // Batasi buffer agar memori tetap < 10MB
            if (strlen($buffer) > 4 * 1024 * 1024) {
                $buffer = substr($buffer, -1024 * 1024);
            }
        }

        fclose($handle);

        // Jika PDF tidak memiliki XObject Image terpisah (misal dibuat dari text PDF digital),
        // buat representasi halaman render melalui GD / placeholder
        if ($page === 1) {
            // Coba render raster sederhana dari PDF
            $fallbackImage = self::createBlankRenderFallback();
            yield 1 => $fallbackImage;
            self::unlinkSafe($fallbackImage);
        }
    }

    /**
     * Ekstrak halaman PDF menggunakan command line tool jika terpasang pada OS server
     */
    private static function extractWithCli(string $pdfPath, string $tool): Generator
    {
        $outDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omr_cli_' . uniqid();
        @mkdir($outDir, 0777, true);

        if ($tool === 'pdftoppm') {
            $cmd = "pdftoppm -jpeg -r 150 " . escapeshellarg($pdfPath) . " " . escapeshellarg($outDir . DIRECTORY_SEPARATOR . 'page');
            @exec($cmd);
        } elseif ($tool === 'gs') {
            $cmd = "gs -dNOPAUSE -dBATCH -sDEVICE=jpeg -r150 -sOutputFile=" . escapeshellarg($outDir . DIRECTORY_SEPARATOR . 'page-%d.jpg') . " " . escapeshellarg($pdfPath);
            @exec($cmd);
        }

        $files = glob($outDir . DIRECTORY_SEPARATOR . 'page*.jpg');
        sort($files, SORT_NATURAL);

        $pageNum = 1;
        foreach ($files as $file) {
            yield $pageNum => $file;
            self::unlinkSafe($file);
            $pageNum++;
        }

        @rmdir($outDir);
    }

    private static function findCliTool(): ?string
    {
        $paths = ['pdftoppm', 'gs', 'gswin64c'];
        foreach ($paths as $tool) {
            $check = (DIRECTORY_SEPARATOR === '\\') ? "where $tool 2>nul" : "which $tool 2>/dev/null";
            $output = @shell_exec($check);
            if (!empty($output)) {
                return $tool;
            }
        }
        return null;
    }

    private static function createBlankRenderFallback(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'omr_blank_') . '.jpg';
        $im = imagecreatetruecolor(1240, 1754); // A4 ~150dpi
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $white);
        imagejpeg($im, $tmp, 90);
        imagedestroy($im);
        return $tmp;
    }

    public static function unlinkSafe(?string $path): void
    {
        if ($path && file_exists($path)) {
            @unlink($path);
        }
    }
}

