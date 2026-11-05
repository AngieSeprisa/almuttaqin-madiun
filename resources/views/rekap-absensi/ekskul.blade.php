@extends('layouts.app')

@section('title', 'Rekap Presensi Ekstrakurikuler')

@section('header-actions')
<div style="display:flex; align-items:center; gap:8px;">
    {{-- Switcher Jenjang untuk Yayasan --}}
    @if(!empty($isYayasan) && isset($sekolahLembagas) && $sekolahLembagas->count() > 1)
    <div class="yayasan-switch-wrap">
        @foreach($sekolahLembagas as $lem)
            <a href="{{ route('rekap-absensi.ekskul', ['lembaga' => strtolower($lem->kode)]) }}"
               class="yayasan-tab-btn {{ ($kodeLembaga ?? '') === strtolower($lem->kode) ? 'active' : '' }}">
                {{ strtoupper($lem->singkatan ?: $lem->kode) }}
            </a>
        @endforeach
    </div>
    @endif

    @if($selectedEkskul && $matriksSiswa->isNotEmpty())
    <button type="button" class="btn-wa" onclick="openWaModal()" title="Buka &amp; Salin Format Laporan WhatsApp">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.41 1.56 1.56 2.41 3.63 2.41 5.83s-.85 4.27-2.41 5.83c-1.55 1.55-3.62 2.41-5.82 2.41-1.5 0-2.97-.39-4.27-1.13l-.3-.17-3.17.83.85-3.09-.2-.31c-.81-1.33-1.25-2.88-1.25-4.47 0-2.2 0.86-4.27 2.42-5.83 1.56-1.55 3.63-2.41 5.82-2.41m-2.64 5.34c-.16 0-.39.06-.6.28-.2.22-.79.77-.79 1.88s.81 2.18.92 2.33c.12.16 1.57 2.4 3.82 3.37.53.23.95.37 1.28.47.54.17 1.03.15 1.42.09.43-.06 1.33-.54 1.52-1.07.19-.52.19-.97.13-1.07-.06-.09-.22-.16-.47-.28-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.65.81-.8 1-.15.19-.3.21-.55.09-.25-.12-1.07-.39-2.04-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.28.37-.42.12-.15.16-.25.25-.42.08-.16.04-.31-.02-.43s-.56-1.36-.77-1.86c-.2-.48-.41-.42-.56-.43h-.48z"/>
        </svg>
        <span>Laporan WA</span>
    </button>
    @endif
    <a href="{{ route('sekolah.ekskul.index', !empty($isYayasan) ? ['lembaga' => $kodeLembaga] : []) }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
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

    {{-- Filter Bar Card --}}
    <div class="form-section-card filter-card">
        <form action="{{ route('rekap-absensi.ekskul') }}" method="GET" class="filter-form">
            @if(!empty($isYayasan))
            <input type="hidden" name="lembaga" value="{{ $kodeLembaga }}">
            @endif
            <div class="filter-group-left">
                {{-- Filter Ekstrakurikuler --}}
                <div class="filter-select-wrap">
                    <div class="filter-select-icon-left">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                    </div>
                    <select name="ekskul_id" class="form-select filter-select" onchange="this.form.submit()">
                        @foreach($ekskuls as $ek)
                            <option value="{{ $ek->id }}" {{ ($selectedEkskul && $selectedEkskul->id == $ek->id) ? 'selected' : '' }}>
                                {{ $ek->nama_ekskul }}
                            </option>
                        @endforeach
                    </select>
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
                        @foreach($bulanList as $num => $namaB)
                            <option value="{{ $num }}" {{ (int) request('bulan', $bulan) === $num ? 'selected' : '' }}>
                                {{ $namaB }}
                            </option>
                        @endforeach
                    </select>
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
                </div>

                @if(request()->hasAny(['bulan', 'tahun']))
                <a href="{{ route('rekap-absensi.ekskul', ['ekskul_id' => $selectedEkskul?->id]) }}" class="btn btn-secondary filter-btn-reset">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px;">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    Reset
                </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Stat Summary Cards --}}
    <div class="stat-summary-grid">
        <div class="form-section-card stat-summary-card">
            <span class="stat-summary-label">Total Pertemuan Latihan</span>
            <div class="stat-summary-val-wrap">
                <span class="stat-summary-num text-electric-blue">{{ $totalPertemuan }}</span>
                <span class="stat-summary-sub">Pertemuan</span>
            </div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                Pembina: <strong>{{ $selectedEkskul?->nama_pembina ?: '-' }}</strong>
            </div>
        </div>

        <div class="form-section-card stat-summary-card">
            <span class="stat-summary-label">Total Anggota Siswa</span>
            <div class="stat-summary-val-wrap">
                <span class="stat-summary-num text-electric-blue">{{ $matriksSiswa->count() }}</span>
                <span class="stat-summary-sub">Peserta Aktif</span>
            </div>
            <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;">
                Jadwal: {{ $selectedEkskul?->hari ?: 'Belum diatur' }}
            </div>
        </div>

        @php
            $donutR = 42;
            $donutC = 2 * M_PI * $donutR;
            $donutOffset = $donutC * (1 - $persentaseKehadiran / 100);
        @endphp
        <div class="form-section-card stat-summary-card stat-donut-card">
            <span class="stat-summary-label">Akumulasi Kehadiran Peserta</span>
            <div class="donut-row">
                <div class="donut-wrap" role="img" aria-label="Persentase kehadiran {{ $persentaseKehadiran }} persen">
                    <svg viewBox="0 0 100 100" class="donut-svg">
                        <circle class="donut-track" cx="50" cy="50" r="{{ $donutR }}"></circle>
                        <circle class="donut-value" cx="50" cy="50" r="{{ $donutR }}"
                                stroke-dasharray="{{ $donutC }}" stroke-dashoffset="{{ $donutOffset }}"
                                transform="rotate(-90 50 50)"></circle>
                    </svg>
                    <span class="donut-text">{{ round($persentaseKehadiran) }}%</span>
                </div>
                <div class="donut-legend">
                    <span class="stat-pill-tag tag-h">H: {{ $totalHadir }}</span>
                    <span class="stat-pill-tag tag-s">S: {{ $totalSakit }}</span>
                    <span class="stat-pill-tag tag-i">I: {{ $totalIzin }}</span>
                    <span class="stat-pill-tag tag-a">A: {{ $totalAlpa }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabel Rekap Absensi Ekstrakurikuler --}}
    <div class="table-card rekap-matrix-card">
        <div class="table-wrapper rekap-scroll-wrap">
            <table class="rekap-matrix-table">
                <thead>
                    <tr>
                        <th class="th-fixed th-col-no">NO</th>
                        <th class="th-fixed th-col-nama">NAMA SISWA</th>
                        <th style="min-width:80px; text-align:center;">KELAS</th>

                        @forelse($jurnals as $j)
                            <th class="th-mapel-code" title="{{ $j->hari }}, {{ \Carbon\Carbon::parse($j->tanggal)->translatedFormat('d M Y') }} - Materi: {{ $j->materi }}">
                                <div style="font-size:10px; font-weight:600; color:#64748B;">{{ substr($j->hari, 0, 3) }}</div>
                                <div style="font-size:11.5px; font-weight:700;">{{ \Carbon\Carbon::parse($j->tanggal)->format('d/m') }}</div>
                            </th>
                        @empty
                            <th style="padding:10px 16px; font-size:11.5px; color:#64748B;">BELUM ADA PERTEMUAN</th>
                        @endforelse

                        <th class="th-stat-summary col-stat-h" title="Hadir">H</th>
                        <th class="th-stat-summary col-stat-s" title="Sakit">S</th>
                        <th class="th-stat-summary col-stat-i" title="Izin">I</th>
                        <th class="th-stat-summary col-stat-a" title="Alpa">A</th>
                        <th style="width:80px; text-align:center; font-size:11px; font-weight:700;">% HADIR</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($matriksSiswa as $index => $item)
                    <tr>
                        <td class="td-col-no">{{ $index + 1 }}</td>
                        <td class="td-col-nama">
                            <div class="siswa-nama-text">{{ $item['nama'] }}</div>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge-lembaga" style="background:#F1F5F9; color:#475569; font-weight:700; font-size:11px;">
                                {{ $item['kelas'] }}
                            </span>
                        </td>

                        {{-- Status per Pertemuan --}}
                        @if($totalPertemuan > 0)
                            @foreach($jurnals as $j)
                                @php
                                    $st = $item['pertemuan'][$j->id] ?? null;
                                @endphp
                                <td class="td-matrix-cell">
                                    @if($st === 'hadir')
                                        <span class="absen-pill pill-h" title="Hadir">H</span>
                                    @elseif($st === 'sakit')
                                        <span class="absen-pill pill-s" title="Sakit">S</span>
                                    @elseif($st === 'izin')
                                        <span class="absen-pill pill-i" title="Izin">I</span>
                                    @elseif($st === 'alpa')
                                        <span class="absen-pill pill-a" title="Alpa">A</span>
                                    @else
                                        <span style="color:#CBD5E1;">-</span>
                                    @endif
                                </td>
                            @endforeach
                        @else
                            <td style="text-align:center; color:#94A3B8; font-size:12px;">-</td>
                        @endif

                        <td class="td-stat-h">{{ $item['total_h'] }}</td>
                        <td class="td-stat-s">{{ $item['total_s'] }}</td>
                        <td class="td-stat-i">{{ $item['total_i'] }}</td>
                        <td class="td-stat-a">{{ $item['total_a'] }}</td>
                        <td style="text-align:center; font-weight:700; font-size:12px; color:{{ $item['persen_hadir'] >= 75 ? '#059669' : '#DC2626' }};">
                            {{ $item['persen_hadir'] }}%
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ 8 + max(1, $totalPertemuan) }}" style="text-align:center; padding:48px 16px; color:var(--text-secondary);">
                            <div style="display:flex; flex-direction:column; align-items:center; gap:8px;">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:36px;height:36px;opacity:0.4;">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="12" y1="8" x2="12" y2="12"></line>
                                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                </svg>
                                <span style="font-size:13.5px; font-weight:500;">Belum ada anggota terdaftar di ekstrakurikuler ini.</span>
                                <a href="{{ route('plotting-ekskul.index', !empty($isYayasan) ? ['lembaga' => $kodeLembaga] : []) }}" class="btn btn-sm btn-outline-primary" style="margin-top:4px;">
                                    Buka Plotting Ekstrakurikuler &rarr;
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- MODAL LAPORAN WHATSAPP --}}
@if($selectedEkskul)
<div class="modal-backdrop-custom" id="modalLaporanWA" style="display:none;">
    <div class="modal-dialog-custom" style="max-width: 680px;">
        <div class="modal-content-custom">
            
            <div class="modal-header-custom" style="background:#f8fafc;">
                <div class="modal-title-wrap">
                    <div class="modal-avatar-icon" style="background:#dcfce7; color:#16a34a;">
                        <svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor">
                            <path d="M12.04 2c-5.46 0-9.91 4.45-9.91 9.91 0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.816 9.816 0 0 0 12.04 2m.01 1.67c2.2 0 4.26.86 5.82 2.41 1.56 1.56 2.41 3.63 2.41 5.83s-.85 4.27-2.41 5.83c-1.55 1.55-3.62 2.41-5.82 2.41-1.5 0-2.97-.39-4.27-1.13l-.3-.17-3.17.83.85-3.09-.2-.31c-.81-1.33-1.25-2.88-1.25-4.47 0-2.2 0.86-4.27 2.42-5.83 1.56-1.55 3.63-2.41 5.82-2.41m-2.64 5.34c-.16 0-.39.06-.6.28-.2.22-.79.77-.79 1.88s.81 2.18.92 2.33c.12.16 1.57 2.4 3.82 3.37.53.23.95.37 1.28.47.54.17 1.03.15 1.42.09.43-.06 1.33-.54 1.52-1.07.19-.52.19-.97.13-1.07-.06-.09-.22-.16-.47-.28-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.12-.17.25-.65.81-.8 1-.15.19-.3.21-.55.09-.25-.12-1.07-.39-2.04-1.25-.75-.67-1.26-1.5-1.41-1.75-.15-.25-.02-.39.11-.51.11-.11.25-.28.37-.42.12-.15.16-.25.25-.42.08-.16.04-.31-.02-.43s-.56-1.36-.77-1.86c-.2-.48-.41-.42-.56-.43h-.48z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="modal-title-text" style="font-size:16px;">Laporan WhatsApp Presensi Ekstrakurikuler</h3>
                        <p class="modal-subtitle-text">
                            {{ $selectedEkskul->nama_ekskul }} &bull; {{ $bulan ? ($bulanList[$bulan] ?? 'Bulan ' . $bulan) : 'Semua Bulan' }} {{ $tahun }}
                        </p>
                    </div>
                </div>
                <button type="button" class="modal-close-btn" onclick="closeWaModal()">&times;</button>
            </div>

            <div class="modal-body-custom" style="padding:18px 24px; gap:14px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#64748b; text-transform:uppercase; margin-bottom:5px;">TEKS PESAN WHATSAPP</label>
                    <textarea id="waTextarea" spellcheck="false" style="width:100%; height:270px; font-family:ui-monospace, monospace; font-size:12px; line-height:1.6; padding:12px; border:1px solid #cbd5e1; border-radius:10px; resize:vertical; background:#f8fafc; color:#0f172a; box-sizing:border-box;"></textarea>
                </div>

                <div id="copyNotif" style="display:none; padding:8px 12px; background:#dcfce7; color:#15803d; font-size:12px; font-weight:600; border-radius:6px; text-align:center;">
                    Format WhatsApp berhasil disalin ke clipboard!
                </div>
            </div>

            <div class="modal-footer-custom" style="display:flex; align-items:center; justify-content:space-between; padding:14px 24px; background:#f8fafc;">
                <div style="font-size:11.5px; color:#64748b;">
                    Gunakan tombol salin atau buka langsung via WhatsApp Web.
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <button type="button" class="btn btn-secondary" onclick="closeWaModal()">Tutup</button>
                    <button type="button" class="btn-wa" onclick="copyWaText()" id="btnSalinWa">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span id="btnSalinWaText">Salin Teks</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</div>
