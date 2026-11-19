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
     * @param int $startPage Nomor halaman awal (berguna saat browser mengirim 1 halaman per request)
     * @param string|null $clientQrPayload Hasil decode QR Code dari browser (jika tersedia)
     * @return array Rekapitulasi hasil koreksi semua halaman
     */
    public function processFile(
        Ujian $ujian,
        string $filePath,
        ?callable $progressCallback = null,
        int $startPage = 1,
        ?string $clientQrPayload = null
    ): array {
        $kunciMap = $ujian->kuncis->pluck('kunci_jawaban', 'nomor_soal')->all();
        $bobotMap = $ujian->kuncis->pluck('bobot', 'nomor_soal')->all();

        $totalPages = 0;
        $processedResults = [];

        // Ekstraksi sequential: 1 halaman per iterasi generator
        foreach (PdfImageExtractorService::extractPages($filePath) as $index => $imagePath) {
            $totalPages++;
            $actualPageNumber = ($startPage - 1) + $index;
            $qrForThisPage = ($index === 1) ? $clientQrPayload : null;

            $pageResult = $this->processSinglePageImage($ujian, $imagePath, $actualPageNumber, $kunciMap, $bobotMap, $qrForThisPage);
            $processedResults[] = $pageResult;

            if ($progressCallback) {
                $progressCallback($actualPageNumber, $pageResult);
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
    public function processSinglePageImage(
        Ujian $ujian,
        string $imagePath,
        int $pageNumber,
        array $kunciMap,
        array $bobotMap,
        ?string $clientQrPayload = null
    ): array {
        $im = $this->loadImage($imagePath);
        if (!$im) {
            return [
                'halaman'     => $pageNumber,
                'siswa_id'    => null,
                'nama_siswa'  => 'Halaman ' . $pageNumber,
                'status_scan' => 'perlu_cek_manual',
                'catatan'     => 'Gagal membaca format gambar halaman.',
                'catatan_scan'=> 'Gagal membaca format gambar halaman.',
            ];
        }

        $imgWidth = imagesx($im);
        $imgHeight = imagesy($im);
        $paperWhite = $this->estimatePaperLuminance($im, $imgWidth, $imgHeight);

        // 1. Deteksi 4 Titik Sudut Fiducial Marker
        $markers = $this->detectFiducialMarkers($im, $imgWidth, $imgHeight, $paperWhite);

        // 2. Baca Identitas Siswa dari QR Code (prioritas hasil decode browser, lalu Zxing server)
        $siswaData = $this->parseQrPayload($clientQrPayload, $ujian);
        if (empty($siswaData['siswa_id'])) {
            $siswaData = $this->decodeQrCodeOrIdentity($im, $markers, $ujian, $imgWidth, $imgHeight);
        }

        $siswa = null;
        if (!empty($siswaData['siswa_id'])) {
            $siswa = Siswa::find($siswaData['siswa_id']);
        }

        // 3. Kalibrasi vertikal dinamis berdasarkan bar header hitam (.omr-col-header)
        $deltaV = 0.0;
        $detectedHeaderBottomV = $this->detectHeaderBarBottomV($im, $markers, $ujian->jumlah_soal, $imgWidth, $imgHeight, $paperWhite);
        if ($detectedHeaderBottomV !== null) {
            $deltaV = $detectedHeaderBottomV - LjkTemplateService::getNominalHeaderBottomV();
        }

        // 4. Evaluasi Bulatan Jawaban (OMR Sampling)
        $bubbleCoords = LjkTemplateService::getBubbleCoordinates($ujian->jumlah_soal, $ujian->jumlah_opsi);
        $jawabanSiswa = [];
        $catatanScan = [];
        $statusScan = $siswa ? 'valid' : 'perlu_cek_manual';

        if (!$siswa) {
            $catatanScan[] = 'QR Code identitas siswa tidak terbaca. Harap pilih nama siswa secara manual.';
        }

        // Hitung radius bulatan berdasarkan resolusi gambar (jarak marker TL ke TR = 176mm, radius bulatan ~2.25mm => 0.0128)
        $markerDistX = sqrt(pow($markers['TR']['x'] - $markers['TL']['x'], 2) + pow($markers['TR']['y'] - $markers['TL']['y'], 2));
        $sampleRadius = max(4, (int) round($markerDistX * 0.0115)); // ~2.0mm radius bulatan

        foreach ($bubbleCoords as $nomor => $opsiList) {
            $optionDarkness = [];

            foreach ($opsiList as $opsi => $coord) {
                // Transformasi koordinat relatif (u, v + deltaV) ke piksel nyata (Bilinear Mapping)
                $pt = $this->mapPoint($coord['u'], $coord['v'] + $deltaV, $markers);
                $darkness = $this->measureBubbleDarkness($im, $pt['x'], $pt['y'], $sampleRadius, $imgWidth, $imgHeight, $paperWhite);
                $optionDarkness[$opsi] = $darkness;
            }

            // Tentukan pilihan berdasarkan tingkat kehitaman (adaptif terhadap cetakan huruf A/B/C/D di dalam bulatan kosong)
            $minDarkness = !empty($optionDarkness) ? min($optionDarkness) : 0.0;
            $markedOptions = array_keys(array_filter(
                $optionDarkness,
                fn($d) => $d >= self::DARKNESS_THRESHOLD && (($d - $minDarkness) >= 0.14 || $d >= 0.48)
            ));

            if (count($markedOptions) === 1) {
                $jawabanSiswa[$nomor] = $markedOptions[0];
            } elseif (count($markedOptions) > 1) {
                // Cek apakah ada 1 yang sangat dominan (misal bekas hapusan vs hitam pekat)
                arsort($optionDarkness);
                $keys = array_keys($optionDarkness);
                $top1 = $optionDarkness[$keys[0]];
                $top2 = $optionDarkness[$keys[1]];

                if ($top1 >= 0.50 && ($top1 - $top2) >= 0.18 && $top2 < 0.38) {
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
            'nama_siswa'    => $siswa?->nama_lengkap ?? "Siswa Belum Teridentifikasi (Hal. $pageNumber)",
            'nisn'          => $siswa?->pendaftaran?->nisn ?? $siswa?->nisn ?? '-',
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
     * Estimasi kecerahan putih kertas (persentil ke-90 di area tengah kertas)
     */
    private function estimatePaperLuminance($im, int $w, int $h): float
    {
        $samples = [];
        $stepX = max(10, (int) ($w / 18));
        $stepY = max(10, (int) ($h / 24));

        for ($y = (int)($h * 0.12); $y < (int)($h * 0.88); $y += $stepY) {
            for ($x = (int)($w * 0.12); $x < (int)($w * 0.88); $x += $stepX) {
                $rgb = imagecolorat($im, $x, $y);
                $samples[] = 0.299 * (($rgb >> 16) & 0xFF)
                           + 0.587 * (($rgb >> 8) & 0xFF)
                           + 0.114 * ($rgb & 0xFF);
            }
        }

        if (empty($samples)) {
            return 250.0;
        }

        sort($samples, SORT_NUMERIC);
        $p90Idx = (int) floor(count($samples) * 0.90);
        return max(150.0, (float) ($samples[$p90Idx] ?? 250.0));
    }

    /**
     * Parse teks QR Code (JSON LjkTemplateService::makeQrPayload atau ID teks)
     */
    private function parseQrPayload(?string $raw, Ujian $ujian): array
    {
        if (empty($raw)) {
            return [];
        }

        $trimmed = trim($raw);
        $json = @json_decode($trimmed, true);
        if (is_array($json) && !empty($json['s'])) {
            return [
                'siswa_id' => (int) $json['s'],
                'ujian_id' => (int) ($json['u'] ?? $ujian->id),
            ];
        }

        // Pola Barcode 1D: LJK-{ujian_id}-{siswa_id}
        if (preg_match('/^LJK-(\d+)-(\d{1,6})$/i', $trimmed, $m)) {
            return [
                'ujian_id' => (int) $m[1],
                'siswa_id' => (int) $m[2],
            ];
        }

        // Fallback pola teks ID: ID: UJN-202611-001-0192
        if (preg_match('/-(\d{1,6})$/', $trimmed, $m)) {
            return [
                'siswa_id' => (int) $m[1],
                'ujian_id' => $ujian->id,
            ];
        }

        return [];
    }

    /**
     * Deteksi 4 Titik Sudut Fiducial Markers pada LJK
     */
    private function detectFiducialMarkers($im, int $w, int $h, float $paperWhite = 250.0): array
    {
        // 4 Zona Pencarian Sudut (lebar sedikit longgar agar toleran terhadap margin scanner)
        $zones = [
            'TL' => ['min_x' => 0,               'max_x' => (int)($w * 0.24), 'min_y' => 0,               'max_y' => (int)($h * 0.20)],
            'TR' => ['min_x' => (int)($w * 0.76), 'max_x' => $w - 1,          'min_y' => 0,               'max_y' => (int)($h * 0.20)],
            'BL' => ['min_x' => 0,               'max_x' => (int)($w * 0.24), 'min_y' => (int)($h * 0.80), 'max_y' => $h - 1],
            'BR' => ['min_x' => (int)($w * 0.76), 'max_x' => $w - 1,          'min_y' => (int)($h * 0.80), 'max_y' => $h - 1],
        ];

        $darkCutoff = min(145.0, max(85.0, $paperWhite * 0.58));
        $markers = [];

        foreach ($zones as $key => $z) {
            $center = $this->findMarkerInZone($im, $z['min_x'], $z['max_x'], $z['min_y'], $z['max_y'], $w, $h, $darkCutoff);
            if ($center) {
                $markers[$key] = $center;
            } else {
                // Fallback proporsional pusat marker (17/210 = 0.081, 193/210 = 0.919, 17/297 = 0.057, 280/297 = 0.943)
                $markers[$key] = match ($key) {
                    'TL' => ['x' => (int)($w * 0.081), 'y' => (int)($h * 0.057)],
                    'TR' => ['x' => (int)($w * 0.919), 'y' => (int)($h * 0.057)],
                    'BL' => ['x' => (int)($w * 0.081), 'y' => (int)($h * 0.943)],
                    'BR' => ['x' => (int)($w * 0.919), 'y' => (int)($h * 0.943)],
                };
            }
        }

        return $markers;
    }

    /**
     * Cari pusat titik hitam terpadat (marker kotak 10x10mm) di dalam zona pencarian lalu haluskan pusat massanya
     */
    private function findMarkerInZone($im, int $x1, int $x2, int $y1, int $y2, int $imgW, int $imgH, float $darkCutoff = 120.0): ?array
    {
        $zoneW = max(1, $x2 - $x1);
        $step = max(2, (int) round($zoneW / 42));
        $bestDensity = 0.0;
        $bestX = null;
        $bestY = null;
        // Setengah sisi kotak 10mm pada kertas 210mm ~= 0.0238 * imgW
        $radius = max(6, (int) round($imgW * 0.018));

        for ($y = $y1 + $radius; $y < $y2 - $radius; $y += $step) {
            for ($x = $x1 + $radius; $x < $x2 - $radius; $x += $step) {
                $darkCount = 0;
                $sampleCount = 0;

                for ($dy = -$radius; $dy <= $radius; $dy += $step) {
                    for ($dx = -$radius; $dx <= $radius; $dx += $step) {
                        $rgb = imagecolorat($im, $x + $dx, $y + $dy);
                        $lum = 0.299 * (($rgb >> 16) & 0xFF)
                             + 0.587 * (($rgb >> 8) & 0xFF)
                             + 0.114 * ($rgb & 0xFF);

                        if ($lum < $darkCutoff) {
                            $darkCount++;
                        }
                        $sampleCount++;
                    }
                }

                $density = $sampleCount > 0 ? ($darkCount / $sampleCount) : 0.0;
                if ($density > 0.68 && $density > $bestDensity) {
                    $bestDensity = $density;
                    $bestX = $x;
                    $bestY = $y;
                }
            }
        }

        if ($bestX === null || $bestY === null) {
            return null;
        }

        // Haluskan pusat kotak dengan menghitung centroid piksel gelap di sekitar kandidat terbaik
        $refineR = max($radius + 4, (int) round($imgW * 0.032));
        $sumX = 0;
        $sumY = 0;
        $count = 0;

        $rx1 = max($x1, $bestX - $refineR);
        $rx2 = min($x2, $bestX + $refineR);
        $ry1 = max($y1, $bestY - $refineR);
        $ry2 = min($y2, $bestY + $refineR);

        for ($y = $ry1; $y <= $ry2; $y += 2) {
            for ($x = $rx1; $x <= $rx2; $x += 2) {
                $rgb = imagecolorat($im, $x, $y);
                $lum = 0.299 * (($rgb >> 16) & 0xFF)
                     + 0.587 * (($rgb >> 8) & 0xFF)
                     + 0.114 * ($rgb & 0xFF);

                if ($lum < $darkCutoff) {
                    $sumX += $x;
                    $sumY += $y;
                    $count++;
                }
            }
        }

        if ($count > 10) {
            return [
                'x' => (int) round($sumX / $count),
                'y' => (int) round($sumY / $count),
            ];
        }

        return ['x' => $bestX, 'y' => $bestY];
    }

    /**
     * Deteksi otomatis tepi bawah bar header kolom hitam (.omr-col-header "NOMOR 1 - 10", dst)
     * agar posisi vertikal grid tetap presisi meski jumlah baris peraturan ujian berubah.
     */
    private function detectHeaderBarBottomV($im, array $markers, int $jumlahSoal, int $w, int $h, float $paperWhite): ?float
    {
        $bands = LjkTemplateService::getColumnHeaderBands($jumlahSoal);
        if (empty($bands)) {
            return null;
        }

        $darkThreshold = $paperWhite * 0.52;
        $vMin = 0.18;
        $vMax = 0.50;
        $vStep = 0.002;

        $runStartV = null;
        $bestRunHeight = 0.0;
        $bestRunBottomV = null;

        for ($v = $vMin; $v <= $vMax; $v += $vStep) {
            $colsDark = 0;
            foreach ($bands as $band) {
                $darkPts = 0;
                $totalPts = 0;
                // Sample 7 titik horizontal di sepanjang bar kolom
                for ($i = 0; $i < 7; $i++) {
                    $u = $band['u1'] + (($band['u2'] - $band['u1']) * ($i / 6.0));
                    $pt = $this->mapPoint($u, $v, $markers);
                    if ($pt['x'] >= 0 && $pt['x'] < $w && $pt['y'] >= 0 && $pt['y'] < $h) {
                        $rgb = imagecolorat($im, $pt['x'], $pt['y']);
                        $lum = 0.299 * (($rgb >> 16) & 0xFF)
                             + 0.587 * (($rgb >> 8) & 0xFF)
                             + 0.114 * ($rgb & 0xFF);
                        if ($lum < $darkThreshold) {
                            $darkPts++;
                        }
                        $totalPts++;
                    }
                }
                if ($totalPts > 0 && ($darkPts / $totalPts) >= 0.55) {
                    $colsDark++;
                }
            }

            $isHeaderRow = ($colsDark >= max(2, count($bands) - 1));
            if ($isHeaderRow) {
                if ($runStartV === null) {
                    $runStartV = $v;
                }
            } else {
                if ($runStartV !== null) {
                    $runHeight = $v - $runStartV;
                    // Tinggi bar .omr-col-header ~5mm = 0.019 (rentang wajar 0.009 - 0.038)
                    if ($runHeight >= 0.009 && $runHeight <= 0.038 && $runHeight > $bestRunHeight) {
                        $bestRunHeight = $runHeight;
                        $bestRunBottomV = $v;
                    }
                    $runStartV = null;
                }
            }
        }

        return $bestRunBottomV;
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
    private function measureBubbleDarkness($im, int $cx, int $cy, int $radius, int $maxW, int $maxH, float $sheetPaperLum = 250.0): float
    {
        if ($cx < $radius || $cx >= $maxW - $radius || $cy < $radius || $cy >= $maxH - $radius) {
            return 0.0;
        }

        // 1. Ukur latar belakang kertas di sekitar bulatan (ambil piksel tercerah agar tidak terganggu garis tepi/huruf)
        $bgOffset = (int) round($radius * 1.45);
        $bgSamples = [
            [$cx - $bgOffset, $cy - $bgOffset],
            [$cx + $bgOffset, $cy - $bgOffset],
            [$cx - $bgOffset, $cy + $bgOffset],
            [$cx + $bgOffset, $cy + $bgOffset],
        ];
        $localMaxLum = 0.0;

        foreach ($bgSamples as $bg) {
            $bx = max(0, min($maxW - 1, $bg[0]));
            $by = max(0, min($maxH - 1, $bg[1]));
            $rgb = imagecolorat($im, $bx, $by);
            $lum = 0.299 * (($rgb >> 16) & 0xFF) + 0.587 * (($rgb >> 8) & 0xFF) + 0.114 * ($rgb & 0xFF);
            if ($lum > $localMaxLum) {
                $localMaxLum = $lum;
            }
        }
        $paperLum = max($localMaxLum, $sheetPaperLum * 0.90, 120.0);

        // 2. Ukur isi bulatan (bagian dalam, r <= 0.70 * radius agar tidak menangkap ring lingkaran luar)
        $innerRadius = max(2, (int) round($radius * 0.70));
        $innerLumTotal = 0.0;
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

        $innerLum = $innerCount > 0 ? ($innerLumTotal / $innerCount) : $paperLum;

        // Darkness rasio = seberapa gelap isi bulatan dibanding kertas
        $darkness = ($paperLum - $innerLum) / max(1.0, $paperLum);
        return max(0.0, min(1.0, $darkness));
    }

    /**
     * Dekode QR Code untuk membaca data siswa dan ujian (fallback server-side jika Zxing tersedia)
     */
    private function decodeQrCodeOrIdentity($im, array $markers, Ujian $ujian, int $w, int $h): array
    {
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

                    $parsed = $this->parseQrPayload($text, $ujian);
                    if (!empty($parsed['siswa_id'])) {
                        return $parsed;
                    }
                }
            } catch (Exception $e) {
                Log::warning("Gagal membaca QR code LJK: " . $e->getMessage());
            }
        }

        // Fallback Otomatis: Decode Barcode 1D (Code 128) + OCR Teks "ID: UJN-..." + OCR "Nama Siswa"
        try {
            $ocrIdentity = LjkOcrService::identifyStudent($im, $markers, $ujian);
            if (!empty($ocrIdentity['siswa_id'])) {
                return $ocrIdentity;
            }
        } catch (Exception $e) {
            Log::warning("Gagal membaca OCR identitas LJK: " . $e->getMessage());
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

