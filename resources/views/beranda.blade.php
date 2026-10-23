@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<div style="display:flex; flex-direction:column; gap:24px;">

    {{-- Kartu Statistik Utama --}}
    <div class="stats-grid">
        <!-- Siswa Keseluruhan -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">
                    {{ $activeRoom === 'smp' ? 'Siswa SMP' : ($activeRoom === 'sma' ? 'Siswa SMA' : 'Santri Keseluruhan') }}
                </span>
                <div class="stat-icon-wrapper stat-icon-total">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($statistik['total_siswa'], 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Siswa Aktif -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Aktif di Asrama</span>
                <div class="stat-icon-wrapper stat-icon-aktif">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <polyline points="17 11 19 13 23 9"></polyline>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($statistik['siswa_aktif'], 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Siswa Pulang -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Izin Pulang</span>
                <div class="stat-icon-wrapper stat-icon-pulang">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($statistik['siswa_pulang'], 0, ',', '.') }}</div>
            </div>
        </div>

        <!-- Siswa Sakit -->
        <div class="stat-card">
            <div class="stat-card-header">
                <span class="stat-card-title">Izin Sakit</span>
                <div class="stat-icon-wrapper stat-icon-sakit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 12h-4l-3 9L9 3l-3 9H2"></path>
                    </svg>
                </div>
            </div>
            <div class="stat-card-body">
                <div class="stat-number">{{ number_format($statistik['siswa_sakit'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    {{-- Ringkasan Antar Lembaga (Khusus Ruang Kerja Yayasan / Super Admin) --}}
    @if($lembagaSummary)
    <div class="form-section-card">
        <div class="form-section-header">
            <div class="form-section-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9 22 9 12 15 12 15 22"></polyline>
                </svg>
            </div>
            <div class="form-section-title-wrap">
                <h2 class="form-section-title">Distribusi Santri Antar Lembaga</h2>
            </div>
        </div>

        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:16px;">
            <div style="padding:16px 20px; border-radius:12px; border:1px solid var(--border-light); background:var(--bg-surface); display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <span class="badge-lembaga badge-lembaga-pondok">Pondok Pesantren</span>
                    <div style="font-size:24px; font-weight:800; color:var(--text-primary); margin-top:8px;">
                        {{ $lembagaSummary['pondok'] }} <span style="font-size:13px; font-weight:500; color:var(--text-muted);">Santri Mukim</span>
                    </div>
                </div>
                <a href="{{ route('switch-lembaga', ['kode' => 'pondok']) }}" class="btn btn-sm btn-secondary" style="text-decoration:none; font-size:12px;">
                    Buka Room
                </a>
            </div>

            <div style="padding:16px 20px; border-radius:12px; border:1px solid var(--border-light); background:var(--bg-surface); display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <span class="badge-lembaga badge-lembaga-smp">SMP Al-Muttaqin</span>
                    <div style="font-size:24px; font-weight:800; color:var(--text-primary); margin-top:8px;">
                        {{ $lembagaSummary['smp'] }} <span style="font-size:13px; font-weight:500; color:var(--text-muted);">Siswa</span>
                    </div>
                </div>
                <a href="{{ route('switch-lembaga', ['kode' => 'smp']) }}" class="btn btn-sm btn-secondary" style="text-decoration:none; font-size:12px;">
                    Buka Room
                </a>
            </div>

            <div style="padding:16px 20px; border-radius:12px; border:1px solid var(--border-light); background:var(--bg-surface); display:flex; align-items:center; justify-content:space-between;">
                <div>
                    <span class="badge-lembaga badge-lembaga-sma">SMA Al-Muttaqin</span>
                    <div style="font-size:24px; font-weight:800; color:var(--text-primary); margin-top:8px;">
                        {{ $lembagaSummary['sma'] }} <span style="font-size:13px; font-weight:500; color:var(--text-muted);">Siswa</span>
                    </div>
                </div>
                <a href="{{ route('switch-lembaga', ['kode' => 'sma']) }}" class="btn btn-sm btn-secondary" style="text-decoration:none; font-size:12px;">
                    Buka Room
                </a>
            </div>
        </div>
    </div>
    @endif

</div>
@endsection
