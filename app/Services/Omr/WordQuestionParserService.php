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
     * Kelompokkan baris paragraf menjadi struktur nomor soal, pertanyaan, opsi, dan kunci
     */
    private function parseParagraphsToQuestions(array $paragraphs): array
    {
        $soals = [];
        $currentSoal = null;
        $maxOpsiCount = 4;

        foreach ($paragraphs as $line) {
            $line = trim($line);
            if ($line === '') continue;

            // 1. Cek baris KUNCI JAWABAN (misal: "Kunci: A" atau "Jawaban: B" atau "Kunci Jawaban = C")
            if (preg_match('/^(kunci\s*jawaban|kunci|jawaban|ans|key)\s*[:=\-]\s*([A-Ea-e])/i', $line, $km)) {
                if ($currentSoal !== null) {
                    $currentSoal['kunci'] = strtoupper($km[2]);
                }
                continue;
            }

            // 2. Cek baris nomor soal baru (misal: "1.", "1)", "1. ", "No. 1.")
            if (preg_match('/^(?:no\.?\s*)?(\d+)[\.\)\:\-]\s*(.*)$/i', $line, $qm)) {
                $nomor = (int)$qm[1];
                $teks = trim($qm[2]);

                // Simpan soal sebelumnya jika ada
                if ($currentSoal !== null) {
                    $soals[$currentSoal['nomor']] = $this->finalizeSoal($currentSoal);
                }

                $currentSoal = [
                    'nomor'      => $nomor,
                    'pertanyaan' => $teks,
                    'opsi'       => [],
                    'kunci'      => 'A',
                ];
                continue;
            }

            // Jika belum ada nomor soal, lewati (mungkin kop soal / petunjuk awal)
            if ($currentSoal === null) {
                continue;
            }

            // 3. Cek apakah baris ini memuat OPSI GANDA DALAM SATU BARIS (misal: "A. Merah B. Kuning C. Hijau D. Biru")
            $multiOptions = $this->extractInlineOptions($line);
            if (!empty($multiOptions)) {
                foreach ($multiOptions as $opt => $optText) {
                    $currentSoal['opsi'][$opt] = $optText;
                }
                continue;
            }

            // 4. Cek baris opsi tunggal (misal: "A. Jakarta" atau "A) Jakarta" atau "(A) Jakarta")
            if (preg_match('/^\(?([A-Ea-e])[\.\)\:\-]\s*(.*)$/i', $line, $om)) {
                $huruf = strtoupper($om[1]);
                $currentSoal['opsi'][$huruf] = trim($om[2]);
                continue;
            }

            // 5. Jika tidak ada opsi yang terdeteksi, anggap baris lanjutan teks pertanyaan
            if (empty($currentSoal['opsi'])) {
                $currentSoal['pertanyaan'] .= ($currentSoal['pertanyaan'] !== '' ? "\n" : '') . $line;
            } else {
                // Lanjutan teks opsi terakhir
                end($currentSoal['opsi']);
                $lastOpt = key($currentSoal['opsi']);
                if ($lastOpt) {
                    $currentSoal['opsi'][$lastOpt] .= ' ' . $line;
                }
            }
        }

        // Simpan soal terakhir
        if ($currentSoal !== null) {
            $soals[$currentSoal['nomor']] = $this->finalizeSoal($currentSoal);
        }

        // Hitung estimasi jumlah opsi (4 atau 5)
        foreach ($soals as $s) {
            if (!empty($s['opsi_e'])) {
                $maxOpsiCount = 5;
                break;
            }
        }

        ksort($soals);

        return [
            'soals'       => array_values($soals),
            'total_soal'  => count($soals),
            'jumlah_opsi' => $maxOpsiCount,
        ];
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
        return [
            'nomor'      => $s['nomor'],
            'pertanyaan' => trim($s['pertanyaan']),
            'opsi_a'     => $s['opsi']['A'] ?? '',
            'opsi_b'     => $s['opsi']['B'] ?? '',
            'opsi_c'     => $s['opsi']['C'] ?? '',
            'opsi_d'     => $s['opsi']['D'] ?? '',
            'opsi_e'     => $s['opsi']['E'] ?? null,
            'kunci'      => $s['kunci'] ?? 'A',
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
            <w:r><w:rPr><w:i/><w:sz w:val="20"/></w:rPr><w:t>Petunjuk: Tulis nomor soal berurutan (1, 2, 3...), pilihan opsi (A, B, C, D, E), dan baris Kunci: di bawah setiap soal.</w:t></w:r>
        </w:p>
        <w:p><w:r><w:t></w:t></w:r></w:p>

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
    </w:body>
</w:document>';
        $zip->addFromString('word/document.xml', $documentXml);

        $zip->close();

        return $tempPath;
    }
}

