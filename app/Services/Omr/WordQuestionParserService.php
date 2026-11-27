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

        // Ambil definisi penomoran otomatis Microsoft Word bila tersedia (word/numbering.xml)
        $numberingXml = $zip->getFromName('word/numbering.xml') ?: null;

        $zip->close();

        // Bersihkan dan ekstrak teks per paragraf (termasuk auto-numbering & Shift+Enter)
        $paragraphs = $this->extractParagraphs($xmlContent, $numberingXml);

        return $this->parseParagraphsToQuestions($paragraphs);
    }

    /**
     * Parsing word/numbering.xml agar daftar bernomor otomatis Word (<w:numPr>) dapat direkonstruksi
     * menjadi awalan "1. ", "2. ", "A. ", "B. ", dst.
     */
    private function parseNumberingDefinitions(?string $numberingXml): array
    {
        if (empty($numberingXml)) {
            return [];
        }

        $dom = new \DOMDocument();
        if (!@$dom->loadXML($numberingXml, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET)) {
            return [];
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $abstractDefs = [];
        foreach ($xpath->query('//w:abstractNum') as $absNode) {
            $absId = $absNode->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'abstractNumId')?->nodeValue
                ?? $absNode->attributes?->getNamedItem('abstractNumId')?->nodeValue;
            if ($absId === null) {
                continue;
            }
            $levels = [];
            foreach ($xpath->query('./w:lvl', $absNode) as $lvlNode) {
                $ilvl = (int) ($lvlNode->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'ilvl')?->nodeValue
                    ?? $lvlNode->attributes?->getNamedItem('ilvl')?->nodeValue ?? 0);
                $numFmtNode = $xpath->query('./w:numFmt', $lvlNode)->item(0);
                $startNode = $xpath->query('./w:start', $lvlNode)->item(0);
                $fmt = $numFmtNode?->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')?->nodeValue
                    ?? $numFmtNode?->attributes?->getNamedItem('val')?->nodeValue ?? 'decimal';
                $start = (int) ($startNode?->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')?->nodeValue
                    ?? $startNode?->attributes?->getNamedItem('val')?->nodeValue ?? 1);
                $levels[$ilvl] = ['fmt' => $fmt, 'start' => max(1, $start)];
            }
            $abstractDefs[$absId] = $levels;
        }

        $numDefs = [];
        foreach ($xpath->query('//w:num') as $numNode) {
            $numId = $numNode->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'numId')?->nodeValue
                ?? $numNode->attributes?->getNamedItem('numId')?->nodeValue;
            $absRefNode = $xpath->query('./w:abstractNumId', $numNode)->item(0);
            $absId = $absRefNode?->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')?->nodeValue
                ?? $absRefNode?->attributes?->getNamedItem('val')?->nodeValue;

            if ($numId === null || $absId === null || !isset($abstractDefs[$absId])) {
                continue;
            }

            $levels = $abstractDefs[$absId];
            foreach ($xpath->query('./w:lvlOverride', $numNode) as $ovNode) {
                $ilvl = (int) ($ovNode->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'ilvl')?->nodeValue
                    ?? $ovNode->attributes?->getNamedItem('ilvl')?->nodeValue ?? 0);
                $soNode = $xpath->query('./w:startOverride', $ovNode)->item(0);
                if ($soNode) {
                    $soVal = (int) ($soNode->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')?->nodeValue
                        ?? $soNode->attributes?->getNamedItem('val')?->nodeValue ?? 1);
                    if (!isset($levels[$ilvl])) {
                        $levels[$ilvl] = ['fmt' => 'decimal', 'start' => max(1, $soVal)];
                    } else {
                        $levels[$ilvl]['start'] = max(1, $soVal);
                    }
                }
            }
            $numDefs[$numId] = $levels;
        }

        return $numDefs;
    }

    /**
     * Format angka/huruf untuk item daftar otomatis Word (<w:numPr>)
     */
    private function formatListMarker(string $fmt, int $val): string
    {
        return match ($fmt) {
            'decimal'     => $val . '. ',
            'upperLetter',
            'lowerLetter' => ($val >= 1 && $val <= 26) ? (chr(64 + $val) . '. ') : '',
            'upperRoman',
            'lowerRoman'  => $this->toRoman($val) . '. ',
            default       => '',
        };
    }

    private function toRoman(int $number): string
    {
        $map = [
            10 => 'X', 9 => 'IX', 5 => 'V', 4 => 'IV', 1 => 'I',
        ];
        $res = '';
        $n = max(1, min(20, $number));
        foreach ($map as $val => $sym) {
            while ($n >= $val) {
                $res .= $sym;
                $n -= $val;
            }
        }
        return $res;
    }

    /**
     * Ekstrak teks paragraf dari XML Word menggunakan DOMDocument (aman terhadap <w:p/>, nested XML, <w:br/>, dan auto-numbering)
     */
    private function extractParagraphs(string $xmlContent, ?string $numberingXml = null): array
    {
        $paragraphs = [];
        $numDefs = $this->parseNumberingDefinitions($numberingXml);
        $listCounters = []; // [numId][ilvl] => int

        $dom = new \DOMDocument();
        if (@$dom->loadXML($xmlContent, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET)) {
            $xpath = new \DOMXPath($dom);
            $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
            $xpath->registerNamespace('mc', 'http://schemas.openxmlformats.org/markup-compatibility/2006');

            // Ambil semua <w:p> di dalam body (kecuali yang berada di dalam mc:Fallback agar tidak duplikat)
            $pNodes = $xpath->query('//w:body//w:p[not(ancestor::mc:Fallback)]');

            foreach ($pNodes as $pNode) {
                // Ekstrak teks dengan memisahkan <w:br/> dan <w:cr/> menjadi baris tersendiri
                $lines = [''];
                $this->collectParagraphLines($pNode, $lines);

                // Bersihkan whitespace (termasuk NBSP \u00A0 & zero-width space)
                $cleanLines = [];
                foreach ($lines as $rawLine) {
                    $clean = preg_replace('/[\s\x{00A0}\x{200B}\x{FEFF}]+/u', ' ', $rawLine);
                    $clean = trim((string) $clean);
                    if ($clean !== '') {
                        $cleanLines[] = $clean;
                    }
                }

                if (empty($cleanLines)) {
                    continue;
                }

                // Cek penomoran otomatis Word (<w:pPr><w:numPr>)
                $prefix = '';
                $numPrNode = $xpath->query('./w:pPr/w:numPr', $pNode)->item(0);
                if ($numPrNode) {
                    $numIdNode = $xpath->query('./w:numId', $numPrNode)->item(0);
                    $ilvlNode = $xpath->query('./w:ilvl', $numPrNode)->item(0);
                    $numId = $numIdNode?->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')?->nodeValue
                        ?? $numIdNode?->attributes?->getNamedItem('val')?->nodeValue;
                    $ilvl = (int) ($ilvlNode?->attributes?->getNamedItemNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'val')?->nodeValue
                        ?? $ilvlNode?->attributes?->getNamedItem('val')?->nodeValue ?? 0);

                    if ($numId !== null && $numId !== '0') {
                        $def = $numDefs[$numId][$ilvl] ?? [
                            'fmt'   => $ilvl === 0 ? 'decimal' : 'upperLetter',
                            'start' => 1,
                        ];

                        if (!isset($listCounters[$numId][$ilvl])) {
                            $listCounters[$numId][$ilvl] = $def['start'];
                        } else {
                            $listCounters[$numId][$ilvl]++;
                        }

                        // Reset level anak pada numId yang sama (misal ilvl=0 maju ke soal berikutnya => opsi ilvl=1 reset ke A)
                        foreach (array_keys($listCounters[$numId]) as $lvlKey) {
                            if ($lvlKey > $ilvl) {
                                unset($listCounters[$numId][$lvlKey]);
                            }
                        }

                        // Jika sebuah item bernomor decimal baru muncul, reset semua daftar huruf (opsi A-E) yang terpisah
                        if ($def['fmt'] === 'decimal') {
                            foreach ($listCounters as $otherNumId => $lvls) {
                                foreach ($lvls as $otherLvl => $v) {
                                    $otherFmt = $numDefs[$otherNumId][$otherLvl]['fmt'] ?? ($otherLvl > 0 ? 'upperLetter' : 'decimal');
                                    if (in_array($otherFmt, ['upperLetter', 'lowerLetter'], true)) {
                                        unset($listCounters[$otherNumId][$otherLvl]);
                                    }
                                }
                            }
                        }

                        $firstLine = $cleanLines[0];
                        $alreadyHasPrefix =
                            ($def['fmt'] === 'decimal' && preg_match('/^(?:(?:no|soal)\.?\s*)?\d+[\.\)\:\-]/i', $firstLine)) ||
                            (in_array($def['fmt'], ['upperLetter', 'lowerLetter'], true) && preg_match('/^\(?[A-Ea-e][\.\)\:\-]/', $firstLine)) ||
                            (in_array($def['fmt'], ['upperRoman', 'lowerRoman'], true) && preg_match('/^[IVX]+[\.\)]/i', $firstLine));

                        if (!$alreadyHasPrefix) {
                            $prefix = $this->formatListMarker($def['fmt'], $listCounters[$numId][$ilvl]);
                        }
                    }
                }

                if ($prefix !== '') {
                    $cleanLines[0] = $prefix . $cleanLines[0];
                }

                foreach ($cleanLines as $cl) {
                    $paragraphs[] = $cl;
                }
            }

            if (!empty($paragraphs)) {
                return $paragraphs;
            }
        }

        // Fallback regex bila DOMDocument gagal memuat XML non-standar
        $cleanXml = preg_replace('/(<\/?)w:/', '$1', $xmlContent);
        if (preg_match_all('/<p(?:\s[^>]*)?>((?:(?!<p[\s>]).)*?)<\/p>/is', $cleanXml, $matches)) {
            foreach ($matches[1] as $pXml) {
                $pXml = preg_replace('/<tab\/>/i', '  ', $pXml);
                $segments = preg_split('/<(?:br|cr)\b[^>]*\/?>/i', $pXml);
                foreach ($segments as $seg) {
                    if (preg_match_all('/<t\b[^>]*>(.*?)<\/t>/is', $seg, $tMatches)) {
                        $text = implode('', $tMatches[1]);
                        $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
                        $text = trim((string) preg_replace('/[\s\x{00A0}\x{200B}\x{FEFF}]+/u', ' ', $text));
                        if ($text !== '') {
                            $paragraphs[] = $text;
                        }
                    }
                }
            }
        }

        return $paragraphs;
    }

    /**
     * Kumpulkan teks dari node <w:p> secara rekursif sambil menghormati <w:tab/>, <w:br/>, <w:cr/>, dan skip <mc:Fallback>.
     */
    private function collectParagraphLines(\DOMNode $node, array &$lines): void
    {
        foreach ($node->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $local = $child->localName;
            // Skip properti paragraf, teks terhapus (track changes), fallback duplikat, dan nested paragraf (diproses tersendiri)
            if (in_array($local, ['pPr', 'rPr', 'del', 'Fallback', 'p'], true)) {
                continue;
            }

            if ($local === 't') {
                $lines[count($lines) - 1] .= $child->textContent;
            } elseif ($local === 'tab') {
                $lines[count($lines) - 1] .= '  ';
            } elseif ($local === 'br' || $local === 'cr') {
                $lines[] = '';
            } else {
                $this->collectParagraphLines($child, $lines);
            }
        }
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
                if (preg_match('/^(?:kunci\s*jawaban|jawaban\s*benar|kunci|jawaban|ans|key)\s*[:=\-]?\s*\(?([A-Ea-e])\)?(?:\b|\.)/i', $line, $km)) {
                    if ($currentSoal !== null) {
                        $currentSoal['kunci'] = strtoupper($km[1]);
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

            // 3. Cek baris nomor soal baru (misal: "1.", "1)", "No. 1.", "Soal 1.") — hindari angka desimal/ribuan seperti "3.5" atau "1.000"
            if (preg_match('/^(?:(?:no|soal)\.?\s*)?(\d{1,3})([\.\)\:\-])(?:\s+(.*)|$|(?=\D)(.*))$/iu', $line, $qm)) {
                $nomor = (int) $qm[1];
                $teks = trim(($qm[3] ?? '') !== '' ? $qm[3] : ($qm[4] ?? ''));

                if ($nomor >= 1 && $nomor <= 200) {
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

        // Normalisasi nomor PG menjadi 1..N agar selalu selaras dengan LJK dan kunci jawaban
        $pgList = [];
        $pgSeq = 1;
        foreach ($soalsPg as $pg) {
            $pg['nomor'] = $pgSeq++;
            $pgList[] = $pg;
        }

        // Normalisasi nomor essay menjadi 1..N agar selalu selaras dengan nomor Essay pada LJK
        $normalizedEssay = [];
        $essaySeq = 1;
        foreach ($soalsEssay as $es) {
            $es['nomor'] = $essaySeq++;
            $normalizedEssay[] = $es;
        }

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
            'pertanyaan'      => trim((string) ($s['pertanyaan'] ?? '')),
            'opsi_a'          => $isEssay ? null : trim((string) ($s['opsi']['A'] ?? '')),
            'opsi_b'          => $isEssay ? null : trim((string) ($s['opsi']['B'] ?? '')),
            'opsi_c'          => $isEssay ? null : trim((string) ($s['opsi']['C'] ?? '')),
            'opsi_d'          => $isEssay ? null : trim((string) ($s['opsi']['D'] ?? '')),
            'opsi_e'          => $isEssay ? null : (isset($s['opsi']['E']) ? trim((string) $s['opsi']['E']) : null),
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

