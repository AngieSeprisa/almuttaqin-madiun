<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Rekap Presensi Pondok — Yayasan Pondok Pesantren Al-Muttaqin</title>
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
            background: #2563eb;
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
            padding: 5px 6px;
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
            <strong>Pratinjau Cetak / PDF Rekap Presensi Pondok</strong>
            <span style="color:#64748b;margin-left:8px;">
                ({{ $mode === 'bulanan' ? 'Matriks Bulanan ' . $namaBulan . ' ' . $tahun : 'Rekap Harian ' . $tanggal }})
            </span>
        </div>
        <button type="button" class="btn-print" onclick="window.print()">Cetak / Simpan PDF</button>
    </div>

    <div class="kop">
        <h2>Yayasan Pondok Pesantren Al-Muttaqin</h2>
        <p>
            Laporan Rekapitulasi Presensi Pondok (Sholat &amp; Pengajian) &mdash;
            @if($mode === 'bulanan')
                Periode Bulan {{ $namaBulan }} {{ $tahun }}
            @else
                Tanggal {{ $tanggalCarbon->translatedFormat('d F Y') }}
            @endif
        </p>
    </div>

    <div class="summary-row">
        <div class="summary-box"><strong>Sesi:</strong> {{ $selectedSesi ? $selectedSesi->nama_sesi : ucfirst($kategori) }}</div>
        <div class="summary-box"><strong>Total Target Santri:</strong> {{ $stats['total_target'] }}</div>
        <div class="summary-box"><strong>Hadir Tepat Waktu:</strong> {{ $stats['hadir'] }}</div>
        <div class="summary-box"><strong>Terlambat:</strong> {{ $stats['terlambat'] }}</div>
        <div class="summary-box"><strong>Izin / Sakit:</strong> {{ $stats['izin_sakit'] }}</div>
        <div class="summary-box"><strong>Tidak Hadir / Alfa:</strong> {{ $stats['alfa'] }}</div>
    </div>

    @if($mode === 'bulanan')
        <table>
            <thead>
                <tr>
                    <th style="text-align:center;width:28px;">No</th>
                    <th>NISN</th>
                    <th>Nama Santri</th>
                    <th style="text-align:center;">L/P</th>
                    <th>Kamar / Kelas</th>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                        <th style="text-align:center;padding:3px 2px;">{{ $d }}</th>
                    @endfor
                    <th style="text-align:center;">H</th>
                    <th style="text-align:center;">T</th>
                    <th style="text-align:center;">I/S</th>
                    <th style="text-align:center;">A</th>
                    <th style="text-align:center;">%</th>
                </tr>
            </thead>
            <tbody>
                @forelse($matriksRows as $idx => $mRow)
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>{{ $mRow['nisn'] }}</td>
                        <td>{{ $mRow['nama_lengkap'] }}</td>
                        <td style="text-align:center;">{{ $mRow['gender_code'] }}</td>
                        <td>{{ $mRow['kamar'] }} / {{ $mRow['kelas_pengajian'] }}</td>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            <td style="text-align:center;padding:3px 2px;">{{ $mRow['days'][$d]['code'] ?? '-' }}</td>
                        @endfor
                        <td style="text-align:center;font-weight:700;">{{ $mRow['total_h'] }}</td>
                        <td style="text-align:center;font-weight:700;">{{ $mRow['total_t'] }}</td>
                        <td style="text-align:center;font-weight:700;">{{ $mRow['total_is'] }}</td>
                        <td style="text-align:center;font-weight:700;">{{ $mRow['total_a'] }}</td>
                        <td style="text-align:center;font-weight:700;">{{ $mRow['persentase'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $daysInMonth + 10 }}" style="text-align:center;padding:18px;">Tidak ada data santri.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:32px;text-align:center;">No</th>
                    <th>NISN</th>
                    <th>Nama Santri</th>
                    <th style="text-align:center;">Gender</th>
                    <th>Kamar / Kelas Pengajian</th>
                    <th style="text-align:center;">Jam Tap</th>
                    <th>Titik Mesin ESP32</th>
                    <th style="text-align:center;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($dailyRows as $idx => $row)
                    <tr>
                        <td style="text-align:center;">{{ $idx + 1 }}</td>
                        <td>{{ $row['nisn'] }}</td>
                        <td>{{ $row['nama_lengkap'] }}</td>
                        <td style="text-align:center;">{{ $row['gender_label'] }}</td>
                        <td>{{ $row['kamar'] }} / {{ $row['kelas_pengajian'] }}</td>
                        <td style="text-align:center;">{{ $row['jam_tap'] ?: '-' }}</td>
                        <td>{{ $row['device_label'] }}</td>
                        <td style="text-align:center;font-weight:700;">{{ $row['status_label'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align:center;padding:18px;">Tidak ada data santri.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif
</body>
</html>