@endif

<style>
.yayasan-switch-wrap {
    display: inline-flex; background: #F3F4F6; border-radius: 8px; padding: 3px; border: 1px solid #E5E7EB;
}
.dark-mode .yayasan-switch-wrap { background: #1E293B; border-color: #334155; }
.yayasan-tab-btn {
    padding: 5px 13px; font-size: 12px; font-weight: 700; color: #6B7280;
    text-decoration: none; border-radius: 6px; transition: all 0.15s ease;
}
.yayasan-tab-btn.active { background: #0052FF; color: #FFFFFF; }

.rekap-sekolah-container { display: flex; flex-direction: column; gap: 18px; width: 100%; }
.stat-summary-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
.stat-summary-card { padding: 16px 20px; }
.stat-summary-label { font-size: 12px; font-weight: 600; color: var(--text-secondary); display: block; }
.stat-summary-val-wrap { display: flex; align-items: baseline; gap: 8px; margin-top: 4px; }
.stat-summary-num { font-size: 24px; font-weight: 800; color: var(--text-primary); }
.stat-summary-sub { font-size: 12px; color: var(--text-secondary); }
.text-electric-blue { color: var(--electric-blue, #0052FF); }

.stat-pill-tag { font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 8px; }
.stat-pill-tag.tag-h { background: rgba(16, 185, 129, 0.12); color: #059669; }
.stat-pill-tag.tag-s { background: rgba(245, 158, 11, 0.12); color: #D97706; }
.stat-pill-tag.tag-i { background: rgba(37, 99, 235, 0.12); color: #2563EB; }
.stat-pill-tag.tag-a { background: rgba(239, 68, 68, 0.12); color: #EF4444; }

.stat-donut-card { padding: 14px 18px; }
.donut-row { display: flex; align-items: center; gap: 16px; margin-top: 8px; }
.donut-wrap { position: relative; width: 78px; height: 78px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.donut-svg { width: 100%; height: 100%; }
.donut-track { fill: none; stroke: #E2E8F0; stroke-width: 13; }
.donut-value { fill: none; stroke: #3B82F6; stroke-width: 13; stroke-linecap: butt; }
.donut-text { position: absolute; font-size: 16px; font-weight: 800; color: #3B82F6; }
.donut-legend { display: grid; grid-template-columns: repeat(2, auto); gap: 6px 8px; }

.rekap-matrix-card { overflow: hidden; background: var(--bg-card, #FFFFFF); border: 1px solid var(--border-color, #E2E8F0); border-radius: 12px; }
.rekap-scroll-wrap { overflow-x: auto; width: 100%; }
.rekap-matrix-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; white-space: nowrap; }
.rekap-matrix-table th { background: #F8FAFC; color: #475569; font-size: 11.5px; font-weight: 700; text-align: center; padding: 9px 8px; border-bottom: 1px solid #E2E8F0; }
.dark-mode .rekap-matrix-table th { background: #0F172A; border-bottom-color: #1E293B; color: #94A3B8; }
.rekap-matrix-table td { padding: 8px 10px; border-bottom: 1px solid #F1F5F9; vertical-align: middle; }
.dark-mode .rekap-matrix-table td { border-bottom-color: #1E293B; }

.th-col-no, .td-col-no { width: 45px; text-align: center; }
.th-col-nama, .td-col-nama { min-width: 200px; text-align: left; }
.siswa-nama-text { font-weight: 600; color: var(--text-primary); }

.th-mapel-code { min-width: 50px; text-align: center; padding: 4px 6px; }
.td-matrix-cell { text-align: center; padding: 4px !important; }

.absen-pill {
    display: inline-flex; align-items: center; justify-content: center;
    width: 24px; height: 24px; border-radius: 6px; font-size: 11px; font-weight: 800;
}
.pill-h { background: rgba(16, 185, 129, 0.15); color: #059669; }
.pill-s { background: rgba(245, 158, 11, 0.15); color: #D97706; }
.pill-i { background: rgba(37, 99, 235, 0.15); color: #2563EB; }
.pill-a { background: rgba(239, 68, 68, 0.15); color: #DC2626; }

.th-stat-summary { width: 36px; text-align: center; font-size: 11px; font-weight: 800; }
.col-stat-h, .td-stat-h { color: #059669; background: rgba(16, 185, 129, 0.05); text-align: center; font-weight: 700; }
.col-stat-s, .td-stat-s { color: #D97706; background: rgba(245, 158, 11, 0.05); text-align: center; font-weight: 700; }
.col-stat-i, .td-stat-i { color: #2563EB; background: rgba(37, 99, 235, 0.05); text-align: center; font-weight: 700; }
.col-stat-a, .td-stat-a { color: #DC2626; background: rgba(239, 68, 68, 0.05); text-align: center; font-weight: 700; }

.modal-backdrop-custom {
    position: fixed; inset: 0; z-index: 9999; background: rgba(15, 23, 42, 0.5);
    display: flex; align-items: center; justify-content: center; padding: 16px;
}
.modal-dialog-custom { width: 100%; }
.modal-content-custom { background: #FFFFFF; border-radius: 14px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
.modal-header-custom { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #E2E8F0; }
.modal-title-wrap { display: flex; align-items: center; gap: 12px; }
.modal-avatar-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
.modal-title-text { margin: 0; font-size: 15px; font-weight: 700; }
.modal-subtitle-text { margin: 2px 0 0; font-size: 12px; color: #64748B; }
.modal-close-btn { background: transparent; border: none; font-size: 22px; color: #94A3B8; cursor: pointer; }
.btn-wa {
    display: inline-flex; align-items: center; gap: 6px; padding: 7px 14px;
    background: #16A34A; color: #FFFFFF; border: none; border-radius: 8px;
    font-size: 13px; font-weight: 600; cursor: pointer; transition: all 0.15s ease;
}
.btn-wa:hover { background: #15803D; }
</style>

<script>
const matriksData = @json($matriksSiswa);
const namaEkskul = "{{ $selectedEkskul?->nama_ekskul }}";
const pembinaEkskul = "{{ $selectedEkskul?->nama_pembina }}";
const totalPertemuan = {{ $totalPertemuan }};
const periodeBulan = "{{ $bulan ? ($bulanList[$bulan] ?? 'Bulan ' . $bulan) : 'Semua Bulan' }} {{ $tahun }}";

function openWaModal() {
    renderWaText();
    document.getElementById('modalLaporanWA').style.display = 'flex';
}

function closeWaModal() {
    document.getElementById('modalLaporanWA').style.display = 'none';
}

function renderWaText() {
    let text = `*LAPORAN PRESENSI EKSTRAKURIKULER*\n`;
    text += `*${namaEkskul}*\n`;
    text += `Pembina: ${pembinaEkskul || '-'}\n`;
    text += `Periode: ${periodeBulan}\n`;
    text += `Total Pertemuan: ${totalPertemuan} kali\n`;
    text += `----------------------------------------\n\n`;
    text += `*DAFTAR KEHADIRAN ANGGOTA:*\n`;

    matriksData.forEach((s, idx) => {
        text += `${idx + 1}. *${s.nama}* (${s.kelas})\n`;
        text += `   Hadir: ${s.total_h} | Sakit: ${s.total_s} | Izin: ${s.total_i} | Alpa: ${s.total_a} (${s.persen_hadir}%)\n`;
    });

    text += `\n----------------------------------------\n`;
    text += `_Laporan otomatis digenerate dari Sistem SIAKAD Al-Muttaqin_`;

    document.getElementById('waTextarea').value = text;
}

function copyWaText() {
    const ta = document.getElementById('waTextarea');
    ta.select();
    navigator.clipboard.writeText(ta.value).then(() => {
        const notif = document.getElementById('copyNotif');
        notif.style.display = 'block';
        setTimeout(() => { notif.style.display = 'none'; }, 2500);
    });
}
</script>
@endsection
