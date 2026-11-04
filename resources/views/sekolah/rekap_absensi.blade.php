@extends('layouts.app')

@section('title', 'Rekap Absensi Siswa')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px;">
    <button type="button" class="btn-wa" onclick="openWaModal()" title="Buka &amp; Salin Format Laporan WhatsApp">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.41 1.56 1.56 2.41 3.63 2.41 5.83s-.85 4.27-2.41 5.83c-1.55 1.55-3.62 2.41-5.82 2.41-1.5 0-2.97-.39-4.27-1.13l-.3-.17-3.17.83.85-3.09-.2-.31c-.81-1.33-1.25-2.88-1.25-4.47 0-2.2 0.86-4.27 2.42-5.83 1.56-1.55 3.63-2.41 5.82-2.41m-2.64 5.34c-.16 0-.39.06-.6.28-.2.22-.79.77-.79 1.88s.81 2.18.92 2.33c.12.16 1.57 2.4 3.82 3.37.53.23.95.37 1.28.47.54.17 1.03.15 1.42.09.43-.06 1.33-.54 1.52-1.07.19-.52.19-.97.13-1.07-.06-.09-.22-.16-.47-.28-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.65.81-.8 1-.15.19-.3.21-.55.09-.25-.12-1.07-.39-2.04-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.28.37-.42.12-.15.16-.25.25-.42.08-.16.04-.31-.02-.43s-.56-1.36-.77-1.86c-.2-.48-.41-.42-.56-.43h-.48z"/>
        </svg>
        <span>Laporan WA</span>
    </button>
    <a href="{{ route('sekolah.jurnal') }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
            <line x1="12" y1="5" x2="12" y2="19"></line>
            <line x1="5" y1="12" x2="19" y2="12"></line>
        </svg>
        <span>Input Jurnal</span>
    </a>
</div>
@endsection

