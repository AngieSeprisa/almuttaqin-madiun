<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kartu Peserta - {{ $pendaftaran->no_induk }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            background: #E2E8F0;
            color: #0F172A;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar {
            position: sticky; top: 0; z-index: 10;
            display: flex; justify-content: space-between; align-items: center; gap: 12px;
            padding: 12px 24px;
            background: #0F172A; color: #FFFFFF;
        }
        .tb-btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px; border: 1px solid #334155;
            background: #1E293B; color: #FFFFFF; font-size: 13px; font-weight: 600;
            text-decoration: none; cursor: pointer;
        }
        .tb-btn.primary { background: #0052FF; border-color: #0052FF; }
        .sheet {
            max-width: 190mm;
            margin: 24px auto;
            background: #FFFFFF;
            padding: 14mm 16mm;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.16);
        }
        .kop {
            display: flex; align-items: center; gap: 14px;
            padding-bottom: 12px; border-bottom: 2.5px solid #0F172A;
        }
        .kop img { width: 58px; height: 58px; object-fit: contain; }
        .kop-title { font-size: 16px; font-weight: 800; }
        .kop-sub { font-size: 12px; color: #475569; margin-top: 2px; }
        .doc-title {
            text-align: center; font-size: 15px; font-weight: 800;
            margin: 18px 0 14px; text-transform: uppercase; letter-spacing: 0.04em;
        }
        .reg-box {
            display: flex; justify-content: space-between; align-items: center;
            padding: 12px 16px; background: #F8FAFC; border: 1.5px solid #CBD5E1; border-radius: 8px;
            margin-bottom: 16px;
        }
        .reg-code { font-size: 20px; font-weight: 800; font-family: monospace; color: #0052FF; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        td { padding: 8px 6px; border-bottom: 1px solid #E5E7EB; vertical-align: top; }
        td.lbl { width: 38%; color: #475569; font-weight: 600; }
        td.val { font-weight: 700; color: #0F172A; }
        .sign-row {
            display: flex; justify-content: space-between; margin-top: 32px; font-size: 12.5px;
        }
        .sign-col { width: 42%; text-align: center; }
        .sign-space { height: 56px; }
        @media print {
            body { background: #FFFFFF; }
            .toolbar { display: none; }
            .sheet { margin: 0; box-shadow: none; border-radius: 0; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div style="font-size:14px; font-weight:700;">Kartu Peserta · {{ $pendaftaran->no_induk }}</div>
        <div style="display:flex; gap:8px;">
            <a href="{{ route('spmb.bukti', ['slug' => $penerimaan->slug, 'token' => $pendaftaran->public_token]) }}" class="tb-btn">Kembali</a>
            <button type="button" onclick="window.print()" class="tb-btn primary">Cetak / Simpan PDF</button>
        </div>
    </div>

    <div class="sheet">
        <div class="kop">
            <img src="{{ asset('images/logo.png') }}" alt="Logo">
            <div>
                <div class="kop-title">YAYASAN PONDOK PESANTREN AL MUTTAQIN MADIUN</div>
                <div class="kop-sub">{{ $penerimaan->judul }} · Tahun {{ $penerimaan->tahun_ajaran }}</div>
            </div>
        </div>

        <div class="doc-title">Kartu Bukti Pendaftaran & Peserta Seleksi</div>

        <div class="reg-box">
            <div>
                <div style="font-size:11px; color:#64748B; font-weight:700;">NOMOR REGISTRASI</div>
                <div class="reg-code">{{ $pendaftaran->no_induk }}</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:11px; color:#64748B; font-weight:700;">STATUS</div>
                <div style="font-size:14px; font-weight:800;">{{ strtoupper($pendaftaran->status_pendaftaran) }}</div>
            </div>
        </div>

        <table>
            <tr><td class="lbl">Nama Lengkap</td><td class="val">{{ $pendaftaran->nama_lengkap }}</td></tr>
            <tr><td class="lbl">Jenjang</td><td class="val">{{ $pendaftaran->jenjang ?? '-' }}</td></tr>
            <tr><td class="lbl">Jenis Kelamin</td><td class="val">{{ $pendaftaran->jenis_kelamin ?? '-' }}</td></tr>
            <tr><td class="lbl">NIK / NISN</td><td class="val">{{ $pendaftaran->nik ?? '-' }} / {{ $pendaftaran->nisn ?? '-' }}</td></tr>
            <tr><td class="lbl">Tempat, Tanggal Lahir</td><td class="val">{{ $pendaftaran->tempat_lahir ?? '-' }}, {{ $pendaftaran->tanggal_lahir?->translatedFormat('d F Y') ?? '-' }}</td></tr>
            <tr><td class="lbl">Sekolah Asal</td><td class="val">{{ $pendaftaran->nama_sekolah_asal ?? '-' }}</td></tr>
            <tr><td class="lbl">Nama Orang Tua (Ayah / Ibu)</td><td class="val">{{ $pendaftaran->ayah_nama ?? '-' }} / {{ $pendaftaran->ibu_nama ?? '-' }}</td></tr>
            <tr><td class="lbl">No. WhatsApp Aktif</td><td class="val">{{ $pendaftaran->pj_biaya_hp ?? '-' }}</td></tr>
            @if($pendaftaran->gelombang)
            <tr><td class="lbl">Gelombang</td><td class="val">{{ $pendaftaran->gelombang->nama_gelombang }}</td></tr>
            @endif
            @if($penerimaan->jadwal_seleksi)
            <tr><td class="lbl">Jadwal Seleksi</td><td class="val">{{ $penerimaan->jadwal_seleksi }}</td></tr>
            @endif
            @if($penerimaan->lokasi_seleksi)
            <tr><td class="lbl">Lokasi Seleksi</td><td class="val">{{ $penerimaan->lokasi_seleksi }}</td></tr>
            @endif
            <tr><td class="lbl">Tanggal Daftar</td><td class="val">{{ $pendaftaran->created_at?->translatedFormat('d F Y, H:i') }} WIB</td></tr>
        </table>

        <div class="sign-row">
            <div class="sign-col">
                <div>Calon Santri / Wali,</div>
                <div class="sign-space"></div>
                <div style="font-weight:700;">( {{ $pendaftaran->nama_lengkap }} )</div>
            </div>
            <div class="sign-col">
                <div>Panitia SPMB,</div>
                <div class="sign-space"></div>
                <div style="font-weight:700;">( ................................ )</div>
            </div>
        </div>
    </div>
</body>
</html>
