<?php

namespace App\Services\Omr;

use App\Models\Ujian;
use App\Models\UjianKunci;
use App\Models\UjianHasil;
use App\Models\Siswa;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Service Pemindaian dan Koreksi OMR (Optical Mark Recognition) + QR Code
 */
class OmrScannerService
{
    // Ambang batas kehitaman bulatan (0.0 s.d. 1.0)
    public const DARKNESS_THRESHOLD = 0.32; 

    /**
     * Proses pemindaian satu file (PDF multi-halaman atau gambar tunggal).
     * Memproses satu halaman per siklus secara berurutan dan hemat memori.
     *
     * @param Ujian $ujian Ujian terkait
     * @param string $filePath Path file fisik di server
     * @param callable|null $progressCallback Callback setiap selesai 1 halaman: fn(int $page, array $result)
     * @return array Rekapitulasi hasil koreksi semua halaman
     */
    public function processFile(Ujian $ujian, string $filePath, ?callable $progressCallback = null): array
    {
        $kunciMap = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $bobotMap = $ujian->kuncis->pluck('bobot', 'nomor_soal')->all();

        $totalPages = 0;
        $processedResults = [];

        // Ekstraksi sequential: 1 halaman per iterasi generator
        foreach (PdfImageExtractorService::extractPages($filePath) as $pageNumber => $imagePath) {
            $totalPages++;
            $pageResult = $this->processSinglePageImage($ujian, $imagePath, $pageNumber, $kunciMap, $bobotMap);
            $processedResults[] = $pageResult;

            if ($progressCallback) {
                $progressCallback($pageNumber, $pageResult);
            }

            // Bersihkan memori PHP setelah setiap halaman
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        return [
            'total_halaman' => $totalPages,
            'berhasil'      => count(array_filter($processedResults, fn($r) => !empty($r['siswa_id']))),
            'perlu_cek'     => count(array_filter($processedResults, fn($r) => ($r['status_scan'] ?? '') === 'perlu_cek_manual')),
            'detail'        => $processedResults,
            'rincian'       => $processedResults,
        ];
    }

    /**
     * Proses analisis OMR untuk satu halaman gambar LJK
     */
    public function processSinglePageImage(Ujian $ujian, string $imagePath, int $pageNumber, array $kunciMap, array $bobotMap): array
    {
        $im = $this->loadImage($imagePath);
        if (!$im) {
            return [
                'halaman'     => $pageNumber,
                'siswa_id'    => null,
                'nama_siswa'  => 'Halaman ' . $pageNumber,
                'status_scan' => 'perlu_cek_manual',
                'catatan'     => 'Gagal membaca format gambar halaman.',
            ];
        }

        $imgWidth = imagesx($im);
        $imgHeight = imagesy($im);

        // 1. Deteksi 4 Titik Sudut Fiducial Marker
        $markers = $this->detectFiducialMarkers($im, $imgWidth, $imgHeight);

        // 2. Baca Identitas Siswa dari QR Code
        $siswaData = $this->decodeQrCodeOrIdentity($im, $markers, $ujian, $imgWidth, $imgHeight);
        $siswa = null;

        if (!empty($siswaData['siswa_id'])) {
            $siswa = Siswa::find($siswaData['siswa_id']);
        }

        // Fallback jika QR tidak terbaca: cocokkan dengan siswa kelas berdasarkan urutan halaman
        if (!$siswa && $ujian->kelas_id) {
            $siswaList = Siswa::where('kelas', $ujian->kelas?->nama_kelas)
                ->orWhereHas('pendaftaran', fn($q) => $q->where('kelas', $ujian->kelas?->nama_kelas))
                ->orderBy('nama_lengkap')
                ->get();

            $siswa = $siswaList->get($pageNumber - 1) ?? null;
        }

        // 3. Evaluasi Bulatan Jawaban (OMR Sampling)
        $bubbleCoords = LjkTemplateService::getBubbleCoordinates($ujian->jumlah_soal, $ujian->jumlah_opsi);
        $jawabanSiswa = [];
        $catatanScan = [];
        $statusScan = 'valid';

        // Hitung radius bulatan berdasarkan resolusi gambar (jarak marker TL ke TR)
        $markerDistX = sqrt(pow($markers['TR']['x'] - $markers['TL']['x'], 2) + pow($markers['TR']['y'] - $markers['TL']['y'], 2));
        $sampleRadius = max(3, (int) round($markerDistX * 0.0075)); // radius ~1.5mm

        foreach ($bubbleCoords as $nomor => $opsiList) {
            $optionDarkness = [];

            foreach ($opsiList as $opsi => $coord) {
                // Transformasi koordinat relatif (u, v) ke piksel nyata (Bilinear Mapping)
                $pt = $this->mapPoint($coord['u'], $coord['v'], $markers);
                $darkness = $this->measureBubbleDarkness($im, $pt['x'], $pt['y'], $sampleRadius, $imgWidth, $imgHeight);
                $optionDarkness[$opsi] = $darkness;
            }

            // Tentukan pilihan berdasarkan tingkat kehitaman
            $markedOptions = array_keys(array_filter($optionDarkness, fn($d) => $d >= self::DARKNESS_THRESHOLD));

            if (count($markedOptions) === 1) {
                $jawabanSiswa[$nomor] = $markedOptions[0];
            } elseif (count($markedOptions) > 1) {
                // Cek apakah ada 1 yang sangat dominan (misal bekas hapusan vs hitam pekat)
                arsort($optionDarkness);
                $keys = array_keys($optionDarkness);
                $top1 = $optionDarkness[$keys[0]];
                $top2 = $optionDarkness[$keys[1]];

                if ($top1 >= 0.50 && $top2 < 0.35) {
                    $jawabanSiswa[$nomor] = $keys[0];
                } else {
                    $jawabanSiswa[$nomor] = 'GANDA';
                    $catatanScan[] = "Nomor $nomor terdeteksi jawaban ganda (" . implode(', ', $markedOptions) . ").";
                    $statusScan = 'perlu_cek_manual';
                }
            } else {
                $jawabanSiswa[$nomor] = null; // Kosong
            }
        }

        // 4. Hitung Skor Jawaban
        $jumlahBenar = 0;
        $jumlahSalah = 0;
        $jumlahKosong = 0;
        $totalSkorDidapat = 0.0;
        $totalSkorMaksimal = 0.0;

        for ($no = 1; $no <= $ujian->jumlah_soal; $no++) {
            $bobotSoal = (float) ($bobotMap[$no] ?? $ujian->bobot_benar ?? 1.0);
            $totalSkorMaksimal += $bobotSoal;

            $kunci = $kunciMap[$no] ?? null;
            $jawab = $jawabanSiswa[$no] ?? null;

            if ($jawab === null) {
                $jumlahKosong++;
            } elseif ($kunci && strtoupper($jawab) === strtoupper($kunci)) {
                $jumlahBenar++;
                $totalSkorDidapat += $bobotSoal;
            } else {
                $jumlahSalah++;
            }
        }

        $nilaiAkhir = $totalSkorMaksimal > 0
            ? round(($totalSkorDidapat / $totalSkorMaksimal) * 100, 2)
            : 0.00;

        // 5. Simpan / Perbarui ke Database jika data siswa teridentifikasi
        $hasilModel = null;
        if ($siswa) {
            $hasilModel = UjianHasil::updateOrCreate(
                [
                    'ujian_id' => $ujian->id,
                    'siswa_id' => $siswa->id,
                ],
                [
                    'jawaban_siswa' => $jawabanSiswa,
                    'jumlah_benar'  => $jumlahBenar,
                    'jumlah_salah'  => $jumlahSalah,
                    'jumlah_kosong' => $jumlahKosong,
                    'nilai_akhir'   => $nilaiAkhir,
                    'status_scan'   => $statusScan,
                    'catatan_scan'  => !empty($catatanScan) ? implode('; ', $catatanScan) : null,
                    'waktu_scan'    => now(),
                ]
            );
        }

        imagedestroy($im);

        return [
            'halaman'       => $pageNumber,
            'hasil_id'      => $hasilModel?->id,
            'siswa_id'      => $siswa?->id,
            'nama_siswa'    => $siswa?->nama_lengkap ?? "Siswa Tidak Dikenal (Hal. $pageNumber)",
            'nisn'          => $siswa?->nisn ?? '-',
            'jumlah_benar'  => $jumlahBenar,
            'jumlah_salah'  => $jumlahSalah,
            'jumlah_kosong' => $jumlahKosong,
            'nilai_akhir'   => $nilaiAkhir,
            'status_scan'   => $statusScan,
            'catatan'       => !empty($catatanScan) ? implode('; ', $catatanScan) : null,
            'catatan_scan'  => !empty($catatanScan) ? implode('; ', $catatanScan) : null,
            'jawaban'       => $jawabanSiswa,
        ];
    }

    /**
     * Deteksi 4 Titik Sudut Fiducial Markers pada LJK
     */
    private function detectFiducialMarkers($im, int $w, int $h): array
    {
        // 4 Zona Pencarian Sudut
        $zones = [
            'TL' => ['min_x' => 0,               'max_x' => (int)($w * 0.22), 'min_y' => 0,               'max_y' => (int)($h * 0.18)],
            'TR' => ['min_x' => (int)($w * 0.78), 'max_x' => $w - 1,          'min_y' => 0,               'max_y' => (int)($h * 0.18)],
            'BL' => ['min_x' => 0,               'max_x' => (int)($w * 0.22), 'min_y' => (int)($h * 0.82), 'max_y' => $h - 1],
            'BR' => ['min_x' => (int)($w * 0.78), 'max_x' => $w - 1,          'min_y' => (int)($h * 0.82), 'max_y' => $h - 1],
        ];

        $markers = [];

        foreach ($zones as $key => $z) {
            $center = $this->findMarkerInZone($im, $z['min_x'], $z['max_x'], $z['min_y'], $z['max_y']);
            if ($center) {
                $markers[$key] = $center;
            } else {
                // Fallback proporsional jika marker terpotong scanner
                $markers[$key] = match ($key) {
                    'TL' => ['x' => (int)($w * 0.057), 'y' => (int)($h * 0.040)],
                    'TR' => ['x' => (int)($w * 0.895), 'y' => (int)($h * 0.040)],
                    'BL' => ['x' => (int)($w * 0.057), 'y' => (int)($h * 0.925)],
                    'BR' => ['x' => (int)($w * 0.895), 'y' => (int)($h * 0.925)],
                };
            }
        }

        return $markers;
    }

    /**
     * Cari pusat titik hitam terpadat (marker kotak) di dalam zona pencarian
     */
    private function findMarkerInZone($im, int $x1, int $x2, int $y1, int $y2): ?array
    {
        $step = max(2, (int) round(($x2 - $x1) / 40));
        $bestDensity = 0;
        $bestX = null;
        $bestY = null;
        $radius = max(6, (int) round(($x2 - $x1) * 0.08));

        for ($y = $y1 + $radius; $y < $y2 - $radius; $y += $step) {
            for ($x = $x1 + $radius; $x < $x2 - $radius; $x += $step) {
                $darkCount = 0;
                $sampleCount = 0;

                for ($dy = -$radius; $dy <= $radius; $dy += $step) {
                    for ($dx = -$radius; $dx <= $radius; $dx += $step) {
                        $rgb = imagecolorat($im, $x + $dx, $y + $dy);
                        $r = ($rgb >> 16) & 0xFF;
                        $g = ($rgb >> 8) & 0xFF;
                        $b = $rgb & 0xFF;
                        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b);

                        if ($lum < 90) {
                            $darkCount++;
                        }
                        $sampleCount++;
                    }
                }

                $density = $sampleCount > 0 ? ($darkCount / $sampleCount) : 0;
                if ($density > 0.70 && $density > $bestDensity) {
                    $bestDensity = $density;
                    $bestX = $x;
                    $bestY = $y;
                }
            }
        }

        return ($bestX !== null && $bestY !== null) ? ['x' => $bestX, 'y' => $bestY] : null;
    }