@section('content')
<div class="rekap-sekolah-container">

    {{-- Unified Filter Bar Card --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('rekap-absensi.sekolah') }}" method="GET" class="filter-form">
            <div class="filter-group-left">
                
                {{-- Filter Kelas --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="kelas" class="form-select filter-select" onchange="this.form.submit()">
                        @foreach($kelasList as $k)
                            @php
                                $isKSelected = $selectedKelas === $k || \App\Http\Controllers\PlottingKelasController::isSameClass($selectedKelas, $k);
                                $dispK = \App\Http\Controllers\PlottingKelasController::toRomanDisplay($k);
                            @endphp
                            <option value="{{ $k }}" {{ $isKSelected ? 'selected' : '' }}>
                                Kelas {{ $dispK }}
                            </option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Bulan --}}
                @php
                    $bulanList = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                @endphp
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <select name="bulan" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Bulan</option>
                        @foreach($bulanList as $num => $namaBulan)
                            <option value="{{ $num }}" {{ (int) request('bulan', $bulan) === $num ? 'selected' : '' }}>
                                {{ $namaBulan }}
                            </option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Filter Tahun --}}
                <div class="filter-select-wrap">
                    <select name="tahun" class="form-select filter-select" style="min-width:110px;" onchange="this.form.submit()">
                        @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                            <option value="{{ $y }}" {{ (int) request('tahun', $tahun) === $y ? 'selected' : '' }}>
                                Tahun {{ $y }}
                            </option>
                        @endfor
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Reset Button --}}
                @if(request()->hasAny(['bulan', 'search']) || (request()->has('kelas') && request('kelas') !== ($kelasList[0] ?? '')))
                <a href="{{ route('rekap-absensi.sekolah') }}" class="btn btn-secondary filter-btn-reset">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Reset
                </a>
                @endif
            </div>

            {{-- Filter Pencarian Nama Siswa & Action Laporan WA --}}
            <div style="display:flex; align-items:center; gap:8px;">
                <div class="filter-search-wrap">
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control filter-search-input"
                           placeholder="Cari nama siswa...">
                    <button type="submit" class="btn btn-primary filter-search-btn">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                    </button>
                </div>

                {{-- Tombol Laporan WA --}}
                <button type="button" class="btn-wa" onclick="openWaModal()" title="Buka &amp; Salin Format Laporan WhatsApp">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.41 1.56 1.56 2.41 3.63 2.41 5.83s-.85 4.27-2.41 5.83c-1.55 1.55-3.62 2.41-5.82 2.41-1.5 0-2.97-.39-4.27-1.13l-.3-.17-3.17.83.85-3.09-.2-.31c-.81-1.33-1.25-2.88-1.25-4.47 0-2.2 0.86-4.27 2.42-5.83 1.56-1.55 3.63-2.41 5.82-2.41m-2.64 5.34c-.16 0-.39.06-.6.28-.2.22-.79.77-.79 1.88s.81 2.18.92 2.33c.12.16 1.57 2.4 3.82 3.37.53.23.95.37 1.28.47.54.17 1.03.15 1.42.09.43-.06 1.33-.54 1.52-1.07.19-.52.19-.97.13-1.07-.06-.09-.22-.16-.47-.28-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.65.81-.8 1-.15.19-.3.21-.55.09-.25-.12-1.07-.39-2.04-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.28.37-.42.12-.15.16-.25.25-.42.08-.16.04-.31-.02-.43s-.56-1.36-.77-1.86c-.2-.48-.41-.42-.56-.43h-.48z"/>
                    </svg>
                    <span>Laporan WA</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Kartu Ringkasan Statistik --}}
    <div class="stat-summary-grid">
        <div class="form-section-card stat-summary-card">
            <span class="stat-summary-label">Total Pertemuan</span>
            <div class="stat-summary-val-wrap">
                <span class="stat-summary-num">{{ $totalPertemuan }}</span>
                <span class="stat-summary-sub">Sesi Jurnal</span>
            </div>
        </div>

        <div class="form-section-card stat-summary-card">
            <span class="stat-summary-label">Total Siswa Kelas</span>
            <div class="stat-summary-val-wrap">
                <span class="stat-summary-num text-electric-blue">{{ count($rekapSiswa) }}</span>
                <span class="stat-summary-sub">Siswa Aktif</span>
            </div>
        </div>

        @php
            $totalAbsen = $totalSemuaHadir + $totalSemuaSakit + $totalSemuaIzin + $totalSemuaAlpa;
            $persenHadir = $totalAbsen > 0 ? round($totalSemuaHadir / $totalAbsen * 100) : 0;
            $donutR = 42;
            $donutC = 2 * M_PI * $donutR;
            $donutOffset = $donutC * (1 - $persenHadir / 100);
        @endphp
        <div class="form-section-card stat-summary-card stat-donut-card">
            <span class="stat-summary-label">Akumulasi Kehadiran Siswa</span>
            <div class="donut-row">
                <div class="donut-wrap" role="img" aria-label="Persentase kehadiran {{ $persenHadir }} persen">
                    <svg viewBox="0 0 100 100" class="donut-svg">
                        <circle class="donut-track" cx="50" cy="50" r="{{ $donutR }}"></circle>
                        <circle class="donut-value" cx="50" cy="50" r="{{ $donutR }}"
                                stroke-dasharray="{{ $donutC }}" stroke-dashoffset="{{ $donutOffset }}"
                                transform="rotate(-90 50 50)"></circle>
                    </svg>
                    <span class="donut-text">{{ $persenHadir }}%</span>
                </div>
                <div class="donut-legend">
                    <span class="stat-pill-tag tag-h">H: {{ $totalSemuaHadir }}</span>
                    <span class="stat-pill-tag tag-s">S: {{ $totalSemuaSakit }}</span>
                    <span class="stat-pill-tag tag-i">I: {{ $totalSemuaIzin }}</span>
                    <span class="stat-pill-tag tag-a">A: {{ $totalSemuaAlpa }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Rekap Absensi Sekolah --}}
    <div class="table-card rekap-matrix-card">
        <div class="table-wrapper rekap-scroll-wrap">
            <table class="rekap-matrix-table">
                <thead>
                    {{-- Baris 1: Header Atas --}}
                    <tr>
                        <th rowspan="2" class="th-fixed th-col-no">NO</th>
                        <th rowspan="2" class="th-fixed th-col-nama">NAMA SISWA</th>

                        @if($totalPertemuan > 0)
                            @foreach($tanggalGroups as $group)
                                <th colspan="{{ $group['count'] }}" class="th-date-group">
                                    {{ $group['tanggal'] }}
                                </th>
                            @endforeach
                        @else
                            <th rowspan="2" class="th-no-session">SESI PERTEMUAN</th>
                        @endif

                        <th rowspan="2" class="th-stat-summary col-stat-h" title="Hadir">H</th>
                        <th rowspan="2" class="th-stat-summary col-stat-t" title="Terlambat">T</th>
                        <th rowspan="2" class="th-stat-summary col-stat-i" title="Izin">I</th>
                        <th rowspan="2" class="th-stat-summary col-stat-s" title="Sakit">S</th>
                        <th rowspan="2" class="th-stat-summary col-stat-a" title="Alpa">A</th>
                        <th rowspan="2" class="th-col-aksi">AKSI</th>
                    </tr>

                    {{-- Baris 2: Header Singkatan Mata Pelajaran --}}
                    @if($totalPertemuan > 0)
                    <tr>
                        @foreach($jurnals as $jurnal)
                            <th class="th-mapel-code" title="{{ $jurnal->mata_pelajaran }} ({{ $jurnal->jam_ke }}) - Guru: {{ $jurnal->nama_guru }}">
                                <span class="mapel-code mapel-code-pill">{{ $jurnal->kode_mapel }}</span>
                            </th>
                        @endforeach
                    </tr>
                    @endif
                </thead>
                <tbody>
                    @forelse($rekapSiswa as $index => $item)
                    <tr>
                        {{-- Kolom No --}}
                        <td class="td-col-no">{{ $index + 1 }}</td>

                        {{-- Kolom Nama Siswa & NISN --}}
                        <td class="td-col-nama">
                            <div class="siswa-nama-text">{{ $item->siswa->nama_lengkap }}</div>
                            <div class="siswa-nisn-text">NISN: {{ $item->nisn }}</div>
                        </td>

                        {{-- Kolom Matrix Sesi Pertemuan --}}
                        @if($totalPertemuan > 0)
                            @foreach($jurnals as $jurnal)
                                @php
                                    $st = $item->pertemuan_absensi[$jurnal->id] ?? null;
                                @endphp
                                <td class="td-matrix-cell">
                                    @if($st === 'hadir')
                                        <span class="absen-pill pill-h" title="Hadir - {{ $jurnal->mata_pelajaran }}">H</span>
                                    @elseif($st === 'sakit')
                                        <span class="absen-pill pill-s" title="Sakit - {{ $jurnal->mata_pelajaran }}">S</span>
                                    @elseif($st === 'izin')
                                        <span class="absen-pill pill-i" title="Izin - {{ $jurnal->mata_pelajaran }}">I</span>
                                    @elseif($st === 'alpa')
                                        <span class="absen-pill pill-a" title="Alpa - {{ $jurnal->mata_pelajaran }}">A</span>
                                    @else
                                        <span class="absen-pill-empty" title="Tidak ada data">-</span>
                                    @endif
                                </td>
                            @endforeach
                        @else
                            <td class="td-no-session-cell">
                                <span style="color:var(--text-muted); font-size:12px;">Belum ada sesi</span>
                            </td>
                        @endif

                        {{-- Kolom Total Akumulasi (H, T, I, S, A) --}}
                        <td class="td-stat-val val-h">{{ $item->hadir }}</td>
                        <td class="td-stat-val val-t">{{ $item->terlambat }}</td>
                        <td class="td-stat-val {{ $item->izin > 0 ? 'val-i-active' : 'val-muted' }}">{{ $item->izin }}</td>
                        <td class="td-stat-val {{ $item->sakit > 0 ? 'val-s-active' : 'val-muted' }}">{{ $item->sakit }}</td>
                        <td class="td-stat-val {{ $item->alpa > 0 ? 'val-a-active' : 'val-muted' }}">{{ $item->alpa }}</td>

                        {{-- Kolom Tombol Aksi Detail --}}
                        <td class="td-col-aksi">
                            <button type="button"
                                    class="btn-table-detail js-btn-detail"
                                    data-siswa-id="{{ $item->siswa->id }}"
                                    data-nama="{{ $item->siswa->nama_lengkap }}"
                                    data-nisn="{{ $item->nisn }}"
                                    data-kelas="{{ $item->siswa->kelas }}"
                                    data-jenjang="{{ $item->siswa->jenjang }}"
                                    data-pertemuan="{{ $item->pertemuan }}"
                                    data-hadir="{{ $item->hadir }}"
                                    data-terlambat="{{ $item->terlambat }}"
                                    data-izin="{{ $item->izin }}"
                                    data-sakit="{{ $item->sakit }}"
                                    data-alpa="{{ $item->alpa }}"
                                    data-persentase="{{ $item->persentase }}"
                                    data-riwayat='@json($item->riwayat)'>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <span>Detail</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $totalPertemuan > 0 ? 8 + $totalPertemuan : 9 }}" class="rekap-empty-row">
                            <div class="empty-state-wrap">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="empty-icon">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                </svg>
                                <span class="empty-text">Tidak ada data siswa atau absensi untuk kelas ini</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


</div>

