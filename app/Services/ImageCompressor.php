<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Mengompresi foto upload (GD) sebelum disimpan agar tidak memenuhi penyimpanan.
 *
 * - Resize sisi terpanjang ke $maxDimension (tidak pernah diperbesar)
 * - Konversi ke JPEG dengan kualitas $quality
 * - Memperbaiki orientasi EXIF & membuang metadata EXIF
 */
class ImageCompressor
{
    public function __construct(
        protected int $maxDimension = 1600,
        protected int $quality = 72,
        protected int $maxPixels = 40_000_000,
    ) {
    }

    /**
     * Kompres lalu simpan ke disk "public". Mengembalikan path relatif.
     */
    public function store(UploadedFile $file, string $directory = 'berkas'): string
    {
        $binary = $this->compress($file->getRealPath());

        $path = trim($directory, '/') . '/' . date('Y/m') . '/' . Str::uuid() . '.jpg';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    /**
     * Mengembalikan isi JPEG hasil kompresi.
     */
    public function compress(string $sourcePath): string
    {
        $info = @getimagesize($sourcePath);
        if ($info === false) {
            throw new RuntimeException('File bukan gambar yang valid.');
        }

        [$width, $height, $type] = $info;

        if ($width * $height > $this->maxPixels) {
            throw new RuntimeException('Resolusi foto terlalu besar.');
        }

        $source = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (! $source) {
            throw new RuntimeException('Format gambar tidak didukung (gunakan JPG, PNG, atau WEBP).');
        }

        // Perbaiki orientasi dari EXIF (foto HP sering tersimpan miring).
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($sourcePath);
            $rotate = match ($exif['Orientation'] ?? 1) {
                3 => 180,
                6 => -90,
                8 => 90,
                default => 0,
            };
            if ($rotate !== 0) {
                $rotated = imagerotate($source, $rotate, 0);
                if ($rotated) {
                    $source = $rotated;
                    $width = imagesx($source);
                    $height = imagesy($source);
                }
            }
        }

        // Hitung ukuran baru (tidak memperbesar).
        $scale = min(1, $this->maxDimension / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        // Latar putih agar PNG transparan tidak menjadi hitam di JPEG.
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        imageinterlace($canvas, true); // progressive JPEG

        ob_start();
        imagejpeg($canvas, null, $this->quality);
        $binary = ob_get_clean();
        imagedestroy($canvas);

        return $binary;
    }

    /**
     * Hapus file lama dari disk "public" (aman bila path kosong/tidak ada).
     */
    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
