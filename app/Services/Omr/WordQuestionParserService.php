<?php

namespace App\Services\Omr;

use ZipArchive;
use Illuminate\Support\Facades\Storage;

class WordQuestionParserService
{
    /**
     * Parse file Word (.docx) dan ekstrak butir soal, opsi A-E, serta kunci jawaban
     *
     * @param string $filePath Path fisik ke file .docx
     * @return array [ 'soals' => [...], 'total_soal' => int, 'jumlah_opsi' => int ]
     */
    public function parse(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File naskah soal tidak ditemukan: {$filePath}");
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new \Exception("Gagal membuka file Word (.docx). Pastikan file berformat .docx valid.");
        }

        // Ambil isi dokumen utama
        $xmlContent = $zip->getFromName('word/document.xml');
        if (!$xmlContent) {
            $zip->close();
            throw new \Exception("Format dokumen Word tidak valid (word/document.xml tidak ditemukan).");
        }

        $zip->close();

        // Bersihkan dan ekstrak teks per paragraf
        $paragraphs = $this->extractParagraphs($xmlContent);

        return $this->parseParagraphsToQuestions($paragraphs);
    }

    /**
     * Ekstrak teks paragraf dari XML Word
     */
    private function extractParagraphs(string $xmlContent): array
    {
        $paragraphs = [];

        // Hapus namespace XML agar parsing regex / DOM lebih mudah
        $cleanXml = preg_replace('/(<\/?)w:/', '$1', $xmlContent);

        // Cari setiap blok <p> ... </p>
        if (preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $cleanXml, $matches)) {
            foreach ($matches[1] as $pXml) {
                // Konversi <tab/> menjadi spasi ganda
                $pXml = preg_replace('/<tab\/>/i', '  ', $pXml);

                // Ambil semua teks dalam <t>...</t>
                if (preg_match_all('/<t\b[^>]*>(.*?)<\/t>/is', $pXml, $tMatches)) {
                    $text = implode('', $tMatches[1]);
                    $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
                    $text = trim(preg_replace('/\s+/', ' ', $text));

                    if ($text !== '') {
                        $paragraphs[] = $text;
                    }
                }
            }
        }

        return $paragraphs;
    }

    /**
     * Kelompokkan baris paragraf menjadi struktur soal Pilihan Ganda (PG) dan Essay/Uraian
     */
    private function parseParagraphsToQuestions(array $paragraphs): array
    {
        $soalsPg = [];
        $soalsEssay = [];
        $currentSoal = null;
        $currentMode = 'pg'; // 'pg' atau 'essay'
        $inPedoman = false;
        $maxOpsiCount = 4;

        $flushCurrent = function () use (&$currentSoal, &$soalsPg, &$soalsEssay) {
            if ($currentSoal === null) {
                return;
            }
            $finalized = $this->finalizeSoal($currentSoal);
            if ($finalized['tipe_soal'] === 'essay') {
                $soalsEssay[$finalized['nomor']] = $finalized;
            } else {
                $soalsPg[$finalized['nomor']] = $finalized;
            }
            $currentSoal = null;
        };

        foreach ($paragraphs as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            // 0. Deteksi penanda bagian [ESSAY] / BAGIAN B. ESSAY / SOAL URAIAN
            if ($this->isEssaySectionHeader($line)) {
                $flushCurrent();
                $currentMode = 'essay';
                $inPedoman = false;
                continue;
            }

            // 0b. Deteksi penanda bagian [PILIHAN GANDA] / BAGIAN A. PILIHAN GANDA
            if ($this->isPgSectionHeader($line)) {
                $flushCurrent();
                $currentMode = 'pg';
                $inPedoman = false;
                continue;
            }

            // 1. Cek baris TIMER per soal (misal: "Timer: 180", "Timer: 3 menit", "Waktu: 120 detik")
            if ($currentSoal !== null && preg_match('/^(?:timer|waktu)\s*[:=\-]\s*(\d+)\s*(menit|mnt|m|detik|dtk|s)?\b/i', $line, $tm)) {
                $val = (int) $tm[1];
                $unit = strtolower($tm[2] ?? 'detik');
                if (in_array($unit, ['menit', 'mnt', 'm'], true)) {
                    $val *= 60;
                }
                if ($val >= 5 && $val <= 3600) {
                    $currentSoal['timer_detik'] = $val;
                }
                continue;
            }

            // 1b. Cek baris SKOR MAKS khusus essay (misal: "Skor: 20", "Skor Maks: 25", "Poin: 10")
            if ($currentSoal !== null && $currentMode === 'essay' && preg_match('/^(?:skor(?:\s*maks(?:imal)?)?|poin|bobot)\s*[:=\-]\s*(\d+(?:[\.,]\d+)?)/i', $line, $sm)) {
                $currentSoal['skor_maks'] = (float) str_replace(',', '.', $sm[1]);
                continue;
            }

            // 2. Cek baris KUNCI JAWABAN / PEDOMAN
            if ($currentMode === 'pg') {
                if (preg_match('/^(kunci\s*jawaban|kunci|jawaban|ans|key)\s*[:=\-]\s*([A-Ea-e])\b/i', $line, $km)) {
                    if ($currentSoal !== null) {
                        $currentSoal['kunci'] = strtoupper($km[2]);
                    }
                    continue;
                }
            } else {
                // Mode essay: Kunci / Pedoman / Jawaban / Rubrik bisa berupa kalimat panjang
                if (preg_match('/^(pedoman(?:\s*jawaban)?|kunci(?:\s*jawaban)?|jawaban|rubrik)\s*[:=\-]\s*(.*)$/i', $line, $pm)) {
                    if ($currentSoal !== null) {
                        $currentSoal['pedoman_jawaban'] = trim($pm[2]);
                        $inPedoman = true;
                    }
                    continue;
                }
            }

            // 3. Cek baris nomor soal baru (misal: "1.", "1)", "1. ", "No. 1.")
            if (preg_match('/^(?:no\.?\s*)?(\d+)[\.\)\:\-]\s*(.*)$/i', $line, $qm)) {
                $nomor = (int) $qm[1];
                $teks = trim($qm[2]);

                $flushCurrent();
                $inPedoman = false;

                $currentSoal = [
                    'tipe_soal'       => $currentMode,
                    'nomor'           => $nomor,
                    'pertanyaan'      => $teks,
                    'opsi'            => [],
                    'kunci'           => $currentMode === 'pg' ? 'A' : null,
                    'skor_maks'       => null,
                    'pedoman_jawaban' => null,
                    'timer_detik'     => null,
                ];
                continue;
            }

            // Jika belum ada nomor soal, lewati (kop soal / petunjuk awal)
            if ($currentSoal === null) {
                continue;
            }

            // Mode Essay: lanjutan teks pertanyaan atau lanjutan pedoman jawaban
            if ($currentMode === 'essay') {
                if ($inPedoman) {
                    $currentSoal['pedoman_jawaban'] = trim(($currentSoal['pedoman_jawaban'] ?? '') . "\n" . $line);
                } else {
                    $currentSoal['pertanyaan'] .= ($currentSoal['pertanyaan'] !== '' ? "\n" : '') . $line;
                }
                continue;
            }

            // Mode PG: cek opsi ganda dalam satu baris (misal: "A. Merah B. Kuning C. Hijau D. Biru")
            $multiOptions = $this->extractInlineOptions($line);
            if (!empty($multiOptions)) {
                foreach ($multiOptions as $opt => $optText) {
                    $currentSoal['opsi'][$opt] = $optText;
                }
                continue;
            }

            // Mode PG: cek baris opsi tunggal (misal: "A. Jakarta" atau "A) Jakarta" atau "(A) Jakarta")
            if (preg_match('/^\(?([A-Ea-e])[\.\)\:\-]\s*(.*)$/i', $line, $om)) {
                $huruf = strtoupper($om[1]);
                $currentSoal['opsi'][$huruf] = trim($om[2]);
                continue;
            }

            // Mode PG: lanjutan teks pertanyaan atau opsi terakhir
            if (empty($currentSoal['opsi'])) {
                $currentSoal['pertanyaan'] .= ($currentSoal['pertanyaan'] !== '' ? "\n" : '') . $line;
            } else {
                end($currentSoal['opsi']);
                $lastOpt = key($currentSoal['opsi']);
                if ($lastOpt) {
                    $currentSoal['opsi'][$lastOpt] .= ' ' . $line;
                }
            }
        }

        // Simpan soal terakhir
        $flushCurrent();

        // Hitung estimasi jumlah opsi (4 atau 5) dari soal PG
        foreach ($soalsPg as $s) {
            if (!empty($s['opsi_e'])) {
                $maxOpsiCount = 5;
                break;
            }
        }

        ksort($soalsPg);
        ksort($soalsEssay);

        // Normalisasi nomor essay menjadi 1..N agar selalu selaras dengan nomor Essay pada LJK
        $normalizedEssay = [];
        $essaySeq = 1;
        foreach ($soalsEssay as $es) {
            $es['nomor'] = $essaySeq++;
            $normalizedEssay[] = $es;
        }

        $pgList = array_values($soalsPg);

        return [
            'soals'            => $pgList,
            'soals_pg'         => $pgList,
            'soals_essay'      => $normalizedEssay,
            'total_soal'       => count($pgList),
            'total_soal_pg'    => count($pgList),
            'total_soal_essay' => count($normalizedEssay),
            'jumlah_opsi'      => $maxOpsiCount,
        ];
    }

    /**
     * Deteksi baris penanda dimulainya bagian Soal Essay / Uraian
     */
    private function isEssaySectionHeader(string $line): bool
    {
        if (mb_strlen($line) > 80) {
            return false;
        }

        if (preg_match('/^\[?\s*(?:bagian\s*(?:b|ii|2)\s*[:.\-]?\s*)?(?:soal\s+)?(?:essay|esai|uraian)(?:\s*\/\s*(?:essay|esai|uraian))?\s*\]?(?:\s*[:.\-].*)?$/i', $line)) {
            return true;
        }

        if (preg_match('/^(?:B|II)[\.\)]\s*(?:soal\s+)?(?:essay|esai|uraian)\b/i', $line)) {
            return true;
        }

        return false;
    }

    /**
     * Deteksi baris penanda dimulainya bagian Soal Pilihan Ganda
     */
    private function isPgSectionHeader(string $line): bool
    {
        if (mb_strlen($line) > 80) {
            return false;
        }

        if (preg_match('/^\[?\s*(?:bagian\s*(?:a|i|1)\s*[:.\-]?\s*)?(?:soal\s+)?(?:pilihan\s+ganda|pg)\s*\]?(?:\s*[:.\-].*)?$/i', $line)) {
            return true;
        }

        if (preg_match('/^(?:A|I)[\.\)]\s*(?:soal\s+)?(?:pilihan\s+ganda|pg)\b/i', $line)) {
            return true;
        }

        return false;
    }

    /**
     * Ekstrak beberapa opsi pilihan jika berada dalam 1 baris
     */
    private function extractInlineOptions(string $line): array
    {
        $options = [];
        // Regex cari pola A. ... B. ... C. ... D. ...
        $pattern = '/(?<=\s|^)([A-Ea-e])[\.\)]\s*(.*?)(?=(?:\s+[A-Ea-e][\.\)]|$))/i';
        if (preg_match_all($pattern, $line, $matches, PREG_SET_ORDER)) {
            if (count($matches) >= 2) {
                foreach ($matches as $m) {
                    $huruf = strtoupper(trim($m[1]));
                    $text = trim($m[2]);
                    $options[$huruf] = $text;
                }
            }
        }
        return $options;
    }

    /**
     * Finalisasi satu butir soal
     */
    private function finalizeSoal(array $s): array
    {
        $isEssay = ($s['tipe_soal'] ?? 'pg') === 'essay';

        return [
            'tipe_soal'       => $isEssay ? 'essay' : 'pg',
            'nomor'           => $s['nomor'],
            'pertanyaan'      => trim($s['pertanyaan']),
            'opsi_a'          => $isEssay ? null : ($s['opsi']['A'] ?? ''),
            'opsi_b'          => $isEssay ? null : ($s['opsi']['B'] ?? ''),
            'opsi_c'          => $isEssay ? null : ($s['opsi']['C'] ?? ''),
            'opsi_d'          => $isEssay ? null : ($s['opsi']['D'] ?? ''),
            'opsi_e'          => $isEssay ? null : ($s['opsi']['E'] ?? null),
            'kunci'           => $isEssay ? null : ($s['kunci'] ?? 'A'),
            'skor_maks'       => $s['skor_maks'] ?? null,
            'pedoman_jawaban' => $s['pedoman_jawaban'] ?? null,
            'timer_detik'     => $s['timer_detik'] ?? null,
        ];
    }

    /**
     * Generate Template Naskah Soal Word (.docx) Resmi untuk Diunduh Guru
     */
    public function generateTemplateDocx(): string
    {
        $zip = new ZipArchive();
        $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'template_soal_ujian_' . uniqid() . '.docx';

        if ($zip->open($tempPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \Exception("Gagal membuat berkas template Word sementara.");
        }

        // [Content_Types].xml
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
    <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
    <Default Extension="xml" ContentType="application/xml"/>
    <Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>
</Types>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);

        // _rels/.rels
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
    <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>
</Relationships>';
        $zip->addFromString('_rels/.rels', $rels);

        // word/document.xml
        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:body>
        <w:p>
            <w:pPr><w:jc w:val="center"/><w:b/></w:pPr>
            <w:r><w:rPr><w:b/><w:sz w:val="32"/></w:rPr><w:t>TEMPLATE NASKAH SOAL UJIAN</w:t></w:r>
        </w:p>
        <w:p>
            <w:pPr><w:jc w:val="center"/></w:pPr>
            <w:r><w:rPr><w:i/><w:sz w:val="20"/></w:rPr><w:t>Petunjuk: Tulis soal Pilihan Ganda dengan nomor (1, 2, 3...), opsi (A-E), dan Kunci:. Jika ada soal uraian, tulis pemisah [ESSAY] lalu nomor 1, 2, dst.</w:t></w:r>
        </w:p>
        <w:p><w:r><w:t></w:t></w:r></w:p>

        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>[PILIHAN GANDA]</w:t></w:r></w:p>
        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>1. Manakah di bawah ini yang merupakan rukun Islam pertama?</w:t></w:r></w:p>
        <w:p><w:r><w:t>A. Mengucapkan dua kalimat syahadat</w:t></w:r></w:p>
        <w:p><w:r><w:t>B. Menunaikan ibadah sholat lima waktu</w:t></w:r></w:p>
        <w:p><w:r><w:t>C. Membayar zakat fitrah</w:t></w:r></w:p>
        <w:p><w:r><w:t>D. Berpuasa di bulan Ramadhan</w:t></w:r></w:p>
        <w:p><w:r><w:t>E. Menunaikan ibadah haji bagi yang mampu</w:t></w:r></w:p>
        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Kunci: A</w:t></w:r></w:p>
        <w:p><w:r><w:t></w:t></w:r></w:p>

        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>2. Berapakah hasil dari perhitungan 15 x 6 + 10?</w:t></w:r></w:p>
        <w:p><w:r><w:t>A. 85</w:t></w:r></w:p>
        <w:p><w:r><w:t>B. 90</w:t></w:r></w:p>
        <w:p><w:r><w:t>C. 100</w:t></w:r></w:p>
        <w:p><w:r><w:t>D. 110</w:t></w:r></w:p>
        <w:p><w:r><w:t>E. 120</w:t></w:r></w:p>
        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Kunci: C</w:t></w:r></w:p>
        <w:p><w:r><w:t></w:t></w:r></w:p>

        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>3. Ibukota Negara Indonesia saat ini adalah...</w:t></w:r></w:p>
        <w:p><w:r><w:t>A. Surabaya</w:t></w:r></w:p>
        <w:p><w:r><w:t>B. Jakarta</w:t></w:r></w:p>
        <w:p><w:r><w:t>C. Bandung</w:t></w:r></w:p>
        <w:p><w:r><w:t>D. Medan</w:t></w:r></w:p>
        <w:p><w:r><w:t>E. Makassar</w:t></w:r></w:p>
        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>Kunci: B</w:t></w:r></w:p>
        <w:p><w:r><w:t></w:t></w:r></w:p>

        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>[ESSAY]</w:t></w:r></w:p>
        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>1. Jelaskan secara singkat pengertian dan hikmah menuntut ilmu dalam Islam!</w:t></w:r></w:p>
        <w:p><w:r><w:t>Pedoman: Menjelaskan kewajiban menuntut ilmu bagi muslim serta minimal 2 hikmah seperti meningkatkan derajat dan kemudahan jalan menuju surga.</w:t></w:r></w:p>
        <w:p><w:r><w:t>Timer: 180 detik</w:t></w:r></w:p>
        <w:p><w:r><w:t></w:t></w:r></w:p>

        <w:p><w:r><w:rPr><w:b/></w:rPr><w:t>2. Sebutkan 3 contoh adab santri terhadap guru di lingkungan pondok pesantren!</w:t></w:r></w:p>
        <w:p><w:r><w:t>Pedoman: Menghormati dan menyapa dengan sopan, menyimak pelajaran dengan tenang, serta melaksanakan nasihat dan tugas dengan ikhlas.</w:t></w:r></w:p>
        <w:p><w:r><w:t>Timer: 180 detik</w:t></w:r></w:p>
    </w:body>
</w:document>';
        $zip->addFromString('word/document.xml', $documentXml);

        $zip->close();

        return $tempPath;
    }
}

