<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Nomor Meja Ujian - Kelas {{ $kelas->nama_kelas }}</title>
    <style>
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
            color: #0f172a;
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
            box-shadow: 0 4px 14px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-print {
            background: #0052FF;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            font-size: 13.5px;
            font-weight: 700;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-print:hover {
            background: #0041cc;
        }
        .meja-page {
            width: 210mm;
            height: 297mm;
            margin: 18px auto;
            background: #ffffff;
            padding: 12mm;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
            page-break-after: always;
            overflow: hidden;
        }
        .meja-page:last-child {
            page-break-after: auto;
        }
        @media print {
            body { background: #ffffff; }
            .no-print-bar { display: none !important; }
            .meja-page {
                margin: 0;
                box-shadow: none;
            }
        }
        .meja-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            grid-template-rows: repeat(4, 1fr);
            gap: 6mm;
            width: 100%;
            height: 100%;
        }
        .meja-card {
            border: 1.5px dashed #334155;
            border-radius: 6px;
            padding: 5mm 5.5mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            background: #ffffff;
            page-break-inside: avoid;
        }
        .meja-card-head {
            border-bottom: 1.5px solid #0f172a;
            padding-bottom: 2.5mm;
            text-align: center;
        }
        .meja-lembaga {
            font-size: 7.5pt;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #334155;
            text-transform: uppercase;
        }
        .meja-judul {
            font-size: 9.5pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin-top: 1px;
        }
        .meja-number-wrap {
            text-align: center;
            padding: 2.5mm 0;
        }
        .meja-number-label {
            font-size: 7pt;
            font-weight: 700;
            letter-spacing: 0.1em;
            color: #475569;
            text-transform: uppercase;
        }
        .meja-number-val {
            font-size: 30pt;
            font-weight: 900;
            line-height: 1.05;
            color: #0f172a;
            letter-spacing: 0.02em;
            margin-top: 1mm;
        }
        .meja-student-box {
            border-top: 1px solid #cbd5e1;
            padding-top: 2.5mm;
            text-align: center;
        }
        .meja-student-name {
            font-size: 10.5pt;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            line-height: 1.25;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .meja-student-meta {
            margin-top: 1.5mm;
            font-size: 8pt;
            color: #334155;
            display: flex;
            justify-content: space-between;
            gap: 6px;
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <span style="font-size:13px; font-weight:600; color:#334155;">
            Kelas {{ $kelas->nama_kelas }} &bull; {{ $siswas->count() }} Kartu Nomor Meja
        </span>
        <button type="button" class="btn-print" onclick="window.print()">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2">
                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                <rect x="6" y="14" width="12" height="8"></rect>
            </svg>
            <span>Cetak Nomor Meja (A4)</span>
        </button>
    </div>

    @php
        $chunks = $siswas->values()->chunk(8);
    @endphp

    @forelse($chunks as $pageIdx => $pageItems)
    <div class="meja-page">
        <div class="meja-grid">
            @foreach($pageItems as $itemIdx => $siswa)
                @php
                    $nomorUrut = ($pageIdx * 8) + $itemIdx + 1;
                    $nomorMeja = ($awalan !== '' ? $awalan : '') . str_pad((string) $nomorUrut, 2, '0', STR_PAD_LEFT);
                @endphp
                <div class="meja-card">
                    <div class="meja-card-head">
                        <div class="meja-lembaga">{{ $lembagaNama }}</div>
                        <div class="meja-judul">{{ $judulUjian }}</div>
                    </div>

                    <div class="meja-number-wrap">
                        <div class="meja-number-label">Nomor Meja</div>
                        <div class="meja-number-val">{{ $nomorMeja }}</div>
                    </div>

                    <div class="meja-student-box">
                        <div class="meja-student-name">{{ $siswa->nama_lengkap }}</div>
                        <div class="meja-student-meta">
                            <span>Kelas: <strong>{{ $kelas->nama_kelas }}</strong></span>
                            <span>{{ $ruangLabel }}</span>
                            <span>NISN: <strong>{{ $siswa->nisn ?: '-' }}</strong></span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="meja-page" style="display:flex; align-items:center; justify-content:center; text-align:center;">
        <div>
            <h2 style="font-size:16pt; margin-bottom:8px;">Belum Ada Siswa Aktif di Kelas {{ $kelas->nama_kelas }}</h2>
            <p style="font-size:10pt; color:#475569;">Silakan pastikan data siswa sudah diplot ke kelas ini terlebih dahulu.</p>
        </div>
    </div>
    @endforelse
</body>
</html>
