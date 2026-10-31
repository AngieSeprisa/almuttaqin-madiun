<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekap Presensi Sholat — Yayasan Pondok Pesantren Al-Muttaqin</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #0f172a;
            margin: 0;
            padding: 20px;
            background: #ffffff;
        }
        .print-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            margin-bottom: 18px;
        }
        .btn-print {
            background: #4f46e5;
            color: #ffffff;
            border: none;
            padding: 7px 14px;
            border-radius: 6px;
            font-weight: 700;
            cursor: pointer;
            font-size: 12px;
        }
        .kop {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }
        .kop h2 { margin: 0; font-size: 16px; text-transform: uppercase; }
        .kop p { margin: 4px 0 0; font-size: 12px; color: #475569; }
        .summary-row {
            display: flex;
            gap: 14px;
            margin-bottom: 12px;
            flex-wrap: wrap;
            font-size: 11px;
        }
        .summary-box {
            padding: 6px 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            background: #f8fafc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #f1f5f9;
            font-weight: 700;
            font-size: 10.5px;
        }
        @media print {
            .print-toolbar { display: none !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="print-toolbar">
        <div>
            <strong>Pratinjau Cetak / PDF Rekap Presensi Sholat</strong>
            <span style="color:#64748b;margin-left:8px;">
                @if($rentang === 'harian')
                    (Harian &mdash; {{ $tanggalCarbon->translatedFormat('d F Y') }})
                @elseif($rentang === 'mingguan')
                    (Mingguan &mdash; {{ $startDate->translatedFormat('d M') }} s/d {{ $endDate->translatedFormat('d M Y') }})
                @else
                    (Bulanan &mdash; {{ $namaBulan }} {{ $tahun }})
                @endif
            </span>
        </div>
        <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="kop">
        <h2>Yayasan Pondok Pesantren Al-Muttaqin</h2>
        <p>
            Laporan Rekapitulasi Presensi Sholat Santri &mdash;
            @if($rentang === 'harian')
                Tanggal {{ $tanggalCarbon->translatedFormat('d F Y') }}
            @elseif($rentang === 'mingguan')
                Periode {{ $startDate->translatedFormat('d M Y') }} s/d {{ $endDate->translatedFormat('d M Y') }}
            @else
                Periode Bulan {{ $namaBulan }} {{ $tahun }}
            @endif
        </p>
    </div>

    <div class="summary-row">
        <div class="summary-box"><strong>Total Santri:</strong> {{ $stats['total_target'] }}</div>
        <div class="summary-box"><strong>Hadir Tepat:</strong> {{ $stats['hadir'] }}</div>
        <div class="summary-box"><strong>Terlambat:</strong> {{ $stats['terlambat'] }}</div>
        <div class="summary-box"><strong>Izin / Sakit:</strong> {{ $stats['izin_sakit'] }}</div>
        <div class="summary-box"><strong>Alpha:</strong> {{ $stats['alfa'] }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:36px; text-align:center;">No</th>
                <th style="width:110px;">NISN</th>
                <th>Nama Santri</th>
                <th>Kamar</th>
                <th>Jenjang</th>
                <th style="text-align:center;width:60px;">Hadir</th>
                <th style="text-align:center;width:60px;">Telat</th>
                <th style="text-align:center;width:60px;">Izin</th>
                <th style="text-align:center;width:60px;">Alfa</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $idx => $r)
                <tr>
                    <td style="text-align:center;">{{ $idx + 1 }}</td>
                    <td>{{ $r['nisn'] }}</td>
                    <td><strong>{{ $r['nama_lengkap'] }}</strong></td>
                    <td>{{ $r['kamar'] }}</td>
                    <td>{{ $r['jenjang'] }}</td>
                    <td style="text-align:center;font-weight:700;color:#15803d;">{{ $r['total_h'] }}</td>
                    <td style="text-align:center;font-weight:700;color:#b45309;">{{ $r['total_t'] }}</td>
                    <td style="text-align:center;font-weight:700;color:#1d4ed8;">{{ $r['total_i'] + $r['total_s'] }}</td>
                    <td style="text-align:center;font-weight:700;color:#b91c1c;">{{ $r['total_a'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" style="text-align:center;padding:18px;">Tidak ada data santri.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