{{-- Modal Detail Kehadiran Siswa --}}
<div class="modal-backdrop-custom" id="modalDetailSiswa" style="display:none;">
    <div class="modal-dialog-custom">
        <div class="modal-content-custom">
            
            {{-- Modal Header --}}
            <div class="modal-header-custom">
                <div class="modal-title-wrap">
                    <div class="modal-avatar-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </div>
                    <div>
                        <h3 class="modal-title-text" id="modalNamaSiswa">Nama Siswa</h3>
                        <p class="modal-subtitle-text" id="modalSubInfo">NISN: - • Kelas -</p>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" id="modalCloseBtn" aria-label="Tutup Modal">&times;</button>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body-custom">
                
                {{-- Statistik Kehadiran Siswa --}}
                <div class="modal-stats-grid">
                    <div class="modal-stat-box">
                        <span class="modal-stat-title">Total Sesi</span>
                        <span class="modal-stat-value" id="modalTotalSesi">0</span>
                    </div>
                    <div class="modal-stat-box box-h">
                        <span class="modal-stat-title">Hadir (H)</span>
                        <span class="modal-stat-value text-h" id="modalHadir">0</span>
                    </div>
                    <div class="modal-stat-box box-t">
                        <span class="modal-stat-title">Terlambat (T)</span>
                        <span class="modal-stat-value text-t" id="modalTerlambat">0</span>
                    </div>
                    <div class="modal-stat-box box-i">
                        <span class="modal-stat-title">Izin (I)</span>
                        <span class="modal-stat-value text-i" id="modalIzin">0</span>
                    </div>
                    <div class="modal-stat-box box-s">
                        <span class="modal-stat-title">Sakit (S)</span>
                        <span class="modal-stat-value text-s" id="modalSakit">0</span>
                    </div>
                    <div class="modal-stat-box box-a">
                        <span class="modal-stat-title">Alpa (A)</span>
                        <span class="modal-stat-value text-a" id="modalAlpa">0</span>
                    </div>
                </div>

                {{-- Bar Persentase Kehadiran --}}
                <div class="progress-wrap-box">
                    <div class="progress-header-row">
                        <span class="progress-label">Persentase Kehadiran</span>
                        <span class="progress-percent-val" id="modalPersentase">0%</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" id="modalProgressBar" style="width:0%;"></div>
                    </div>
                </div>

                {{-- Riwayat Pertemuan --}}
                <h4 class="history-section-title">Riwayat Pertemuan & Presensi</h4>
                <div class="history-table-wrapper">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th style="width:40px; text-align:center;">No</th>
                                <th style="width:90px;">Tanggal</th>
                                <th style="min-width:130px;">Mata Pelajaran</th>
                                <th style="width:75px; text-align:center;">Jam Ke</th>
                                <th style="min-width:110px;">Guru</th>
                                <th style="min-width:140px;">Materi</th>
                                <th style="width:85px; text-align:center;">Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="modalRiwayatTableBody">
                            {{-- Diisi secara dinamis oleh JavaScript --}}
                        </tbody>
                    </table>
                </div>

            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer-custom">
                <button type="button" class="btn btn-secondary" id="modalCloseFooterBtn">Tutup</button>
            </div>

        </div>
    </div>
</div>

{{-- Modal Laporan WhatsApp --}}
<div class="modal-backdrop-custom" id="modalLaporanWA" style="display:none;">
    <div class="modal-dialog-custom" style="max-width: 680px;">
        <div class="modal-content-custom">
            
            {{-- Modal Header --}}
            <div class="modal-header-custom" style="background:#f8fafc;">
                <div class="modal-title-wrap">
                    <div class="modal-avatar-icon" style="background:#dcfce7; color:#16a34a;">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.41 1.56 1.56 2.41 3.63 2.41 5.83s-.85 4.27-2.41 5.83c-1.55 1.55-3.62 2.41-5.82 2.41-1.5 0-2.97-.39-4.27-1.13l-.3-.17-3.17.83.85-3.09-.2-.31c-.81-1.33-1.25-2.88-1.25-4.47 0-2.2 0.86-4.27 2.42-5.83 1.56-1.55 3.63-2.41 5.82-2.41m-2.64 5.34c-.16 0-.39.06-.6.28-.2.22-.79.77-.79 1.88s.81 2.18.92 2.33c.12.16 1.57 2.4 3.82 3.37.53.23.95.37 1.28.47.54.17 1.03.15 1.42.09.43-.06 1.33-.54 1.52-1.07.19-.52.19-.97.13-1.07-.06-.09-.22-.16-.47-.28-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.65.81-.8 1-.15.19-.3.21-.55.09-.25-.12-1.07-.39-2.04-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.28.37-.42.12-.15.16-.25.25-.42.08-.16.04-.31-.02-.43s-.56-1.36-.77-1.86c-.2-.48-.41-.42-.56-.43h-.48z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="modal-title-text" style="font-size:16px;">Laporan WhatsApp Absensi Sekolah</h3>
                        <p class="modal-subtitle-text">
                            Kelas {{ \App\Http\Controllers\PlottingKelasController::toRomanDisplay($selectedKelas ?? '-') }} &bull; {{ $bulan ? ($bulanList[$bulan] ?? 'Bulan ' . $bulan) : 'Semua Bulan' }} {{ $tahun }}
                        </p>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeWaModal()" aria-label="Tutup Modal">&times;</button>
            </div>

            {{-- Modal Body --}}
            <div class="modal-body-custom" style="padding:18px 24px; gap:14px;">
                {{-- Controls Filter Mode --}}
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:12px;">
                    <div>
                        <label style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">PILIH FORMAT LAPORAN</label>
                        <select id="waModeSelect" class="form-select" style="font-size:12.5px; height:38px;" onchange="handleWaModeChange()">
                            <option value="rekap">Rekap Keseluruhan (Bulan/Periode)</option>
                            <option value="sesi">Per Sesi Pertemuan / Tanggal</option>
                        </select>
                    </div>
                    <div id="waSesiWrap" style="display:none;">
                        <label style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">PILIH SESI PERTEMUAN</label>
                        <select id="waSesiSelect" class="form-select" style="font-size:12.5px; height:38px;" onchange="renderSekolahWaPreview()">
                            @foreach($jurnals as $j)
                                <option value="{{ $j->id }}">
                                    {{ $j->tgl_full }} - {{ $j->kode_mapel }} ({{ $j->jam_ke }}) - {{ $j->nama_guru }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Textarea Preview --}}
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">TEKS PESAN WHATSAPP</label>
                    <textarea id="waTextarea" spellcheck="false" style="width:100%; height:270px; font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size:12px; line-height:1.6; padding:12px; border:1px solid #cbd5e1; border-radius:10px; resize:vertical; background:#f8fafc; color:#0f172a; box-sizing:border-box;"></textarea>
                </div>

                <div id="copyNotif" style="display:none; padding:8px 12px; background:#dcfce7; color:#15803d; font-size:12px; font-weight:600; border-radius:6px; text-align:center;">
                    Format WhatsApp berhasil disalin ke clipboard!
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="modal-footer-custom" style="display:flex; align-items:center; justify-content:space-between; padding:14px 24px; background:#f8fafc;">
                <div style="font-size:11.5px; color:#64748b;">
                    Gunakan tombol salin atau buka langsung via WhatsApp Web.
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" class="btn btn-secondary" onclick="closeWaModal()" style="height:38px; padding:0 16px; font-size:13px; font-weight:600;">
                        Tutup
                    </button>
                    <button type="button" class="btn-wa" onclick="copyWaText()" id="btnSalinWa" style="height:38px; padding:0 18px; font-size:13px; font-weight:600;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span id="btnSalinWaText">Salin Teks</span>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="openWaWeb()" title="Buka WhatsApp Web" style="height:38px; padding:0 12px; color:#16a34a; border-color:#86efac; background:#f0fdf4;">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path>
                            <polyline points="15 3 21 3 21 9"></polyline>
                            <line x1="10" y1="14" x2="21" y2="3"></line>
                        </svg>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Layout Container */
