<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak LJK - {{ $ujian->nama_ujian }} ({{ $ujian->kelas?->nama_kelas ?? 'Kelas' }})</title>
    <style>
        /* CSS RESET & A4 PAGE SETUP */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #cbd5e1;
            color: #000000;
        }

        @page {
            size: A4 portrait;
            margin: 0;
        }

        .no-print-bar {
            position: fixed;
            top: 16px;
            right: 16px;
            z-index: 10000;
            background: #ffffff;
            padding: 10px 18px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.25);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-print {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            font-size: 13.5px;
            font-weight: 700;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-print:hover { background: #1d4ed8; }

        /* LEMBAR A4 PERSIS 210mm x 297mm */
        .ljk-sheet {
            width: 210mm;
            height: 297mm;
            margin: 20px auto;
            background: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            page-break-after: always;
        }

        @media print {
            body { background: #ffffff; }
            .no-print-bar { display: none !important; }
            .ljk-sheet {
                margin: 0;
                box-shadow: none;
                page-break-after: always;
            }
        }

        /* 4 TITIK SUDUT HITAM PEKAT (FIDUCIAL MARKERS 10x10 mm) */
        .fiducial-marker {
            position: absolute;
            width: 10mm;
            height: 10mm;
            background: #000000 !important;
            z-index: 99;
        }
        .marker-tl { top: 12mm; left: 12mm; }
        .marker-tr { top: 12mm; right: 12mm; }
        .marker-bl { bottom: 12mm; left: 12mm; }
        .marker-br { bottom: 12mm; right: 12mm; }

        /* AREA KONTEN DI DALAM MARKER */
        .ljk-content {
            position: absolute;
            top: 14mm;
            left: 24mm;
            right: 24mm;
            bottom: 24mm;
        }

        /* HEADER IDENTITAS & QR */
        .ljk-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header-title-box h1 {
            font-size: 13pt;
            font-weight: 800;
            letter-spacing: 0.04em;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .header-title-box h2 {
            font-size: 11pt;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 6px;
        }
        .info-table {
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .info-table td {
            padding: 1.5px 4px 1.5px 0;
            vertical-align: top;
        }
        .info-table .info-val {
            font-weight: bold;
        }

        /* QR CODE BOX (POJOK KANAN ATAS) */
        .qr-box-wrap {
            text-align: center;
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            background: #ffffff;
            margin-right: 6mm;
            border-radius: 4px;
        }
        .qr-box-wrap .qr-caption {
            font-size: 6.8pt;
            font-weight: bold;
            font-family: monospace;
            margin-top: 3px;
            letter-spacing: 0.05em;
            color: #0f172a;
        }

        /* PETUNJUK PENGISIAN */
        .instruction-box {
            border: 1px solid #334155;
            padding: 4px 8px;
            font-size: 7pt;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .instruction-samples {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: bold;
        }
        .sample-bubble {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 1px solid #000;
            font-size: 7pt;
            margin-right: 3px;
        }
        .sample-bubble.filled {
            background: #000 !important;
            color: #fff;
        }

        /* OMR BUBBLE GRID */
        .omr-grid-container {
            display: grid;
            grid-template-columns: repeat({{ count($bubbleCoords) <= 40 ? 4 : 5 }}, 1fr);
            gap: 12px;
            margin-top: 6px;
        }
        .omr-column {
            border: 1px solid #94a3b8;
            border-radius: 4px;
            overflow: hidden;
            background: #ffffff;
        }
        .omr-col-header {
            background: #000000;
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            text-align: center;
            padding: 3px 0;
            letter-spacing: 0.04em;
        }
        .omr-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 3.5px 6px;
            border-bottom: 1px dashed #cbd5e1;
        }
        .omr-row:last-child {
            border-bottom: none;
        }
        .omr-row:nth-child(5n+1) {
            background: #f1f5f9; /* shading pembantu per 5 nomor */
        }
        .omr-qnum {
            font-size: 8pt;
            font-weight: bold;
            color: #0f172a;
            min-width: 20px;
        }
        .omr-bubbles {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .omr-bubble {
            width: 17px;
            height: 17px;
            border-radius: 50%;
            border: 1.2px solid #000000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 7pt;
            font-weight: bold;
            color: #000000;
            background: #ffffff;
        }

        /* AREA JAWABAN ESSAY DI HALAMAN 1 (DI BAWAH GRID PG TANPA MENGGESER OMR) */
        .essay-section-p1 {
            margin-top: 8px;
            border: 1px solid #94a3b8;
            border-radius: 4px;
            padding: 5px 8px 6px;
            background: #ffffff;
        }
        .essay-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #64748b;
            padding-bottom: 3px;
            margin-bottom: 5px;
            font-size: 7.8pt;
            font-weight: 800;
            letter-spacing: 0.03em;
            color: #0f172a;
            text-transform: uppercase;
        }
        .essay-section-note {
            font-size: 6.8pt;
            font-weight: 600;
            color: #475569;
            text-transform: none;
            letter-spacing: 0;
        }
        .essay-item {
            margin-bottom: 4px;
        }
        .essay-item:last-child {
            margin-bottom: 0;
        }
        .essay-line-row {
            display: flex;
            align-items: flex-end;
            gap: 5px;
            height: 5.3mm;
        }
        .essay-num-label {
            font-size: 8pt;
            font-weight: 700;
            color: #0f172a;
            min-width: 18px;
            line-height: 1;
            padding-bottom: 1px;
        }
        .essay-write-line {
            flex: 1;
            height: 100%;
            border-bottom: 0.75pt dotted #64748b;
        }

        /* HALAMAN BELAKANG LANJUTAN ESSAY (OTOMATIS BILA RUANG HALAMAN 1 KURANG) */
        .ljk-sheet-essay .essay-back-content {
            position: absolute;
            top: 14mm;
            left: 18mm;
            right: 18mm;
            bottom: 16mm;
        }
        .essay-back-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 12px;
        }
        .essay-back-header h2 {
            font-size: 11pt;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
            color: #0f172a;
        }
        .essay-back-meta {
            font-size: 8.2pt;
            color: #334155;
            margin-top: 3px;
        }
        .essay-back-item {
            margin-bottom: 8px;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 5px 9px 7px;
        }
        .essay-back-line-row {
            display: flex;
            align-items: flex-end;
            gap: 6px;
            height: 6mm;
        }

        /* FOOTER / TANDA TANGAN */
        .ljk-footer {
            position: absolute;
            bottom: 4mm;
            left: 0;
            right: 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 7.5pt;
            color: #334155;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
        }
    </style>
</head>
<body>

    @php
        $activeCodeType = ($tipeKode ?? 'qr') === 'barcode' ? 'barcode' : 'qr';
        $essayLayout = $essayLayout ?? ['has_essay' => false, 'page1_numbers' => [], 'page1_lines' => 0, 'extra_pages' => [], 'total_pages_per_siswa' => 1];
    @endphp

    <div class="no-print-bar">
        <span style="font-size:13px; font-weight:600; color:#334155;">
            {{ count($siswas) }} Siswa Siap Cetak
            @if(($essayLayout['total_pages_per_siswa'] ?? 1) > 1)
                &bull; {{ $essayLayout['total_pages_per_siswa'] }} Hal/Siswa (Otomatis Lanjut Halaman Belakang)
            @elseif($essayLayout['has_essay'] ?? false)
                &bull; 1 Halaman A4 (PG + Essay)
            @endif
        </span>

        <div style="display:inline-flex; align-items:center; background:#f1f5f9; padding:3px; border-radius:8px; border:1px solid #cbd5e1;">
            <button type="button" id="btnSwitchQr" onclick="switchIdentCode('qr')" style="border:none; background:{{ $activeCodeType === 'qr' ? '#2563eb' : 'transparent' }}; color:{{ $activeCodeType === 'qr' ? '#ffffff' : '#475569' }}; font-size:12px; font-weight:700; padding:6px 12px; border-radius:6px; cursor:pointer;">
                QR Code
            </button>
            <button type="button" id="btnSwitchBarcode" onclick="switchIdentCode('barcode')" style="border:none; background:{{ $activeCodeType === 'barcode' ? '#2563eb' : 'transparent' }}; color:{{ $activeCodeType === 'barcode' ? '#ffffff' : '#475569' }}; font-size:12px; font-weight:700; padding:6px 12px; border-radius:6px; cursor:pointer;">
                Barcode Garis
            </button>
        </div>

        <button type="button" class="btn-print" onclick="window.print()">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>Cetak Seluruh LJK (A4)</span>
        </button>
    </div>

    @php
        $hasPgGrid = count($bubbleCoords) > 0;
        $numCols = $hasPgGrid ? (count($bubbleCoords) <= 40 ? 4 : 5) : 4;
        $perCol = $hasPgGrid ? (int) ceil(count($bubbleCoords) / $numCols) : 0;
        $opsiList = $ujian->jumlah_opsi == 5 ? ['A', 'B', 'C', 'D', 'E'] : ['A', 'B', 'C', 'D'];
    @endphp

    @foreach($siswas as $siswa)
    <div class="ljk-sheet">
        {{-- 4 SUDUT KOTAK REFERENSI HITAM PRESISI (FIDUCIAL MARKERS) --}}
        <div class="fiducial-marker marker-tl"></div>
        <div class="fiducial-marker marker-tr"></div>
        <div class="fiducial-marker marker-bl"></div>
        <div class="fiducial-marker marker-br"></div>

        <div class="ljk-content">
            {{-- HEADER IDENTITAS SEKOLAH & SISWA --}}
            <div class="ljk-header-row">
                <div class="header-title-box">
                    <h1>YAYASAN PONDOK PESANTREN MUTTAQIN</h1>
                    <h2>LEMBAR JAWAB UJIAN KOMPUTER (LJK)</h2>

                    <table class="info-table">
                        <tr>
                            <td style="width:75px; color:#475569;">Nama Ujian</td>
                            <td style="width:8px;">:</td>
                            <td class="info-val" style="color:#000;">{{ $ujian->nama_ujian }}</td>
                            <td style="width:30px;"></td>
                            <td style="width:75px; color:#475569;">Nama Siswa</td>
                            <td style="width:8px;">:</td>
                            <td class="info-val" style="font-size:9pt; text-transform:uppercase;">{{ $siswa->nama_lengkap }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569;">Mata Pelajaran</td>
                            <td>:</td>
                            <td class="info-val">{{ $ujian->mapel?->mata_pelajaran ?? '-' }}</td>
                            <td></td>
                            <td style="color:#475569;">NISN / No. Induk</td>
                            <td>:</td>
                            <td class="info-val" style="font-family:monospace;">{{ $siswa->nisn ?: '-' }}</td>
                        </tr>
                        <tr>
                            <td style="color:#475569;">Kelas / Ruang</td>
                            <td>:</td>
                            <td class="info-val">{{ $ujian->kelas?->nama_kelas ?? '-' }}</td>
                            <td></td>
                            <td style="color:#475569;">Tanggal Ujian</td>
                            <td>:</td>
                            <td class="info-val">{{ $ujian->tanggal_ujian?->translatedFormat('d F Y') ?? date('d/m/Y') }}</td>
                        </tr>
                    </table>
                </div>

                {{-- PILIHAN QR CODE ATAU BARCODE DI POJOK KANAN ATAS (TIDAK DICETAK KEDUANYA) --}}
                <div class="qr-box-wrap">
                    <div class="code-view-qr" style="width:28mm; height:28mm; display:{{ $activeCodeType === 'qr' ? 'flex' : 'none' }}; align-items:center; justify-content:center; margin:0 auto;">
                        {!! $qrCodes[$siswa->id] ?? '' !!}
                    </div>
                    <div class="code-view-barcode" style="width:42mm; height:20mm; display:{{ $activeCodeType === 'barcode' ? 'flex' : 'none' }}; align-items:center; justify-content:center; margin:0 auto;">
                        {!! $barcodes[$siswa->id] ?? '' !!}
                    </div>
                    <div class="qr-caption">ID: {{ $ujian->kode_ujian }}-{{ str_pad($siswa->id, 4, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>

            {{-- KOTAK PETUNJUK & TATA TERTIB UJIAN --}}
            <div class="instruction-box">
                <div style="flex:1;">
                    <strong style="color:#0f172a;">PETUNJUK & TATA TERTIB:</strong>
                    <div style="font-size:6.8pt; color:#334155; line-height:1.35; margin-top:2px; white-space:pre-line;">{{ $ujian->peraturan_efektif }}</div>
                </div>
                @if($hasPgGrid)
                <div class="instruction-samples" style="flex-shrink:0;">
                    <span>Benar: <span class="sample-bubble filled">A</span></span>
                    <span>Salah: <span class="sample-bubble">✓</span> <span class="sample-bubble">✗</span> <span class="sample-bubble">●</span></span>
                </div>
                @endif
            </div>

            {{-- OMR BUBBLE GRID (PILIHAN GANDA) --}}
            @if($hasPgGrid)
            <div class="omr-grid-container">
                @for($c = 0; $c < $numCols; $c++)
                    @php
                        $startNo = ($c * $perCol) + 1;
                        $endNo = min(count($bubbleCoords), ($c + 1) * $perCol);
                    @endphp
                    <div class="omr-column">
                        <div class="omr-col-header">NOMOR {{ $startNo }} - {{ $endNo }}</div>

                        @for($no = $startNo; $no <= $endNo; $no++)
                        <div class="omr-row">
                            <span class="omr-qnum">{{ str_pad($no, 2, '0', STR_PAD_LEFT) }}.</span>
                            <div class="omr-bubbles">
                                @foreach($opsiList as $opsi)
                                <span class="omr-bubble">{{ $opsi }}</span>
                                @endforeach
                            </div>
                        </div>
                        @endfor
                    </div>
                @endfor
            </div>
            @endif

            {{-- AREA JAWABAN ESSAY PADA HALAMAN 1 (OTOMATIS SESUAI RUANG TERSEDIA) --}}
            @if(!empty($essayLayout['page1_numbers']))
            <div class="essay-section-p1">
                <div class="essay-section-header">
                    <span>{{ $hasPgGrid ? 'BAGIAN II — JAWABAN ESSAY / URAIAN' : 'LEMBAR JAWABAN ESSAY / URAIAN' }}</span>
                    @if(!empty($essayLayout['extra_pages']))
                        <span class="essay-section-note">Nomor {{ min($essayLayout['page1_numbers']) }}–{{ max($essayLayout['page1_numbers']) }} (Berlanjut di halaman belakang)</span>
                    @endif
                </div>

                @foreach($essayLayout['page1_numbers'] as $essayNo)
                <div class="essay-item">
                    @for($ln = 0; $ln < $essayLayout['page1_lines']; $ln++)
                    <div class="essay-line-row">
                        <span class="essay-num-label">{{ $ln === 0 ? $essayNo . '.' : '' }}</span>
                        <div class="essay-write-line"></div>
                    </div>
                    @endfor
                </div>
                @endforeach
            </div>
            @endif

            {{-- FOOTER / CATATAN KALIBRASI --}}
            <div class="ljk-footer">
                <div>
                    Sistem Koreksi Otomatis OMR &bull; Lembar Asli Tercetak Otomatis &bull; Pastikan 4 kotak sudut hitam tidak tertutup.
                </div>
                <div style="text-align:right;">
                    Tanda Tangan Siswa: _____________________
                </div>
            </div>
        </div>
    </div>

    {{-- HALAMAN BELAKANG OTOMATIS BILA SOAL ESSAY MELEBIHI RUANG HALAMAN 1 --}}
    @if(!empty($essayLayout['extra_pages']))
        @foreach($essayLayout['extra_pages'] as $extraPage)
        <div class="ljk-sheet ljk-sheet-essay">
            <div class="essay-back-content">
                <div class="essay-back-header">
                    <div>
                        <h2>LANJUTAN JAWABAN ESSAY / URAIAN</h2>
                        <div class="essay-back-meta">
                            <strong>Nama:</strong> {{ $siswa->nama_lengkap }} &bull;
                            <strong>NISN:</strong> {{ $siswa->nisn ?: '-' }} &bull;
                            <strong>Kelas:</strong> {{ $ujian->kelas?->nama_kelas ?? '-' }} &bull;
                            <strong>Mapel:</strong> {{ $ujian->mapel?->mata_pelajaran ?? '-' }}
                        </div>
                    </div>
                    <div style="text-align:right; font-size:8pt; font-weight:700; color:#334155;">
                        <div>{{ $ujian->kode_ujian }}</div>
                        <div>Halaman {{ $extraPage['page_number'] }} dari {{ $essayLayout['total_pages_per_siswa'] }}</div>
                    </div>
                </div>

                @foreach($extraPage['numbers'] as $essayNo)
                <div class="essay-back-item">
                    @for($ln = 0; $ln < $extraPage['lines']; $ln++)
                    <div class="essay-back-line-row">
                        <span class="essay-num-label">{{ $ln === 0 ? $essayNo . '.' : '' }}</span>
                        <div class="essay-write-line"></div>
                    </div>
                    @endfor
                </div>
                @endforeach

                <div class="ljk-footer" style="bottom:0;">
                    <div>
                        Lanjutan Jawaban Essay &bull; {{ $ujian->nama_ujian }} &bull; Halaman {{ $extraPage['page_number'] }}/{{ $essayLayout['total_pages_per_siswa'] }}
                    </div>
                    <div style="text-align:right;">
                        Paraf Pengawas: _____________________
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    @endif
    @endforeach

    <script>
        function switchIdentCode(mode) {
            const isBarcode = (mode === 'barcode');
            document.querySelectorAll('.code-view-qr').forEach(el => {
                el.style.display = isBarcode ? 'none' : 'flex';
            });
            document.querySelectorAll('.code-view-barcode').forEach(el => {
                el.style.display = isBarcode ? 'flex' : 'none';
            });

            const btnQr = document.getElementById('btnSwitchQr');
            const btnBc = document.getElementById('btnSwitchBarcode');
            if (btnQr && btnBc) {
                btnQr.style.background = isBarcode ? 'transparent' : '#2563eb';
                btnQr.style.color = isBarcode ? '#475569' : '#ffffff';
                btnBc.style.background = isBarcode ? '#2563eb' : 'transparent';
                btnBc.style.color = isBarcode ? '#ffffff' : '#475569';
            }
        }
    </script>
</body>
</html>

