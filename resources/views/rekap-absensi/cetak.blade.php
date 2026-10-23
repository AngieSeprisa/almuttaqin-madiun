<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Guru - {{ $lembaga ? $lembaga->nama : 'Al Muttaqin' }} - {{ $namaBulan }} {{ $tahun }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #111;
            margin: 0;
            padding: 0;
            font-size: 11pt;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .header h2 {
            font-size: 12pt;
            font-weight: normal;
            margin: 4px 0 0 0;
        }
        .title-section {
            text-align: center;
            margin-bottom: 16px;
        }
        .title-section h3 {
            font-size: 12pt;
            font-weight: bold;
            margin: 0;
            text-decoration: underline;
        }
        .title-section p {
            font-size: 10pt;
            margin: 4px 0 0 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 9.5pt;
        }
        th, td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .footer-sig {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }
        .sig-box {
            width: 220px;
            text-align: center;
        }
        .sig-space {
            height: 60px;
        }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="background:#f4f4f4; padding:10px 16px; margin-bottom:15px; border-radius:6px; display:flex; justify-content:space-between; align-items:center;">
        <span style="font-family:sans-serif; font-size:13px; font-weight:600;">Pratinjau Cetak Lembar Rekap Absensi Guru</span>
        <button onclick="window.print()" style="padding:6px 16px; background:#0052FF; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:bold;">
            Cetak Dokumen
        </button>
    </div>

    <div class="header">
        <h1>PONDOK PESANTREN AL MUTTAQIN</h1>
        <h2>{{ $lembaga ? strtoupper($lembaga->nama) : 'REKAPITULASI PRESENSI GURU & ASATIDZ' }}</h2>
    </div>

    <div class="title-section">
        <h3>REKAPITULASI KEHADIRAN GURU</h3>
        <p>Periode: <strong>{{ $namaBulan }} {{ $tahun }}</strong></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 28px;">No</th>
                <th style="width: 75px;">Tanggal</th>
                <th>Nama Guru / NIP</th>
                <th style="width: 55px;">Masuk</th>
                <th style="width: 55px;">Pulang</th>
                <th style="width: 80px;">Status</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($records as $index => $row)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center">{{ $row->tanggal ? $row->tanggal->format('d/m/Y') : '-' }}</td>
                <td>
                    <strong>{{ $row->user->name ?? '-' }}</strong>
                    @if($row->user && $row->user->nip)
                    <br><span style="font-size:8.5pt; color:#555;">NIP: {{ $row->user->nip }}</span>
                    @endif
                </td>
                <td class="text-center">{{ $row->jam_masuk ? substr($row->jam_masuk, 0, 5) : '-' }}</td>
                <td class="text-center">{{ $row->jam_pulang ? substr($row->jam_pulang, 0, 5) : '-' }}</td>
                <td class="text-center"><strong>{{ $row->status_label }}</strong></td>
                <td>{{ $row->keterangan ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center" style="padding:20px;">Tidak ada data presensi pada periode ini.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-sig">
        <div class="sig-box">
            <p>Mengetahui,<br>Kepala Lembaga</p>
            <div class="sig-space"></div>
            <p><strong>( .................................................... )</strong></p>
        </div>
        <div class="sig-box">
            <p>Tasikmalaya, {{ date('d') }} {{ $namaBulan }} {{ $tahun }}<br>Petugas Tata Usaha</p>
            <div class="sig-space"></div>
            <p><strong>( {{ auth()->user()->name }} )</strong></p>
        </div>
    </div>

</body>
</html>