    /**
     * Pemetaan Bilinear dari koordinat normalisasi (u, v) ke piksel nyata
     */
    private function mapPoint(float $u, float $v, array $markers): array
    {
        $tl = $markers['TL'];
        $tr = $markers['TR'];
        $bl = $markers['BL'];
        $br = $markers['BR'];

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

    /**
     * Hitung tingkat kehitaman bulatan (0.0 s.d. 1.0)
     */
    private function measureBubbleDarkness($im, int $cx, int $cy, int $radius, int $maxW, int $maxH): float
    {
        if ($cx < $radius || $cx >= $maxW - $radius || $cy < $radius || $cy >= $maxH - $radius) {
            return 0.0;
        }

        // 1. Ukur latar belakang kertas di sekitar bulatan
        $bgSamples = [
            [$cx - $radius * 2, $cy],
            [$cx + $radius * 2, $cy],
            [$cx, $cy - $radius * 2],
            [$cx, $cy + $radius * 2],
        ];
        $bgLumTotal = 0;
        $bgCount = 0;

        foreach ($bgSamples as $bg) {
            $bx = max(0, min($maxW - 1, $bg[0]));
            $by = max(0, min($maxH - 1, $bg[1]));
            $rgb = imagecolorat($im, $bx, $by);
            $bgLumTotal += (0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF));
            $bgCount++;
        }
        $paperLum = $bgCount > 0 ? ($bgLumTotal / $bgCount) : 255.0;

        // 2. Ukur isi bulatan (hanya bagian dalam, r <= 0.65 * radius)
        $innerRadius = max(2, (int) round($radius * 0.65));
        $innerLumTotal = 0;
        $innerCount = 0;

        for ($dy = -$innerRadius; $dy <= $innerRadius; $dy++) {
            for ($dx = -$innerRadius; $dx <= $innerRadius; $dx++) {
                if (($dx * $dx + $dy * $dy) <= ($innerRadius * $innerRadius)) {
                    $px = $cx + $dx;
                    $py = $cy + $dy;
                    if ($px >= 0 && $px < $maxW && $py >= 0 && $py < $maxH) {
                        $rgb = imagecolorat($im, $px, $py);
                        $innerLumTotal += (0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF));
                        $innerCount++;
                    }
                }
            }
        }

