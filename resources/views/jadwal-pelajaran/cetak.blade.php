<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Pelajaran {{ $jenjang }} - Semester Gasal TA {{ $tahunAjaran }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm 10mm 12mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #ffffff;
            margin: 0;
            padding: 15px;
        }

        .no-print-bar {
            background: #1e293b;
            color: #ffffff;
            padding: 10px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .btn-print {
            background: #10b981;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-back {
            background: #475569;
            color: #ffffff;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
        }

        .kop-surat {
            text-align: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .kop-yayasan {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            color: #059669;
            text-transform: uppercase;
        }

        .kop-lembaga {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
            text-transform: uppercase;
            margin: 2px 0;
        }

        .kop-sub {
            font-size: 11px;
            color: #475569;
        }

        .doc-title-box {
            text-align: center;
            margin-bottom: 14px;
        }

        .doc-title {
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
        }

        .doc-subtitle {
            font-size: 11.5px;
            font-weight: 600;
            color: #475569;
            margin-top: 2px;
        }

        table.matrix-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin-bottom: 15px;
        }

        table.matrix-table th,
        table.matrix-table td {
            border: 1px solid #94a3b8;
            padding: 5px 6px;
            text-align: center;
            vertical-align: middle;
        }

        table.matrix-table th {
            background-color: #f1f5f9 !important;
            font-weight: 700;
            color: #0f172a;
        }

        .day-header-cell {
            background-color: #059669 !important;
            color: #ffffff !important;
            font-weight: 800;
            font-size: 11px;
            letter-spacing: 0.8px;
            text-align: left !important;
            padding: 5px 10px !important;
        }

        .break-cell {
            background-color: #fef9c3 !important;
            color: #854d0e;
            font-weight: 700;
            font-size: 10px;
        }

        .kbm-card {
            text-align: left;
            padding: 3px 4px;
            border-radius: 4px;
            background: #ffffff;
            border-left: 2.5px solid #059669;
        }

        .kbm-subject {
            font-weight: 800;
            font-size: 10px;
            color: #0f172a;
        }

        .kbm-teacher {
            font-size: 9px;
            color: #475569;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-top: 1px;
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            font-size: 9.5px;
            margin-top: 10px;
            border: 1px solid #cbd5e1;
            padding: 8px;
            border-radius: 6px;
            background: #f8fafc;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-badge {
            font-weight: 700;
            background: #059669;
            color: #ffffff;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 9px;
            min-width: 28px;
            text-align: center;
        }

        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
            padding: 0 40px;
            page-break-inside: avoid;
        }

        .sig-box {
            text-align: center;
            min-width: 200px;
        }

        .sig-title {
            font-size: 11px;
            font-weight: 600;
            margin-bottom: 50px;
        }

        .sig-name {
            font-size: 11.5px;
            font-weight: 700;
            text-decoration: underline;
        }

        .sig-nip {
            font-size: 10px;
            color: #475569;
        }

        @media print {
            .no-print-bar {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    {{-- Screen Action Bar --}}
    <div class="no-print-bar">
        <div>
            <strong style="font-size:14px;">Pratinjau Cetak Jadwal Pelajaran {{ $jenjang }}</strong>
            <span style="font-size:12px; opacity:0.8; margin-left:10px;">Semester Gasal TA {{ $tahunAjaran }}</span>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="{{ route('jadwal-pelajaran.index', ['jenjang' => $jenjang]) }}" class="btn-back">
                &larr; Kembali ke Grid
            </a>
            <button onclick="window.print()" class="btn-print">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Cetak Dokumen (Ctrl+P)</span>
            </button>
        </div>
    </div>

    {{-- Kop Surat --}}
    <div class="kop-surat">
        <div class="kop-yayasan">YAYASAN PONDOK PESANTREN AL-MUTTAQIN MADIUN</div>
        <div class="kop-lembaga">
            {{ $jenjang === 'SMP' ? 'SMP AL-MUTTAQIN MADIUN' : 'SMA AL-MUTTAQIN MADIUN' }}
        </div>
        <div class="kop-sub">
            Jalan Al-Muttaqin No. 1, Kota Madiun, Jawa Timur • NSS/NPSN Terakreditasi • Website: almuttaqin.id
        </div>
    </div>

    {{-- Document Title --}}
    <div class="doc-title-box">
        <div class="doc-title">
            JADWAL PELAJARAN MULTI-JENJANG TINGKAT {{ $jenjang }}
        </div>
        <div class="doc-subtitle">
            SEMESTER GASAL TAHUN AJARAN {{ $tahunAjaran }}
        </div>
    </div>

    {{-- Matrix Table --}}
    <table class="matrix-table">
        <thead>
            <tr>
                <th style="width:60px;">Jam Ke-</th>
                <th style="width:85px;">Waktu</th>
                @foreach($classrooms as $cls)
                <th>
                    <div>Kelas {{ $cls->nama_kelas }}</div>
                    <div style="font-size:9px; font-weight:normal; color:#475569;">Tingkat {{ $cls->tingkat }}</div>
                </th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($timeSlotsByDay as $dayName => $slots)
                <tr>
                    <td colspan="{{ $classrooms->count() + 2 }}" class="day-header-cell">
                        HARI {{ strtoupper($dayName) }}
                    </td>
                </tr>
                @foreach($slots as $slot)
                    @if($slot->is_break)
                        <tr>
                            <td style="font-weight:700;">{{ $slot->jam_ke === 0 ? '—' : $slot->jam_ke }}</td>
                            <td>{{ substr($slot->waktu_mulai, 0, 5) }} - {{ substr($slot->waktu_selesai, 0, 5) }}</td>
                            <td colspan="{{ $classrooms->count() }}" class="break-cell">
                                {{ $slot->keterangan ?? 'Istirahat / Non-KBM' }}
                            </td>
                        </tr>
                    @else
                        <tr>
                            <td style="font-weight:700; background:#f8fafc;">Jam {{ $slot->jam_ke }}</td>
                            <td style="font-size:9.5px; background:#f8fafc;">
                                {{ substr($slot->waktu_mulai, 0, 5) }} - {{ substr($slot->waktu_selesai, 0, 5) }}
                            </td>
                            @foreach($classrooms as $cls)
                                @php
                                    $sched = $matrix[$dayName][$slot->id][$cls->id] ?? null;
                                @endphp
                                <td>
                                    @if($sched)
                                        <div class="kbm-card">
                                            <div class="kbm-subject">
                                                [{{ $sched->subject->kode }}] {{ $sched->subject->nama_pelajaran }}
                                            </div>
                                            <div class="kbm-teacher">
                                                {{ $sched->teacher->name ?? '-' }}
                                            </div>
                                        </div>
                                    @else
                                        <span style="color:#cbd5e1;">—</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endif
                @endforeach
            @endforeach
        </tbody>
    </table>

    {{-- Teacher Legend --}}
    @if(!empty($teacherLegends))
    <div style="margin-top:10px; page-break-inside:avoid;">
        <div style="font-weight:700; font-size:10px; margin-bottom:4px; text-transform:uppercase; color:#0f172a;">
            Kode Guru & Mata Pelajaran Pengampu:
        </div>
        <div class="legend-grid">
            @foreach($teacherLegends as $tId => $leg)
            <div class="legend-item">
                <span class="legend-badge">{{ $leg['kode'] }}</span>
                <span><strong>{{ $leg['nama_guru'] }}</strong> ({{ $leg['mapel'] }})</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-title">
                Mengetahui,<br>
                Kepala Sekolah {{ $jenjang }} Al-Muttaqin
            </div>
            <div class="sig-name">
                {{ $jenjang === 'SMP' ? 'Drs. H. M. Sholihin, M.Pd.I' : 'H. Ahmad Muzakki, M.Pd.' }}
            </div>
            <div class="sig-nip">NIP/NIY: 19780512 200501 1 004</div>
        </div>

        <div class="sig-box">
            <div class="sig-title">
                Madiun, {{ now()->translatedFormat('d F Y') }}<br>
                Waka Kurikulum
            </div>
            <div class="sig-name">
                Ustadz Nurul Huda, S.Pd.
            </div>
            <div class="sig-nip">NIP/NIY: 19850314 201101 1 008</div>
        </div>
    </div>

</body>
</html>
