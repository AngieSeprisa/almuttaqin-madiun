<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulir Pendaftaran - {{ $pendaftaran->nama_lengkap }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
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

        /* ===== Kertas A4 ===== */
        .sheet-wrap { padding: 24px 0 48px; overflow-x: auto; }
        .paper {
            width: 210mm; min-height: 296mm;
            margin: 0 auto; background: #fff;
            padding: 7mm 13mm 5mm;
            font-size: 8.8pt; line-height: 1.25;
            box-shadow: 0 10px 40px rgba(15,23,42,.25);
        }

        /* ===== Kop surat ===== */
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

        .judul {
            text-align: center; font-weight: 700; font-size: 11pt;
            text-decoration: underline; margin-top: 3mm; letter-spacing: .2px;
        }

        .no-induk-row { display: flex; justify-content: flex-end; align-items: center; gap: 2mm; margin-top: 1.5mm; }
        .no-induk-box { display: flex; border: 1px solid #000; height: 5.2mm; }
        .no-induk-box span { width: 17mm; text-align: center; font-weight: 700; line-height: 4.8mm; border-right: 1px solid #000; }
        .no-induk-box span:last-child { border-right: none; }
        .no-induk-note { font-size: 7.5pt; }

        /* ===== Baris isian ===== */
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

        /* NIK */
        .nik { display: flex; align-items: flex-end; }
        .nik .grp { display: flex; }
        .nik .cell {
            width: 4.7mm; height: 5mm; border: 1px solid #000; margin-right: -1px;
            text-align: center; font-weight: 700; line-height: 4.5mm;
        }
        .nik .dash { width: 3.5mm; text-align: center; font-weight: 700; }

        /* ===== Orang tua ===== */
        .ortu { display: grid; grid-template-columns: 1fr 1fr; column-gap: 8mm; }
        .ortu .l { --lw: 33mm; }
        .ortu .n { width: 5mm; }
        .ortu .sp { width: 5mm; }
        .strike { text-decoration: line-through; color: #555; }
        .sel { font-weight: 700; text-decoration: underline; }

        /* ===== Persyaratan ===== */
        .syarat { display: grid; grid-template-columns: 1fr 1fr; column-gap: 8mm; margin-top: 1mm; }
        .syarat .r { align-items: flex-start; }
        .syarat .lbl { flex: 1; padding-right: 2mm; }
        .syarat .opts { flex: none; white-space: nowrap; }

        /* ===== Tanda tangan ===== */
        .ttd { display: grid; grid-template-columns: 1fr 1fr 1fr; margin-top: 3mm; text-align: center; }
        .ttd .ruang { height: 14mm; }
        .ttd .kurung { letter-spacing: .3px; }

        /* ===== Cetak ===== */
        @media print {
            @page { size: A4 portrait; margin: 0; }
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet-wrap { padding: 0; overflow: visible; }
            .paper { box-shadow: none; margin: 0; width: 210mm; }
        }
    </style>
</head>
<body>
@php
    $p = $pendaftaran;
    $kop = $kop ?? \App\Models\Lembaga::kopForJenjang($p->jenjang);
@endphp

{{-- ===== Toolbar ===== --}}
<div class="toolbar no-print">
    <div class="toolbar-title">
        Pratinjau Formulir Cetak
        <small>{{ $p->nama_lengkap }} • Ukuran kertas A4</small>
    </div>
    <div class="toolbar-actions">
        @php
            $backUrl = route('pendaftaran.show', $p->id);
            if (request('from') === 'siswa' || url()->previous() === route('siswa')) {
                $backUrl = route('siswa');
            } elseif (url()->previous() && url()->previous() !== url()->current()) {
                $backUrl = url()->previous();
            }
        @endphp
        <a href="{{ $backUrl }}" class="tb-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Kembali
        </a>
        <button type="button" class="tb-btn primary" onclick="window.print()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Cetak
        </button>
        <button type="button" class="tb-btn success" id="btnPdf" onclick="unduhPdf()">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span id="btnPdfText">Unduh PDF</span>
        </button>
    </div>
</div>

<div class="sheet-wrap">
    @include('pendaftaran._formulir_sheet', ['pendaftaran' => $p, 'kop' => $kop, 'paperId' => 'paper'])
</div>

<script src="{{ asset('js/vendor/html2pdf.bundle.min.js') }}"></script>
<script>
    function unduhPdf() {
        const btnText = document.getElementById('btnPdfText');
        const btn = document.getElementById('btnPdf');
        const el = document.getElementById('paper');
        btn.disabled = true;
        btnText.textContent = 'Menyiapkan PDF...';

        // Hilangkan bayangan/margin sementara agar hasil PDF presisi A4
        const oldShadow = el.style.boxShadow, oldMargin = el.style.margin;
        el.style.boxShadow = 'none';
        el.style.margin = '0';
        window.scrollTo(0, 0);

        html2pdf().set({
            margin: 0,
            filename: 'Formulir-Pendaftaran-{{ \Illuminate\Support\Str::slug($p->nama_lengkap) }}.pdf',
            image: { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, scrollX: 0, scrollY: 0, backgroundColor: '#ffffff' },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['avoid-all'] }
        }).from(el).save().finally(() => {
            el.style.boxShadow = oldShadow;
            el.style.margin = oldMargin;
            btn.disabled = false;
            btnText.textContent = 'Unduh PDF';
        });
    }

    // Buka dialog cetak otomatis jika diakses dengan ?print=1
    if (new URLSearchParams(location.search).get('print') === '1') {
        window.addEventListener('load', () => setTimeout(() => window.print(), 400));
    }
</script>
</body>
</html>