        $innerLum = $innerCount > 0 ? ($innerLumTotal / $innerCount) : 255.0;

        // Darkness rasio = seberapa gelap isi bulatan dibanding kertas
        $darkness = ($paperLum - $innerLum) / max(1.0, $paperLum);
        return max(0.0, min(1.0, $darkness));
    }

    /**
     * Dekode QR Code untuk membaca data siswa dan ujian
     */
    private function decodeQrCodeOrIdentity($im, array $markers, Ujian $ujian, int $w, int $h): array
    {
        // Jika pustaka Zxing Decoder tersedia di vendor
        if (class_exists(\Zxing\QrReader::class)) {
            try {
                $qrArea = LjkTemplateService::getQrCodeArea();
                $p1 = $this->mapPoint($qrArea['u1'], $qrArea['v1'], $markers);
                $p2 = $this->mapPoint($qrArea['u2'], $qrArea['v2'], $markers);

                $cropW = max(50, abs($p2['x'] - $p1['x']));
                $cropH = max(50, abs($p2['y'] - $p1['y']));
                $cropX = min($p1['x'], $p2['x']);
                $cropY = min($p1['y'], $p2['y']);

                $cropIm = imagecrop($im, ['x' => $cropX, 'y' => $cropY, 'width' => $cropW, 'height' => $cropH]);
                if ($cropIm) {
                    $tmpCrop = tempnam(sys_get_temp_dir(), 'omr_qr_') . '.png';
                    imagepng($cropIm, $tmpCrop);
                    imagedestroy($cropIm);

                    $qrcode = new \Zxing\QrReader($tmpCrop);
                    $text = $qrcode->text();
                    @unlink($tmpCrop);

                    if (!empty($text)) {
                        $json = @json_decode($text, true);
                        if (is_array($json) && !empty($json['s'])) {
                            return ['siswa_id' => (int)$json['s'], 'ujian_id' => (int)($json['u'] ?? $ujian->id)];
                        }
                    }
                }
            } catch (Exception $e) {
                Log::warning("Gagal membaca QR code LJK: " . $e->getMessage());
            }
        }

        return [];
    }

    private function loadImage(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) return null;

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default        => null,
        };
    }
}

