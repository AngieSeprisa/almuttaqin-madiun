@extends('layouts.app')

@section('title', 'Rekap Presensi Sholat')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px;">
    <a href="{{ route('rekap.sholat.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
       class="btn btn-secondary"
       style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:8px 14px; font-size:13px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
            <polyline points="7 10 12 15 17 10"></polyline>
            <line x1="12" y1="15" x2="12" y2="3"></line>
        </svg>
        <span>Ekspor Excel</span>
    </a>

    <a href="{{ route('rekap.sholat.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
       target="_blank"
       class="btn btn-primary"
       style="display:inline-flex; align-items:center; gap:8px; text-decoration:none; padding:8px 14px; font-size:13px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
            <polyline points="6 9 6 2 18 2 18 9"></polyline>
            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
            <rect x="6" y="14" width="12" height="8"></rect>
        </svg>
        <span>Cetak Laporan</span>
    </a>
</div>
@endsection

@section('content')
<div class="rekap-sekolah-container">

    {{-- 1. Kartu Ringkasan Statistik (5 Stat Cards Bersih Tanpa Deskripsi Panjang) --}}
    <div class="stat-summary-grid">
        <div class="form-section-card stat-summary-card">
            <span class="stat-summary-label">TOTAL SANTRI</span>
            <div class="stat-summary-val-wrap">
                <span class="stat-summary-num text-electric-blue">{{ $stats['total_target'] }}</span>
                <span class="stat-summary-sub">Santri</span>
            </div>
        </div>

        <div class="form-section-card stat-summary-card">
            <span class="stat-summary-label">HADIR TEPAT (H)</span>
            <div class="stat-summary-val-wrap">
                <span class="stat-summary-num" style="color:#059669;">{{ $stats['hadir'] }}</span>
                <span class="stat-summary-sub">Sesi</span>
            </div>
        </div>

        <div class="form-section-card stat-summary-card">
            <span class="stat-summary-label">TERLAMBAT (T)</span>
            <div class="stat-summary-val-wrap">
                <span class="stat-summary-num" style="color:#D97706;">{{ $stats['terlambat'] }}</span>
                <span class="stat-summary-sub">Sesi</span>
            </div>
        </div>

        <div class="form-section-card stat-summary-card">
            <span class="stat-summary-label">IZIN / SAKIT (I)</span>
            <div class="stat-summary-val-wrap">
                <span class="stat-summary-num" style="color:#2563EB;">{{ $stats['izin_sakit'] }}</span>
                <span class="stat-summary-sub">Sesi</span>
            </div>
        </div>

        <div class="form-section-card stat-summary-card">
            <span class="stat-summary-label">ALPHA (A)</span>
            <div class="stat-summary-val-wrap">
                <span class="stat-summary-num" style="color:#DC2626;">{{ $stats['alfa'] }}</span>
                <span class="stat-summary-sub">Sesi</span>
            </div>
        </div>
    </div>

    {{-- 2. Filter Bar Terpadu (Gaya Tabel Menu Lainnya) --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('rekap.sholat.index') }}" method="GET" class="filter-form" id="filterFormSholat">
            <div class="filter-group-left">

                {{-- Rentang Waktu (Harian / Mingguan / Bulanan) --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12 6 12 12 16 14"></polyline>
                        </svg>
                    </div>
                    <select name="rentang" id="selectRentang" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="harian" {{ $rentang === 'harian' ? 'selected' : '' }}>Harian</option>
                        <option value="mingguan" {{ $rentang === 'mingguan' ? 'selected' : '' }}>Mingguan</option>
                        <option value="bulanan" {{ $rentang === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>

                {{-- Input Tanggal (Mode Harian & Mingguan) --}}
                @if($rentang !== 'bulanan')
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="16" y1="2" x2="16" y2="6"></line>
                            <line x1="8" y1="2" x2="8" y2="6"></line>
                            <line x1="3" y1="10" x2="21" y2="10"></line>
                        </svg>
                    </div>
                    <input type="date" name="tanggal" value="{{ $tanggal }}"
                           class="form-control"
                           style="border:none; background:transparent; font-size:13px; font-weight:600; padding-left:36px; padding-right:12px; height:38px; outline:none;"
                           onchange="this.form.submit()">
                </div>
                @else
                {{-- Pilihan Bulan & Tahun (Mode Bulanan) --}}
                <div class="filter-select-wrap">
                    <select name="bulan" class="form-select filter-select" onchange="this.form.submit()">
                        @foreach($namaBulanList as $num => $lbl)
                            <option value="{{ $num }}" {{ (int) $bulan === (int) $num ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>
                <div class="filter-select-wrap">
                    <select name="tahun" class="form-select filter-select" style="min-width:90px;" onchange="this.form.submit()">
                        @for($y = date('Y') + 1; $y >= 2023; $y--)
                            <option value="{{ $y }}" {{ (int) $tahun === (int) $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endfor
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>
                @endif

                {{-- Filter Sesi Sholat --}}
                <div class="filter-select-wrap">
                    <select name="sesi_sholat" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="semua" {{ $filterSesiSholat === 'semua' ? 'selected' : '' }}>Semua Sholat</option>
                        <option value="subuh" {{ $filterSesiSholat === 'subuh' ? 'selected' : '' }}>Subuh</option>
                        <option value="dzuhur" {{ $filterSesiSholat === 'dzuhur' ? 'selected' : '' }}>Dzuhur</option>
                        <option value="ashar" {{ $filterSesiSholat === 'ashar' ? 'selected' : '' }}>Ashar</option>
                        <option value="maghrib" {{ $filterSesiSholat === 'maghrib' ? 'selected' : '' }}>Maghrib</option>
                        <option value="isya" {{ $filterSesiSholat === 'isya' ? 'selected' : '' }}>Isya</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>

                {{-- Filter Kamar --}}
                <div class="filter-select-wrap">
                    <select name="kamar" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="">Semua Kamar</option>
                        @foreach($kamarList as $kmr)
                            <option value="{{ $kmr }}" {{ $filterKamar === $kmr ? 'selected' : '' }}>{{ $kmr }}</option>
                        @endforeach
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>

                {{-- Filter Jenjang (SMP / SMA / Reguler) --}}
                <div class="filter-select-wrap">
                    <select name="jenjang" class="form-select filter-select" onchange="this.form.submit()">
                        <option value="semua" {{ $filterJenjang === 'semua' ? 'selected' : '' }}>Semua Jenjang</option>
                        <option value="smp" {{ $filterJenjang === 'smp' ? 'selected' : '' }}>SMP</option>
                        <option value="sma" {{ $filterJenjang === 'sma' ? 'selected' : '' }}>SMA</option>
                        <option value="reguler" {{ $filterJenjang === 'reguler' ? 'selected' : '' }}>Reguler</option>
                    </select>
                    <div class="filter-select-icon-right">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;"><polyline points="6 9 12 15 18 9"></polyline></svg>
                    </div>
                </div>

                {{-- Reset Button --}}
                @if(request()->hasAny(['kamar', 'jenjang', 'sesi_sholat', 'search', 'bulan', 'tahun']) || request('rentang') === 'mingguan' || request('rentang') === 'bulanan')
                <a href="{{ route('rekap.sholat.index') }}" class="btn btn-secondary filter-btn-reset">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Reset
                </a>
                @endif
            </div>

            {{-- Pencarian Nama Siswa / NISN --}}
            <div class="filter-search-wrap">
                <input type="text"
                       name="search"
                       id="inputSearchNama"
                       value="{{ $search }}"
                       class="form-control filter-search-input"
                       placeholder="Cari nama santri / NISN...">
                <button type="submit" class="btn btn-primary filter-search-btn">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </form>
    </div>

    {{-- 3. Tabel Rekap Presensi Sholat (Gaya Tabel Rekap Sekolah) --}}
    <div class="table-card rekap-matrix-card">
        <div class="table-wrapper rekap-scroll-wrap">
            <table class="rekap-matrix-table" id="tablePresensiSholat">
                <thead>
                    {{-- Baris 1: Header Atas --}}
                    <tr>
                        <th rowspan="2" class="th-fixed th-col-no">NO</th>
                        <th rowspan="2" class="th-fixed th-col-nama">NAMA SANTRI</th>
                        <th rowspan="2" style="min-width:140px; text-align:left; padding-left:12px;">KAMAR / JENJANG</th>
                        <th colspan="5" class="th-date-group" style="text-align:center;">
                            WAKTU SHOLAT
                            @if($rentang === 'harian')
                                &mdash; {{ $tanggalCarbon->translatedFormat('d F Y') }}
                            @elseif($rentang === 'mingguan')
                                &mdash; {{ $startDate->translatedFormat('d M') }} s/d {{ $endDate->translatedFormat('d M Y') }}
                            @else
                                &mdash; {{ $namaBulan }} {{ $tahun }}
                            @endif
                        </th>
                        <th colspan="4" class="th-stat-summary" style="text-align:center; background:#F1F5F9; color:#334155;">REKAPAN</th>
                    </tr>

                    {{-- Baris 2: Sub-Header 5 Waktu Sholat & Rekapan H T I A --}}
                    <tr>
                        <th class="th-mapel-code" style="text-align:center; min-width:70px; {{ $filterSesiSholat === 'subuh' ? 'background:#EFF6FF !important;' : '' }}">
                            <span class="mapel-code" style="font-size:11.5px;">SUBUH</span>
                        </th>
                        <th class="th-mapel-code" style="text-align:center; min-width:70px; {{ $filterSesiSholat === 'dzuhur' ? 'background:#EFF6FF !important;' : '' }}">
                            <span class="mapel-code" style="font-size:11.5px;">DZUHUR</span>
                        </th>
                        <th class="th-mapel-code" style="text-align:center; min-width:70px; {{ $filterSesiSholat === 'ashar' ? 'background:#EFF6FF !important;' : '' }}">
                            <span class="mapel-code" style="font-size:11.5px;">ASHAR</span>
                        </th>
                        <th class="th-mapel-code" style="text-align:center; min-width:70px; {{ $filterSesiSholat === 'maghrib' ? 'background:#EFF6FF !important;' : '' }}">
                            <span class="mapel-code" style="font-size:11.5px;">MAGHRIB</span>
                        </th>
                        <th class="th-mapel-code" style="text-align:center; min-width:70px; {{ $filterSesiSholat === 'isya' ? 'background:#EFF6FF !important;' : '' }}">
                            <span class="mapel-code" style="font-size:11.5px;">ISYA</span>
                        </th>

                        {{-- Kolom Rekapan H, T, I, A --}}
                        <th class="th-stat-summary col-stat-h" title="Hadir Tepat Waktu">H</th>
                        <th class="th-stat-summary col-stat-t" title="Terlambat">T</th>
                        <th class="th-stat-summary col-stat-i" title="Izin / Sakit">I</th>
                        <th class="th-stat-summary col-stat-a" title="Alpha">A</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $idx => $row)
                    <tr class="row-santri-sholat" data-search="{{ mb_strtolower($row['nama_lengkap'] . ' ' . $row['nisn'] . ' ' . $row['kamar'] . ' ' . $row['jenjang']) }}">
                        {{-- Kolom No --}}
                        <td class="td-col-no">{{ $idx + 1 }}</td>

                        {{-- Kolom Nama Santri & NISN --}}
                        <td class="td-col-nama">
                            <div class="siswa-nama-text">{{ $row['nama_lengkap'] }}</div>
                            <div class="siswa-nisn-text">NISN: {{ $row['nisn'] }}</div>
                        </td>

                        {{-- Kolom Kamar / Jenjang --}}
                        <td style="padding-left:12px;">
                            <div style="font-weight:600; font-size:13px; color:#1E293B;">{{ $row['kamar'] }}</div>
                            <div style="font-size:11.5px; color:#64748B;">{{ $row['jenjang'] }}</div>
                        </td>

                        {{-- 5 Waktu Sholat: Subuh, Dzuhur, Ashar, Maghrib, Isya --}}
                        @foreach(['subuh', 'dzuhur', 'ashar', 'maghrib', 'isya'] as $sKey)
                            @php
                                $shItem = $row['sholat'][$sKey] ?? null;
                                $code = $shItem['code'] ?? '-';
                                $disp = $shItem['display'] ?? '-';
                                $jam = $shItem['jam'] ?? null;
                            @endphp
                            <td class="td-matrix-cell" style="{{ $filterSesiSholat === $sKey ? 'background:#F8FAFC;' : '' }}">
                                @if($rentang === 'harian')
                                    @if($code === 'H')
                                        <span class="absen-pill pill-h" title="Hadir{{ $jam ? ' - ' . $jam : '' }}">{{ $jam ?: 'H' }}</span>
                                    @elseif($code === 'T')
                                        <span class="absen-pill pill-s" title="Terlambat{{ $jam ? ' - ' . $jam : '' }}">{{ $jam ?: 'T' }}</span>
                                    @elseif($code === 'I')
                                        <span class="absen-pill pill-i" title="Izin / Sakit">I</span>
                                    @elseif($code === 'A')
                                        <span class="absen-pill pill-a" title="Alpha">A</span>
                                    @else
                                        <span class="absen-pill-empty" title="Belum Berlangsung">&mdash;</span>
                                    @endif
                                @else
                                    {{-- Mode Mingguan / Bulanan --}}
                                    @if($disp !== '-')
                                        <span style="font-size:12px; font-weight:700; color:#334155; padding:3px 6px; background:#F1F5F9; border-radius:6px;">
                                            {{ $disp }}
                                        </span>
                                    @else
                                        <span class="absen-pill-empty">&mdash;</span>
                                    @endif
                                @endif
                            </td>
                        @endforeach

                        {{-- Kolom Total Rekapan H, T, I, A --}}
                        <td class="td-stat-val val-h">{{ $row['total_h'] }}</td>
                        <td class="td-stat-val val-t">{{ $row['total_t'] }}</td>
                        <td class="td-stat-val {{ $row['total_i'] > 0 ? 'val-i-active' : 'val-muted' }}">{{ $row['total_i'] }}</td>
                        <td class="td-stat-val {{ $row['total_a'] > 0 ? 'val-a-active' : 'val-muted' }}">{{ $row['total_a'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="rekap-empty-row">
                            <div class="empty-state-wrap">
                                <span class="empty-text">Tidak ada data presensi sholat santri yang sesuai filter.</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var searchInput = document.getElementById('inputSearchNama');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var q = (this.value || '').toLowerCase().trim();
                var rows = document.querySelectorAll('#tablePresensiSholat tbody tr.row-santri-sholat');
                rows.forEach(function (tr) {
                    var hay = tr.getAttribute('data-search') || '';
                    tr.style.display = (!q || hay.indexOf(q) !== -1) ? '' : 'none';
                });
            });
        }
    });
</script>
@endpush
@endsection