.rekap-sekolah-container {
    display: flex;
    flex-direction: column;
    gap: 18px;
    width: 100%;
}

/* Stat Summary Grid */
.stat-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
}
.stat-summary-card {
    padding: 16px 20px;
}
.stat-summary-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    display: block;
}
.stat-summary-val-wrap {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-top: 4px;
}
.stat-summary-num {
    font-size: 24px;
    font-weight: 800;
    color: var(--text-primary);
}
.stat-summary-sub {
    font-size: 12px;
    color: var(--text-secondary);
}
.text-electric-blue {
    color: var(--electric-blue, #0052FF);
}
.stat-pills-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 6px;
    flex-wrap: wrap;
}
.stat-pill-tag {
    font-size: 12px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 8px;
}
.stat-pill-tag.tag-h { background: rgba(16, 185, 129, 0.12); color: #059669; }
.stat-pill-tag.tag-s { background: rgba(245, 158, 11, 0.12); color: #D97706; }
.stat-pill-tag.tag-i { background: rgba(37, 99, 235, 0.12); color: #2563EB; }
.stat-pill-tag.tag-a { background: rgba(239, 68, 68, 0.12); color: #EF4444; }

/* Donut Chart Persentase Absensi */
.stat-donut-card {
    padding: 14px 18px;
}
.donut-row {
    display: flex;
    align-items: center;
    gap: 16px;
    margin-top: 8px;
}
.donut-wrap {
    position: relative;
    width: 82px;
    height: 82px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}
.donut-svg {
    width: 100%;
    height: 100%;
}
.donut-track {
    fill: none;
    stroke: #E2E8F0;
    stroke-width: 13;
}
.donut-value {
    fill: none;
    stroke: #3B82F6;
    stroke-width: 13;
    stroke-linecap: butt;
    transition: stroke-dashoffset 0.6s cubic-bezier(0.4, 0, 0.2, 1);
}
.donut-text {
    position: absolute;
    font-size: 18px;
    font-weight: 800;
    color: #3B82F6;
    letter-spacing: -0.5px;
    user-select: none;
}
.donut-legend {
    display: grid;
    grid-template-columns: repeat(2, auto);
    gap: 6px 8px;
}
.dark-mode .donut-track {
    stroke: #334155;
}
.dark-mode .donut-value {
    stroke: #60A5FA;
}
.dark-mode .donut-text {
    color: #60A5FA;
}

/* Table Wrapper & Styling */
.rekap-matrix-card {
    overflow: hidden;
    background: var(--bg-card, #FFFFFF);
    border: 1px solid var(--border-color, #E2E8F0);
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.rekap-scroll-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    width: 100%;
}
.rekap-matrix-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-family: inherit;
    font-size: 13px;
    white-space: nowrap;
}

/* Table Header */
.rekap-matrix-table thead tr th {
    background: #F8FAFC;
    color: #475569;
    font-size: 11.5px;
    font-weight: 700;
    text-align: center;
    padding: 9px 8px;
    border-bottom: 1px solid #E2E8F0;
    border-right: 1px solid #EDF2F7;
    letter-spacing: 0.3px;
}
.dark-mode .rekap-matrix-table thead tr th {
    background: #1E293B;
    color: #94A3B8;
    border-bottom: 1px solid #334155;
    border-right: 1px solid #334155;
}

/* Fixed Columns */
.th-col-no {
    width: 48px;
    min-width: 48px;
    text-align: center;
    border-right: 1px solid #E2E8F0 !important;
}
.th-col-nama {
    min-width: 200px;
    text-align: left !important;
    padding-left: 16px !important;
    border-right: 1px solid #E2E8F0 !important;
}
/* Tombol Laporan WA */
.btn-wa {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background-color: #00875a;
    color: #ffffff !important;
    border: none;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.2s, transform 0.1s;
    height: 38px;
    box-sizing: border-box;
    white-space: nowrap;
}
.btn-wa:hover {
    background-color: #006644;
    color: #ffffff !important;
}
.btn-wa:active {
    transform: scale(0.98);
}

.th-date-group {
    font-size: 12px;
    font-weight: 700;
    color: #1E293B !important;
    padding: 6px 4px !important;
    height: 32px;
    line-height: 20px;
    box-sizing: border-box;
    border-bottom: 1px solid #CBD5E1 !important;
    background: #EDF2F7 !important;
}
.dark-mode .th-date-group {
    background: #0F172A !important;
    color: #F8FAFC !important;
    border-bottom: 1px solid #334155 !important;
}
.th-mapel-code {
    font-size: 11px;
    font-weight: 700;
    color: #1E293B;
    padding: 4px 2px !important;
    min-width: 48px;
    width: 48px;
    height: 32px;
    box-sizing: border-box;
    text-align: center;
    vertical-align: middle;
    background: #F8FAFC !important;
    border-bottom: 2px solid #CBD5E1 !important;
}
.th-mapel-code .mapel-code,
.th-mapel-code .mapel-code-pill {
    display: inline-block;
    padding: 2px 5px;
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 11px;
    font-weight: 800;
    color: #0F172A;
    background: #E2E8F0;
    border: 1px solid #CBD5E1;
    border-radius: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
    text-align: center;
}
.dark-mode .th-mapel-code {
    background: #172033 !important;
    border-bottom: 2px solid #334155 !important;
}
.dark-mode .th-mapel-code .mapel-code,
.dark-mode .th-mapel-code .mapel-code-pill {
    color: #F8FAFC;
    background: #1E293B;
    border-color: #334155;
}

/* Gaya buku absensi: kolom No & Nama menempel saat digeser, header menempel saat scroll */
.rekap-scroll-wrap { max-height: 78vh; overflow: auto; }
.rekap-matrix-table thead tr:first-child th {
    position: sticky;
    top: 0;
    z-index: 5;
}
.rekap-matrix-table thead tr:nth-child(2) th {
    position: sticky;
    top: 32px;
    z-index: 4;
}
.rekap-matrix-table .th-col-no,
.rekap-matrix-table .td-col-no {
    position: sticky;
    left: 0;
    z-index: 4;
    background: #fff;
    min-width: 44px;
    width: 44px;
}
.rekap-matrix-table .th-col-nama,
.rekap-matrix-table .td-col-nama {
    position: sticky;
    left: 44px;
    z-index: 4;
    background: #fff;
    box-shadow: 2px 0 4px -2px rgba(15, 23, 42, 0.12);
}
.rekap-matrix-table thead .th-col-no,
.rekap-matrix-table thead .th-col-nama {
    z-index: 8;
    background: #F8FAFC;
}
.dark-mode .rekap-matrix-table .td-col-no,
.dark-mode .rekap-matrix-table .td-col-nama { background: #0F172A; }
.dark-mode .rekap-matrix-table thead .th-col-no,
.dark-mode .rekap-matrix-table thead .th-col-nama { background: #1E293B; }
.rekap-matrix-table .td-matrix-cell { border-right: 1px solid #E2E8F0; text-align: center; }

@media (max-width: 768px) {
    .rekap-matrix-card { display: block !important; }
    .rekap-matrix-table { font-size: 12px; }
    .th-col-nama { min-width: 130px; padding-left: 10px !important; }
    .rekap-matrix-table .td-col-nama { padding-left: 10px !important; }
    .siswa-nama-text { font-size: 12.5px; white-space: normal; min-width: 120px; max-width: 150px; }
    .absen-pill { width: 24px; height: 24px; font-size: 11px; }
}
.th-no-session {
    min-width: 140px;
    color: #94A3B8;
}

/* Stat Columns Header */
.th-stat-summary {
    width: 36px;
    min-width: 36px;
    font-size: 12.5px;
    font-weight: 800;
    text-align: center;
}
.col-stat-h { color: #059669 !important; }
.col-stat-t { color: #D97706 !important; }
.col-stat-i { color: #2563EB !important; }
.col-stat-s { color: #D97706 !important; }
.col-stat-a { color: #EF4444 !important; }
.th-col-aksi {
    width: 90px;
    min-width: 90px;
    text-align: center;
    border-right: none !important;
}

/* Table Body Cells */
.rekap-matrix-table tbody tr {
    transition: background-color 0.12s ease;
}
.rekap-matrix-table tbody tr:hover {
    background-color: rgba(241, 245, 249, 0.6);
}
.dark-mode .rekap-matrix-table tbody tr:hover {
    background-color: rgba(30, 41, 59, 0.4);
}
.rekap-matrix-table tbody tr td {
    padding: 9px 8px;
    border-bottom: 1px solid #EDF2F7;
    border-right: 1px solid #F1F5F9;
    vertical-align: middle;
}
.dark-mode .rekap-matrix-table tbody tr td {
    border-bottom: 1px solid #1E293B;
    border-right: 1px solid #1E293B;
}

/* Column Specific Cells */
.td-col-no {
    text-align: center;
    font-weight: 600;
    color: #64748B;
    font-size: 12.5px;
    border-right: 1px solid #E2E8F0 !important;
}
.dark-mode .td-col-no {
    border-right: 1px solid #334155 !important;
}
.td-col-nama {
    padding-left: 16px !important;
    border-right: 1px solid #E2E8F0 !important;
}
.dark-mode .td-col-nama {
    border-right: 1px solid #334155 !important;
}
.siswa-nama-text {
    font-weight: 700;
    color: #1E293B;
    font-size: 13.5px;
    line-height: 1.3;
}
.dark-mode .siswa-nama-text {
    color: #F8FAFC;
}
.siswa-nisn-text {
    font-size: 11px;
    color: #94A3B8;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    margin-top: 1px;
}

/* Matrix Absen Pills */
.td-matrix-cell {
    text-align: center;
    padding: 6px 3px !important;
}
.absen-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 27px;
    height: 27px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    line-height: 1;
    user-select: none;
    transition: transform 0.1s ease;
}
.absen-pill:hover {
    transform: scale(1.1);
}
.pill-h {
    background: rgba(16, 185, 129, 0.08);
    border: 1px solid rgba(16, 185, 129, 0.35);
    color: #059669;
}
.dark-mode .pill-h {
    background: rgba(16, 185, 129, 0.15);
    border-color: rgba(16, 185, 129, 0.45);
    color: #34D399;
}
.pill-s {
    background: rgba(245, 158, 11, 0.08);
    border: 1px solid rgba(245, 158, 11, 0.35);
    color: #D97706;
}
.dark-mode .pill-s {
    background: rgba(245, 158, 11, 0.15);
    border-color: rgba(245, 158, 11, 0.45);
    color: #FBBF24;
}
.pill-i {
    background: rgba(59, 130, 246, 0.08);
    border: 1px solid rgba(59, 130, 246, 0.35);
    color: #2563EB;
}
.dark-mode .pill-i {
    background: rgba(59, 130, 246, 0.15);
    border-color: rgba(59, 130, 246, 0.45);
    color: #60A5FA;
}
.pill-a {
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.35);
    color: #EF4444;
}
.dark-mode .pill-a {
    background: rgba(239, 68, 68, 0.15);
    border-color: rgba(239, 68, 68, 0.45);
    color: #F87171;
}
.absen-pill-empty {
    color: #CBD5E1;
    font-size: 13px;
    font-weight: 500;
}
.td-no-session-cell {
    text-align: center;
    padding: 12px;
}

/* Stat Values */
.td-stat-val {
    text-align: center;
    font-size: 12.5px;
    font-weight: 700;
    width: 36px;
    min-width: 36px;
}
.val-h { color: #059669; }
.val-t { color: #64748B; }
.val-i-active { color: #2563EB; font-weight: 800; }
.val-s-active { color: #D97706; font-weight: 800; }
.val-a-active { color: #EF4444; font-weight: 800; }
.val-muted { color: #64748B; opacity: 0.85; }

/* Aksi Column & Button */
.td-col-aksi {
    text-align: center;
    border-right: none !important;
}
.btn-table-detail {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid #CBD5E1;
    background: #FFFFFF;
    color: #475569;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    transition: all 0.15s ease;
}
.btn-table-detail svg {
    width: 14px;
    height: 14px;
    stroke-width: 2;
}
.btn-table-detail:hover {
    border-color: var(--electric-blue, #0052FF);
    color: var(--electric-blue, #0052FF);
    background: rgba(0, 82, 255, 0.04);
    box-shadow: 0 2px 4px rgba(0, 82, 255, 0.08);
}
.dark-mode .btn-table-detail {
    background: #1E293B;
    border-color: #334155;
    color: #CBD5E1;
}
.dark-mode .btn-table-detail:hover {
    border-color: #38BDF8;
    color: #38BDF8;
    background: rgba(56, 189, 248, 0.08);
}

/* Empty State */
.rekap-empty-row {
    text-align: center;
    padding: 48px 16px !important;
}
.empty-state-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.empty-icon {
    width: 44px;
    height: 44px;
    opacity: 0.35;
    color: var(--text-secondary);
}
.empty-text {
    font-size: 14px;
    font-weight: 500;
    color: var(--text-secondary);
}

/* Modal Custom Styling */
.modal-backdrop-custom {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    animation: fadeInModal 0.2s ease-out;
}
@keyframes fadeInModal {
    from { opacity: 0; }
    to { opacity: 1; }
}
.modal-dialog-custom {
    width: 100%;
    max-width: 780px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
}
.modal-content-custom {
    background: #FFFFFF;
    border-radius: 14px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    max-height: 90vh;
    overflow: hidden;
    border: 1px solid var(--border-color, #E2E8F0);
}
.dark-mode .modal-content-custom {
    background: #0F172A;
    border-color: #334155;
}
.modal-header-custom {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #E2E8F0;
}
.dark-mode .modal-header-custom {
    border-bottom-color: #1E293B;
}
.modal-title-wrap {
    display: flex;
    align-items: center;
    gap: 12px;
}
.modal-avatar-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: rgba(0, 82, 255, 0.08);
    color: var(--electric-blue, #0052FF);
    display: flex;
    align-items: center;
    justify-content: center;
}
.modal-avatar-icon svg {
    width: 22px;
    height: 22px;
}
.modal-title-text {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary, #0F172A);
}
.modal-subtitle-text {
    margin: 2px 0 0;
    font-size: 12px;
    color: var(--text-secondary, #64748B);
}
.modal-close-btn {
    background: none;
    border: none;
    font-size: 26px;
    line-height: 1;
    color: #94A3B8;
    cursor: pointer;
    transition: color 0.15s ease;
}
.modal-close-btn:hover {
    color: #0F172A;
}
.dark-mode .modal-close-btn:hover {
    color: #F8FAFC;
}
.modal-body-custom {
    padding: 20px 24px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 18px;
}
.modal-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
    gap: 10px;
}
.modal-stat-box {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 10px 12px;
    text-align: center;
}
.dark-mode .modal-stat-box {
    background: #1E293B;
    border-color: #334155;
}
.modal-stat-title {
    font-size: 11px;
    font-weight: 600;
    color: #64748B;
    display: block;
}
.modal-stat-value {
    font-size: 18px;
    font-weight: 800;
    color: #0F172A;
    display: block;
    margin-top: 2px;
}
.dark-mode .modal-stat-value {
    color: #F8FAFC;
}
.box-h { border-color: rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.05); }
.box-t { border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05); }
.box-i { border-color: rgba(59, 130, 246, 0.3); background: rgba(59, 130, 246, 0.05); }
.box-s { border-color: rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05); }
.box-a { border-color: rgba(239, 68, 68, 0.3); background: rgba(239, 68, 68, 0.05); }

.text-h { color: #059669 !important; }
.text-t { color: #D97706 !important; }
.text-i { color: #2563EB !important; }
.text-s { color: #D97706 !important; }
.text-a { color: #EF4444 !important; }

/* Progress Bar */
.progress-wrap-box {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 12px 14px;
}
.dark-mode .progress-wrap-box {
    background: #1E293B;
    border-color: #334155;
}
.progress-header-row {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 6px;
}
.progress-label {
    color: #475569;
}
.dark-mode .progress-label {
    color: #CBD5E1;
}
.progress-percent-val {
    font-weight: 700;
    color: #059669;
}
.progress-bar-bg {
    width: 100%;
    height: 8px;
    background: #E2E8F0;
    border-radius: 6px;
    overflow: hidden;
}
.dark-mode .progress-bar-bg {
    background: #334155;
}
.progress-bar-fill {
    height: 100%;
    border-radius: 6px;
    background: #10B981;
    transition: width 0.3s ease;
}

/* History Table in Modal */
.history-section-title {
    margin: 0;
    font-size: 13.5px;
    font-weight: 700;
    color: var(--text-primary);
}
.history-table-wrapper {
    max-height: 280px;
    overflow-y: auto;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
}
.dark-mode .history-table-wrapper {
    border-color: #334155;
}
.history-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12px;
}
.history-table thead tr th {
    position: sticky;
    top: 0;
    background: #F1F5F9;
    color: #475569;
    font-weight: 700;
    padding: 8px 10px;
    border-bottom: 1px solid #E2E8F0;
    text-align: left;
    z-index: 1;
}
.dark-mode .history-table thead tr th {
    background: #1E293B;
    color: #94A3B8;
    border-bottom-color: #334155;
}
.history-table tbody tr td {
    padding: 8px 10px;
    border-bottom: 1px solid #F1F5F9;
    color: var(--text-primary);
}
.dark-mode .history-table tbody tr td {
    border-bottom-color: #1E293B;
}
.modal-footer-custom {
    padding: 12px 24px;
    border-top: 1px solid #E2E8F0;
    display: flex;
    justify-content: flex-end;
}
.dark-mode .modal-footer-custom {
    border-top-color: #1E293B;
}
</style>

<script>
    // Data JSON untuk generator teks laporan WhatsApp
    window.SEKOLAH_REKAP_DATA = {
        lembaga: {!! json_encode(strtoupper($activeRoom ?? 'SMP AL MUTTAQIN')) !!},
        kelas: {!! json_encode(\App\Http\Controllers\PlottingKelasController::toRomanDisplay($selectedKelas ?? '-')) !!},
        bulanText: {!! json_encode($bulan ? ($bulanList[$bulan] ?? 'Bulan ' . $bulan) : 'Semua Bulan') !!},
        tahun: {!! json_encode($tahun) !!},
        totalPertemuan: {{ (int) $totalPertemuan }},
        totalSemuaHadir: {{ (int) $totalSemuaHadir }},
        totalSemuaSakit: {{ (int) $totalSemuaSakit }},
        totalSemuaIzin: {{ (int) $totalSemuaIzin }},
        totalSemuaAlpa: {{ (int) $totalSemuaAlpa }},
        jurnals: {!! json_encode($jurnals->map(function($j) {
            return [
                'id' => $j->id,
                'tanggal' => $j->tgl_full,
                'mapel' => $j->mata_pelajaran,
                'kode_mapel' => $j->kode_mapel,
                'guru' => $j->nama_guru,
                'jam_ke' => $j->jam_ke,
                'materi' => $j->materi,
            ];
        })) !!},
        siswas: {!! json_encode(collect($rekapSiswa)->map(function($item) {
            return [
                'nama' => $item->siswa->nama_lengkap,
                'nisn' => $item->nisn,
                'hadir' => $item->hadir,
                'sakit' => $item->sakit,
                'izin' => $item->izin,
                'alpa' => $item->alpa,
                'persentase' => $item->persentase,
                'absensi' => $item->pertemuan_absensi,
            ];
        })) !!}
    };

    function setupWaModalDom() {
        var modal = document.getElementById('modalLaporanWA');
        if (modal && modal.parentElement !== document.body) {
            document.body.appendChild(modal);
        }
    }

    function openWaModal() {
        setupWaModalDom();
        var modal = document.getElementById('modalLaporanWA');
        if (modal) {
            modal.style.setProperty('display', 'flex', 'important');
            document.body.style.overflow = 'hidden';
            renderSekolahWaPreview();
        }
    }

    function closeWaModal() {
        var modal = document.getElementById('modalLaporanWA');
        if (modal) {
            modal.style.setProperty('display', 'none', 'important');
            document.body.style.overflow = '';
        }
    }

    function handleWaModeChange() {
        var modeSelect = document.getElementById('waModeSelect');
        var mode = modeSelect ? modeSelect.value : 'rekap';
        var sesiWrap = document.getElementById('waSesiWrap');
        if (sesiWrap) {
            sesiWrap.style.display = mode === 'sesi' ? 'block' : 'none';
        }
        renderSekolahWaPreview();
    }

    function renderSekolahWaPreview() {
        var d = window.SEKOLAH_REKAP_DATA;
        if (!d) return;

        var textarea = document.getElementById('waTextarea');
        if (!textarea) return;

        var mode = document.getElementById('waModeSelect') ? document.getElementById('waModeSelect').value : 'rekap';

        var namaLembaga = d.lembaga || 'SMP/SMA AL MUTTAQIN';
        if (!namaLembaga.includes('AL MUTTAQIN')) {
            namaLembaga = namaLembaga + ' AL MUTTAQIN';
        }

        if (mode === 'sesi') {
            var sesiSelect = document.getElementById('waSesiSelect');
            var selectedJurnalId = sesiSelect ? parseInt(sesiSelect.value) : null;
            var jurnal = null;
            if (d.jurnals && d.jurnals.length > 0) {
                jurnal = d.jurnals.find(function(j) { return j.id === selectedJurnalId; }) || d.jurnals[0];
            }

            if (!jurnal) {
                textarea.value = "Belum ada data sesi pertemuan pada kelas dan periode ini.";
                return;
            }

            var hadirList = [];
            var sakitList = [];
            var izinList = [];
            var alpaList = [];

            (d.siswas || []).forEach(function(s) {
                var st = s.absensi ? s.absensi[jurnal.id] : null;
                if (st === 'hadir') {
                    hadirList.push(s.nama);
                } else if (st === 'sakit') {
                    sakitList.push(s.nama);
                } else if (st === 'izin') {
                    izinList.push(s.nama);
                } else if (st === 'alpa') {
                    alpaList.push(s.nama);
                }
            });

            var lines = [];
            lines.push("*LAPORAN PRESENSI KELAS*");
            lines.push("*" + namaLembaga + "*");
            lines.push("========================================");
            lines.push("📚 *Kelas*        : Kelas " + d.kelas);
            lines.push("📅 *Tanggal*      : " + jurnal.tanggal);
            lines.push("📖 *Mata Pelajaran*: " + jurnal.mapel + " (" + jurnal.kode_mapel + ")");
            lines.push("⏰ *Jam Pelajaran*: " + (jurnal.jam_ke || '-'));
            lines.push("👨‍🏫 *Guru Pengajar*: " + jurnal.guru);
            if (jurnal.materi) {
                lines.push("📝 *Materi*       : " + jurnal.materi);
            }
            lines.push("========================================");
            lines.push("*Ringkasan Kehadiran:*");
            lines.push("👥 Total Siswa : " + (d.siswas ? d.siswas.length : 0) + " Siswa");
            lines.push("✅ Hadir       : " + hadirList.length + " Siswa");
            lines.push("🤒 Sakit       : " + sakitList.length + " Siswa");
            lines.push("📩 Izin        : " + izinList.length + " Siswa");
            lines.push("❌ Alpa        : " + alpaList.length + " Siswa");
            lines.push("----------------------------------------");

            if (sakitList.length > 0 || izinList.length > 0 || alpaList.length > 0) {
                lines.push("*Keterangan Siswa Tidak Hadir:*");
                if (sakitList.length > 0) lines.push("• *Sakit*: " + sakitList.join(', '));
                if (izinList.length > 0) lines.push("• *Izin*: " + izinList.join(', '));
                if (alpaList.length > 0) lines.push("• *Alpa*: " + alpaList.join(', '));
                lines.push("----------------------------------------");
            }

            lines.push("*Daftar Absensi Siswa:*");
            (d.siswas || []).forEach(function(s, idx) {
                var st = s.absensi ? s.absensi[jurnal.id] : null;
                var mark = '➖ Belum ada data';
                if (st === 'hadir') mark = '✅ Hadir';
                else if (st === 'sakit') mark = '🤒 Sakit';
                else if (st === 'izin') mark = '📩 Izin';
                else if (st === 'alpa') mark = '❌ Alpa';
                lines.push((idx + 1) + ". " + s.nama + " : " + mark);
            });

            lines.push("========================================");
            lines.push("_Laporan otomatis dari Sistem Presensi Al Muttaqin_");

            textarea.value = lines.join("\n");
        } else {
            // Mode Rekap Keseluruhan
            var lines = [];
            lines.push("*REKAPITULASI ABSENSI SISWA*");
            lines.push("*" + namaLembaga + "*");
            lines.push("========================================");
            lines.push("📚 *Kelas*          : Kelas " + d.kelas);
            lines.push("📅 *Periode*        : " + d.bulanText + " " + d.tahun);
            lines.push("👥 *Total Siswa*    : " + (d.siswas ? d.siswas.length : 0) + " Siswa");
            lines.push("📊 *Total Pertemuan*: " + d.totalPertemuan + " Sesi");
            lines.push("========================================");
            lines.push("*Akumulasi Kehadiran Kelas:*");
            lines.push("✅ Total Hadir : " + d.totalSemuaHadir);
            lines.push("🤒 Total Sakit : " + d.totalSemuaSakit);
            lines.push("📩 Total Izin  : " + d.totalSemuaIzin);
            lines.push("❌ Total Alpa  : " + d.totalSemuaAlpa);
            lines.push("----------------------------------------");

            var butuhPerhatian = (d.siswas || []).filter(function(s) {
                return (s.alpa > 0 || s.sakit > 0 || s.izin > 0);
            });

            if (butuhPerhatian.length > 0) {
                lines.push("*Catatan Siswa (Perlu Perhatian):*");
                butuhPerhatian.forEach(function(s, idx) {
                    var notes = [];
                    if (s.sakit > 0) notes.push(s.sakit + "S");
                    if (s.izin > 0) notes.push(s.izin + "I");
                    if (s.alpa > 0) notes.push(s.alpa + "A");
                    lines.push((idx + 1) + ". " + s.nama + " (" + notes.join(', ') + ")");
                });
                lines.push("----------------------------------------");
            }

            lines.push("*Daftar Kehadiran Siswa:*");
            (d.siswas || []).forEach(function(s, idx) {
                lines.push((idx + 1) + ". " + s.nama + " - H:" + s.hadir + " S:" + s.sakit + " I:" + s.izin + " A:" + s.alpa + " (" + s.persentase + "%)");
            });

            lines.push("========================================");
            lines.push("_Laporan otomatis dari Sistem Presensi Al Muttaqin_");

            textarea.value = lines.join("\n");
        }
    }

    function copyWaText() {
        var textarea = document.getElementById('waTextarea');
        if (!textarea) return;
        textarea.select();
        textarea.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(textarea.value).then(function() {
            var btnText = document.getElementById('btnSalinWaText');
            if (btnText) btnText.textContent = 'Tersalin!';
            var notif = document.getElementById('copyNotif');
            if (notif) {
                notif.style.display = 'block';
                setTimeout(function() {
                    notif.style.display = 'none';
                    if (btnText) btnText.textContent = 'Salin Teks';
                }, 3000);
            }
        });
    }

    function openWaWeb() {
        var textarea = document.getElementById('waTextarea');
        if (!textarea) return;
        var encoded = encodeURIComponent(textarea.value);
        window.open('https://web.whatsapp.com/send?text=' + encoded, '_blank');
    }

    window.openWaModal = openWaModal;
    window.closeWaModal = closeWaModal;
    window.copyWaText = copyWaText;
    window.openWaWeb = openWaWeb;
    window.handleWaModeChange = handleWaModeChange;
    window.renderSekolahWaPreview = renderSekolahWaPreview;

    document.addEventListener('click', function(e) {
        var waModal = document.getElementById('modalLaporanWA');
        if (waModal && e.target === waModal) {
            closeWaModal();
        }
    });

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('modalDetailSiswa');
    const closeBtn = document.getElementById('modalCloseBtn');
    const closeFooterBtn = document.getElementById('modalCloseFooterBtn');

    function closeModal() {
        if (modal) {
            modal.style.display = 'none';
        }
    }

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (closeFooterBtn) closeFooterBtn.addEventListener('click', closeModal);

    // Close when clicking outside content
    if (modal) {
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });
    }

    // Attach listeners to all Detail buttons
    const detailButtons = document.querySelectorAll('.js-btn-detail');
    detailButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const dataNama = this.getAttribute('data-nama') || '-';
            const dataNisn = this.getAttribute('data-nisn') || '-';
            const dataKelas = this.getAttribute('data-kelas') || '-';
            const dataJenjang = this.getAttribute('data-jenjang') || '';
            const dataPertemuan = this.getAttribute('data-pertemuan') || '0';
            const dataHadir = this.getAttribute('data-hadir') || '0';
            const dataTerlambat = this.getAttribute('data-terlambat') || '0';
            const dataIzin = this.getAttribute('data-izin') || '0';
            const dataSakit = this.getAttribute('data-sakit') || '0';
            const dataAlpa = this.getAttribute('data-alpa') || '0';
            const dataPersentase = this.getAttribute('data-persentase') || '0';
            
            let riwayat = [];
            try {
                riwayat = JSON.parse(this.getAttribute('data-riwayat') || '[]');
            } catch (e) {
                riwayat = [];
            }

            // Set Header
            document.getElementById('modalNamaSiswa').textContent = dataNama;
            document.getElementById('modalSubInfo').textContent = `NISN: ${dataNisn} • Kelas ${dataKelas}${dataJenjang ? ' • ' + dataJenjang : ''}`;

            // Set Stats Box
            document.getElementById('modalTotalSesi').textContent = dataPertemuan;
            document.getElementById('modalHadir').textContent = dataHadir;
            document.getElementById('modalTerlambat').textContent = dataTerlambat;
            document.getElementById('modalIzin').textContent = dataIzin;
            document.getElementById('modalSakit').textContent = dataSakit;
            document.getElementById('modalAlpa').textContent = dataAlpa;

            // Set Progress Bar
            const pct = parseFloat(dataPersentase) || 0;
            const pctElem = document.getElementById('modalPersentase');
            const barElem = document.getElementById('modalProgressBar');
            pctElem.textContent = pct + '%';
            barElem.style.width = Math.min(100, Math.max(0, pct)) + '%';

            if (pct >= 85) {
                barElem.style.background = '#10B981';
                pctElem.style.color = '#059669';
            } else if (pct >= 70) {
                barElem.style.background = '#F59E0B';
                pctElem.style.color = '#D97706';
            } else {
                barElem.style.background = '#EF4444';
                pctElem.style.color = '#EF4444';
            }

            // Populate History Table
            const tbody = document.getElementById('modalRiwayatTableBody');
            tbody.innerHTML = '';

            if (riwayat.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="8" style="text-align:center; padding:24px; color:#94A3B8;">
                            Belum ada riwayat pertemuan untuk siswa ini.
                        </td>
                    </tr>
                `;
            } else {
                riwayat.forEach((item, idx) => {
                    let badgeStatus = '<span style="color:#94A3B8;">-</span>';
                    if (item.status === 'hadir') {
                        badgeStatus = '<span class="absen-pill pill-h" style="width:24px; height:24px; font-size:10.5px;">H</span>';
                    } else if (item.status === 'sakit') {
                        badgeStatus = '<span class="absen-pill pill-s" style="width:24px; height:24px; font-size:10.5px;">S</span>';
                    } else if (item.status === 'izin') {
                        badgeStatus = '<span class="absen-pill pill-i" style="width:24px; height:24px; font-size:10.5px;">I</span>';
                    } else if (item.status === 'alpa') {
                        badgeStatus = '<span class="absen-pill pill-a" style="width:24px; height:24px; font-size:10.5px;">A</span>';
                    }

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td style="text-align:center; font-weight:600; color:#64748B;">${idx + 1}</td>
                        <td>${item.tanggal || '-'}</td>
                        <td style="font-weight:600;">${item.mata_pelajaran || '-'} <span style="font-size:10.5px; color:#64748B; font-weight:normal;">(${item.kode_mapel || '-'})</span></td>
                        <td style="text-align:center;">${item.jam_ke || '-'}</td>
                        <td>${item.guru || '-'}</td>
                        <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${item.materi || ''}">${item.materi || '-'}</td>
                        <td style="text-align:center;">${badgeStatus}</td>
                        <td style="font-size:11.5px; color:#64748B;">${item.keterangan || '-'}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }

            modal.style.display = 'flex';
        });
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeModal();
            closeWaModal();
        }
    });
});
</script>
@endsection
