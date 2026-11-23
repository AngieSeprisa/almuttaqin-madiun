<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti & Formulir Pendaftaran - {{ $pendaftaran->no_induk }}</title>
    <link rel="icon" type="image/png" href="{{ $kop['logo_url'] }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #d5dbe5;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* ===== Toolbar (tidak ikut tercetak) ===== */
        .toolbar {
            position: sticky; top: 0; z-index: 20;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
            padding: 12px 24px;
            background: #0f172a; color: #fff;
            box-shadow: 0 4px 16px rgba(0,0,0,.25);
        }
        .toolbar-title { font-size: 14px; font-weight: 700; }
        .toolbar-title small { display: block; font-size: 12px; font-weight: 400; color: #94a3b8; }
        .toolbar-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .tb-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 9px 18px; font-size: 13px; font-weight: 600; font-family: inherit;
            border-radius: 9px; border: 1px solid #334155; background: #1e293b; color: #fff;
            cursor: pointer; text-decoration: none; transition: .15s;
        }
        .tb-btn:hover { background: #334155; }
        .tb-btn.primary { background: #0052ff; border-color: #0052ff; }
        .tb-btn.primary:hover { background: #0043d4; }
        .tb-btn.success { background: #059669; border-color: #059669; }
        .tb-btn.success:hover { background: #047857; }
        .tb-btn svg { width: 16px; height: 16px; }

        /* ===== Kertas A4 (2 Halaman) ===== */
        .sheet-wrap { padding: 24px 0 48px; overflow-x: auto; }
        .sheets-container {
            width: 210mm;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .paper {
            width: 210mm; min-height: 296mm;
            margin: 0 auto; background: #fff;
            padding: 7mm 13mm 5mm;
            font-size: 8.8pt; line-height: 1.25;
            box-shadow: 0 10px 40px rgba(15,23,42,.25);
        }
        .paper.page-break {
            page-break-after: always;
            break-after: page;
        }

        /* ===== Kop Surat Resmi ===== */
        .kop { display: flex; align-items: center; gap: 4mm; padding: 0 1mm; }
        .kop-logo { width: 26mm; height: 26mm; object-fit: contain; flex: none; }
        .kop-text { flex: 1; text-align: center; font-family: "Times New Roman", Times, serif; color: #000; }
        .kop-text .yayasan { font-size: 11pt; font-weight: 700; line-height: 1.2; letter-spacing: 0.1px; }
        .kop-text .nama { font-size: 15.5pt; font-weight: 700; line-height: 1.12; margin-top: 0.6mm; }
        .kop-text .alamat { font-size: 9.5pt; line-height: 1.25; margin-top: 1.2mm; }
        .kop-text .kop-kontak { margin-top: 0.4mm; }
        .kop-text .email { color: #1d4ed8; text-decoration: underline; }
        .garis-tipis { height: 0.3mm; background: #000; margin-top: 2mm; }
        .garis-tebal { height: 0.9mm; background: #000; margin-top: 0.5mm; }

        /* ===== Halaman 1: Kartu Bukti Pendaftaran ===== */
        .kartu-title {
            text-align: center;
            font-size: 13pt;
            font-weight: 800;
            margin: 7mm 0 5mm;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .kartu-sub {
            text-align: center;
            font-size: 9.5pt;
            color: #334155;
            margin-top: -3mm;
            margin-bottom: 6mm;
        }
        .reg-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4.5mm 6mm;
            background: #f8fafc;
            border: 1.5px solid #94a3b8;
            border-radius: 6px;
            margin-bottom: 6mm;
        }
        .reg-label { font-size: 8pt; color: #475569; font-weight: 700; letter-spacing: 0.04em; }
        .reg-code { font-size: 18pt; font-weight: 800; font-family: monospace; color: #0052ff; margin-top: 1mm; }
        .reg-status { font-size: 11.5pt; font-weight: 800; color: #0f172a; margin-top: 1mm; }

        .kartu-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10pt;
            line-height: 1.45;
        }
        .kartu-table td {
            padding: 2.8mm 2mm;
            border-bottom: 1px solid #cbd5e1;
            vertical-align: top;
        }
        .kartu-table td.lbl { width: 38%; color: #334155; font-weight: 600; }
        .kartu-table td.val { font-weight: 700; color: #0f172a; }

        .kartu-sign-row {
            display: flex;
            justify-content: space-between;
            margin-top: 14mm;
            font-size: 9.5pt;
        }
        .kartu-sign-col { width: 42%; text-align: center; }
        .kartu-sign-space { height: 20mm; }

        /* ===== Halaman 2: Formulir Pendaftaran A4 (Identik pendaftaran/cetak) ===== */
        .judul {
            text-align: center; font-weight: 700; font-size: 11pt;
            text-decoration: underline; margin-top: 3mm; letter-spacing: .2px;
        }
        .no-induk-row { display: flex; justify-content: flex-end; align-items: center; gap: 2mm; margin-top: 1.5mm; }
        .no-induk-box { display: flex; border: 1px solid #000; height: 5.2mm; }
        .no-induk-box span { min-width: 17mm; padding: 0 1.5mm; text-align: center; font-weight: 700; line-height: 4.8mm; border-right: 1px solid #000; }
        .no-induk-box span:last-child { border-right: none; }
        .no-induk-note { font-size: 7.5pt; }

        .sec { font-weight: 700; font-size: 9.6pt; margin-top: 2.6mm; }
        .ind { padding-left: 6mm; }
        .r { display: flex; align-items: flex-end; margin-top: 0.8mm; min-height: 4mm; }
        .n  { flex: none; width: 5.5mm; }
        .l  { flex: none; width: var(--lw, 38mm); }
        .c  { flex: none; width: 2.5mm; text-align: center; }
        .sp { flex: none; width: 43.5mm; }
        .gap { flex: none; width: 3mm; }
        .t  { flex: none; white-space: nowrap; }
        .v  {
            flex: 1; min-width: 0;
            border-bottom: 1px dotted #000;
            padding: 0 1mm; min-height: 4mm;
            font-weight: 700;
        }
        .w-sm { flex: 0 0 40mm; }
        .box {
            display: inline-block; width: 3.3mm; height: 3.3mm;
            border: 1px solid #000; text-align: center;
            line-height: 2.9mm; font-size: 8.5pt; font-weight: 900;
            vertical-align: -0.6mm; margin-right: 1mm;
        }
        .nik { display: flex; align-items: flex-end; }
        .nik .grp { display: flex; }
        .nik .cell {
            width: 4.7mm; height: 5mm; border: 1px solid #000; margin-right: -1px;
            text-align: center; font-weight: 700; line-height: 4.5mm;
        }
        .nik .dash { width: 3.5mm; text-align: center; font-weight: 700; }
        .ortu { display: grid; grid-template-columns: 1fr 1fr; column-gap: 8mm; }
        .ortu .l { --lw: 33mm; }
        .ortu .n { width: 5mm; }
        .ortu .sp { width: 5mm; }
        .strike { text-decoration: line-through; color: #555; }
        .sel { font-weight: 700; text-decoration: underline; }
        .syarat { display: grid; grid-template-columns: 1fr 1fr; column-gap: 8mm; margin-top: 1mm; }
        .syarat .r { align-items: flex-start; }
        .syarat .lbl { flex: 1; padding-right: 2mm; }
        .syarat .opts { flex: none; white-space: nowrap; }
        .ttd { display: grid; grid-template-columns: 1fr 1fr 1fr; margin-top: 3mm; text-align: center; }
        .ttd .ruang { height: 14mm; }
        .ttd .kurung { letter-spacing: .3px; }

        /* ===== Cetak ===== */
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet-wrap { padding: 0; overflow: visible; }
            .sheets-container { gap: 0; margin: 0; width: 210mm; }
            .paper { box-shadow: none; margin: 0; width: 210mm; min-height: 296mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <div class="toolbar-title">
            Bukti &amp; Formulir Pendaftaran · {{ $pendaftaran->no_induk }}
            <small>{{ $pendaftaran->nama_lengkap }} · 2 Halaman (A4)</small>
        </div>
        <div class="toolbar-actions">
            <a href="{{ route('spmb.bukti', ['slug' => $penerimaan->slug, 'token' => $pendaftaran->public_token]) }}" class="tb-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                Kembali
            </a>
            <button type="button" class="tb-btn primary" onclick="window.print()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Cetak (2 Hal)
            </button>
            <button type="button" class="tb-btn success" id="btnPdf" onclick="unduhPdf()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                <span id="btnPdfText">Unduh PDF</span>
            </button>
        </div>
    </div>

    <div class="sheet-wrap">
        <div class="sheets-container" id="pdfContainer">

            {{-- ===== HALAMAN 1: KARTU BUKTI PENDAFTARAN & PESERTA SELEKSI ===== --}}
            <div class="paper page-break">
                @include('pendaftaran._kop_surat', ['kop' => $kop])

                <div class="kartu-title">Kartu Bukti Pendaftaran &amp; Peserta Seleksi</div>
                <div class="kartu-sub">{{ $penerimaan->judul }} · Tahun {{ $penerimaan->tahun_ajaran }}</div>

                <div class="reg-box">
                    <div>
                        <div class="reg-label">NOMOR REGISTRASI</div>
                        <div class="reg-code">{{ $pendaftaran->no_induk }}</div>
                    </div>
                    <div style="text-align:right;">
                        <div class="reg-label">STATUS</div>
                        <div class="reg-status">{{ strtoupper($pendaftaran->status_pendaftaran) }}</div>
                    </div>
                </div>

                <table class="kartu-table">
                    <tr><td class="lbl">Nama Lengkap</td><td class="val">{{ $pendaftaran->nama_lengkap }}</td></tr>
                    <tr><td class="lbl">Jenjang</td><td class="val">{{ $pendaftaran->jenjang ?? '-' }}</td></tr>
                    <tr><td class="lbl">Jenis Kelamin</td><td class="val">{{ $pendaftaran->jenis_kelamin ?? '-' }}</td></tr>
                    <tr><td class="lbl">NIK / NISN</td><td class="val">{{ $pendaftaran->nik ?: '-' }} / {{ $pendaftaran->nisn ?: '-' }}</td></tr>
                    <tr><td class="lbl">Tempat, Tanggal Lahir</td><td class="val">{{ $pendaftaran->tempat_lahir ?: '-' }}, {{ $pendaftaran->tanggal_lahir?->locale('id')->translatedFormat('d F Y') ?? '-' }}</td></tr>
                    <tr><td class="lbl">Sekolah Asal</td><td class="val">{{ $pendaftaran->nama_sekolah_asal ?: '-' }}</td></tr>
                    <tr><td class="lbl">Nama Orang Tua (Ayah / Ibu)</td><td class="val">{{ $pendaftaran->ayah_nama ?: '-' }} / {{ $pendaftaran->ibu_nama ?: '-' }}</td></tr>
                    <tr><td class="lbl">No. WhatsApp Aktif</td><td class="val">{{ $pendaftaran->pj_biaya_hp ?: '-' }}</td></tr>
                    @if($pendaftaran->gelombang)
                    <tr><td class="lbl">Gelombang</td><td class="val">{{ $pendaftaran->gelombang->nama_gelombang }}</td></tr>
                    @endif
                    @if($penerimaan->jadwal_seleksi)
                    <tr><td class="lbl">Jadwal Seleksi</td><td class="val">{{ $penerimaan->jadwal_seleksi }}</td></tr>
                    @endif
                    @if(!empty($lokasiSeleksi))
                    <tr><td class="lbl">Lokasi Seleksi</td><td class="val">{{ $lokasiSeleksi }}</td></tr>
                    @endif
                    <tr><td class="lbl">Tanggal Daftar</td><td class="val">{{ $pendaftaran->created_at?->locale('id')->translatedFormat('d F Y, H:i') }} WIB</td></tr>
                </table>

                <div class="kartu-sign-row">
                    <div class="kartu-sign-col">
                        <div>Calon Santri / Wali,</div>
                        <div class="kartu-sign-space"></div>
                        <div style="font-weight:700;">( {{ $pendaftaran->nama_lengkap }} )</div>
                    </div>
                    <div class="kartu-sign-col">
                        <div>Madiun, {{ $pendaftaran->created_at?->locale('id')->translatedFormat('d F Y') }}</div>
                        <div>Panitia SPMB,</div>
                        <div class="kartu-sign-space"></div>
                        <div style="font-weight:700;">( .................................... )</div>
                    </div>
                </div>
            </div>

            {{-- ===== HALAMAN 2: FORMULIR PENDAFTARAN SANTRI BARU (FORMAT RESMI A4) ===== --}}
            @include('pendaftaran._formulir_sheet', ['pendaftaran' => $pendaftaran, 'kop' => $kop])

        </div>
    </div>

    <script src="{{ asset('js/vendor/html2pdf.bundle.min.js') }}"></script>
    <script>
        function unduhPdf() {
            var btn = document.getElementById('btnPdf');
            var btnText = document.getElementById('btnPdfText');
            var el = document.getElementById('pdfContainer');
            var papers = el.querySelectorAll('.paper');

            btn.disabled = true;
            btnText.textContent = 'Menyiapkan PDF...';

            var oldGap = el.style.gap;
            el.style.gap = '0';
            papers.forEach(function (p) { p.style.boxShadow = 'none'; });
            window.scrollTo(0, 0);

            html2pdf().set({
                margin: 0,
                filename: 'Bukti-Pendaftaran-{{ \Illuminate\Support\Str::slug($pendaftaran->no_induk ?: $pendaftaran->nama_lengkap) }}.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff' },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
                pagebreak: { mode: ['css'], after: '.page-break' }
            }).from(el).save().finally(function () {
                el.style.gap = oldGap;
                papers.forEach(function (p) { p.style.boxShadow = ''; });
                btn.disabled = false;
                btnText.textContent = 'Unduh PDF';
            });
        }
    </script>
</body>
</html>
